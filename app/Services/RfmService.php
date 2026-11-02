<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RfmService
{
    /**
     * Default configurable fixed thresholds.
     */
    protected array $thresholds = [
        'recency' => [
            5 => 7,   // <= 7 days
            4 => 14,  // <= 14 days
            3 => 30,  // <= 30 days
            2 => 60,  // <= 60 days
            // 1 => > 60 days
        ],
        'frequency' => [
            5 => 10,  // >= 10 orders
            4 => 5,   // >= 5 orders
            3 => 3,   // >= 3 orders
            2 => 2,   // >= 2 orders
            // 1 => 1 order
        ],
        'monetary' => [
            5 => 750000, // >= Rp 750.000
            4 => 500000,  // >= Rp 500.000
            3 => 250000,  // >= Rp 250.000
            2 => 100000,  // >= Rp 100.000
            // 1 => < Rp 100.000
        ],
    ];

    public function __construct(array $customThresholds = [])
    {
        if (!empty($customThresholds)) {
            $this->thresholds = array_replace_recursive($this->thresholds, $customThresholds);
        }
    }

    /**
     * Process all users and compute RFM metrics, scores, segments, preferences, and summary KPI.
     *
     * @param Collection|array $users
     * @param Carbon|null $analysisDate
     * @param array $options ['force_method' => 'quintile'|'fixed_threshold']
     * @return array ['users' => Collection, 'summary' => array]
     */
    public function analyzeUsers(Collection|array $users, ?Carbon $analysisDate = null, array $options = []): array
    {
        $usersCollection = $users instanceof Collection ? $users : collect($users);
        $analysisDate = $analysisDate ? $analysisDate->copy() : Carbon::now();

        // 1. Calculate raw metrics for all users
        $rawRecords = $usersCollection->map(function ($user) use ($analysisDate) {
            $raw = $this->computeRawMetrics($user, $analysisDate);
            return [
                'user' => $user,
                'raw' => $raw,
            ];
        });

        // 2. Separate users with valid transactions
        $validRecords = $rawRecords->filter(fn ($item) => $item['raw']['has_valid_orders'])->values();

        // 3. Determine scoring method (Quintile vs Fixed Threshold)
        $scoringMethod = $this->determineScoringMethod($validRecords, $options);

        // 4. Compute quintile cutoffs if quintile method is eligible and chosen
        $quintileCutoffs = [];
        if ($scoringMethod === 'quintile') {
            $quintileCutoffs = [
                'recency' => $this->calculateQuintileCutoffs($validRecords->pluck('raw.recency_days')->all()),
                'frequency' => $this->calculateQuintileCutoffs($validRecords->pluck('raw.frequency_paid_orders')->all()),
                'monetary' => $this->calculateQuintileCutoffs($validRecords->pluck('raw.monetary_total_spent')->all()),
            ];
        }

        // 5. Score and segment each user
        $processedUsers = $rawRecords->map(function ($item) use ($scoringMethod, $quintileCutoffs) {
            $user = $item['user'];
            $raw = $item['raw'];

            if (!$raw['has_valid_orders']) {
                $segmentData = $this->determineSegment(null, null, null);
                $rfmData = array_merge($raw, [
                    'score_r' => null,
                    'score_f' => null,
                    'score_m' => null,
                    'rfm_code' => null,
                    'rfm_total_score' => null,
                    'segment' => $segmentData['segment'],
                    'segment_description' => $segmentData['segment_description'],
                    'crm_recommendation' => $segmentData['crm_recommendation'],
                    'badge_color' => $segmentData['badge_color'],
                    'voucher_preset' => $segmentData['voucher_preset'] ?? null,
                    'scoring_method' => null,
                ]);
            } else {
                $scoreR = $this->scoreRecency($raw['recency_days'], $scoringMethod, $quintileCutoffs['recency'] ?? []);
                $scoreF = $this->scoreFrequency($raw['frequency_paid_orders'], $scoringMethod, $quintileCutoffs['frequency'] ?? []);
                $scoreM = $this->scoreMonetary($raw['monetary_total_spent'], $scoringMethod, $quintileCutoffs['monetary'] ?? []);

                $rfmCode = sprintf('%d%d%d', $scoreR, $scoreF, $scoreM);
                $rfmTotalScore = $scoreR + $scoreF + $scoreM;

                $segmentData = $this->determineSegment($scoreR, $scoreF, $scoreM);

                $rfmData = array_merge($raw, [
                    'score_r' => $scoreR,
                    'score_f' => $scoreF,
                    'score_m' => $scoreM,
                    'rfm_code' => $rfmCode,
                    'rfm_total_score' => $rfmTotalScore,
                    'segment' => $segmentData['segment'],
                    'segment_description' => $segmentData['segment_description'],
                    'crm_recommendation' => $segmentData['crm_recommendation'],
                    'badge_color' => $segmentData['badge_color'],
                    'voucher_preset' => $segmentData['voucher_preset'] ?? null,
                    'scoring_method' => $scoringMethod,
                ]);
            }

            // Format for Inertia & Vue
            $userData = is_array($user) ? $user : $user->toArray();
            $userData['orders_count'] = $raw['total_orders'];
            $userData['orders_max_created_at'] = $raw['recency_last_order_at'];
            $userData['rfm'] = $rfmData;

            return $userData;
        });

        // 6. Generate summary KPI
        $summary = $this->generateSummary($processedUsers);

        return [
            'users' => $processedUsers,
            'summary' => $summary,
            'scoring_method' => $scoringMethod,
        ];
    }

    /**
     * Compute RFM and segment discount for a single user using deterministic thresholds.
     */
    public function analyzeSingleUser($user, ?Carbon $analysisDate = null): array
    {
        $analysisDate = $analysisDate ? $analysisDate->copy() : Carbon::now('Asia/Jakarta');
        $raw = $this->computeRawMetrics($user, $analysisDate);

        if (!$raw['has_valid_orders']) {
            $segmentData = $this->determineSegment(null, null, null);
            return array_merge($raw, [
                'score_r' => null,
                'score_f' => null,
                'score_m' => null,
                'rfm_code' => null,
                'rfm_total_score' => null,
                'segment' => $segmentData['segment'],
                'segment_description' => $segmentData['segment_description'],
                'crm_recommendation' => $segmentData['crm_recommendation'],
                'badge_color' => $segmentData['badge_color'],
                'voucher_preset' => $segmentData['voucher_preset'] ?? null,
                'scoring_method' => 'fixed_threshold',
            ]);
        }

        $rScore = $this->scoreRecencyFixed($raw['recency_days']);
        $fScore = $this->scoreFrequencyFixed($raw['frequency_paid_orders']);
        $mScore = $this->scoreMonetaryFixed($raw['monetary_total_spent']);

        $segmentData = $this->determineSegment($rScore, $fScore, $mScore);

        return array_merge($raw, [
            'score_r' => $rScore,
            'score_f' => $fScore,
            'score_m' => $mScore,
            'rfm_code' => "{$rScore}{$fScore}{$mScore}",
            'rfm_total_score' => $rScore + $fScore + $mScore,
            'segment' => $segmentData['segment'],
            'segment_description' => $segmentData['segment_description'],
            'crm_recommendation' => $segmentData['crm_recommendation'],
            'badge_color' => $segmentData['badge_color'],
            'voucher_preset' => $segmentData['voucher_preset'] ?? null,
            'scoring_method' => 'fixed_threshold',
        ]);
    }

    /**
     * Compute raw RFM metrics and preferences for a single user.
     */
    public function computeRawMetrics(User|array $user, Carbon $analysisDate): array
    {
        $orders = [];
        if (is_array($user)) {
            $orders = $user['orders'] ?? [];
        } else {
            $orders = $user->orders ?? [];
        }

        $allOrders = collect($orders);

        // Valid orders strictly have payment_status === 'paid'
        $paidOrders = $allOrders->filter(function ($order) {
            $status = is_array($order) ? ($order['payment_status'] ?? null) : ($order->payment_status ?? null);
            return $status === 'paid';
        })->sortByDesc(function ($order) {
            $createdAt = is_array($order) ? ($order['created_at'] ?? null) : ($order->created_at ?? null);
            return $createdAt ? Carbon::parse($createdAt)->timestamp : 0;
        })->values();

        $totalOrdersCount = $allOrders->count();
        $frequencyPaidCount = $paidOrders->count();

        if ($frequencyPaidCount === 0) {
            return [
                'has_valid_orders' => false,
                'recency_last_order_at' => null,
                'recency_days' => null,
                'recency_label' => 'Belum pernah beli',
                'frequency_paid_orders' => 0,
                'total_orders' => $totalOrdersCount,
                'monetary_total_spent' => 0.0,
                'monetary_avg_order' => 0.0,
                'top_ingredients' => [],
                'top_categories' => [],
                'top_menus' => [],
            ];
        }

        // Recency calculation
        $latestOrder = $paidOrders->first();
        $latestOrderCreatedAt = is_array($latestOrder)
            ? ($latestOrder['created_at'] ?? null)
            : ($latestOrder->created_at ?? null);

        $lastOrderDate = $latestOrderCreatedAt ? Carbon::parse($latestOrderCreatedAt) : null;
        $recencyDays = null;
        $recencyLabel = 'Belum pernah beli';

        if ($lastOrderDate) {
            // Days difference up to analysisDate (0 if on same day, non-negative)
            $diff = (int) $lastOrderDate->diffInDays($analysisDate);
            $recencyDays = max(0, $diff);
            $recencyLabel = $recencyDays === 0 ? 'Hari ini' : ($recencyDays . ' hari lalu');
        }

        // Monetary calculation
        $monetaryTotalSpent = (float) $paidOrders->sum(function ($order) {
            return is_array($order) ? (float) ($order['total_amount'] ?? 0) : (float) ($order->total_amount ?? 0);
        });

        $monetaryAvgOrder = $frequencyPaidCount > 0 ? round($monetaryTotalSpent / $frequencyPaidCount, 0) : 0.0;

        // Preferences extraction (top menus, ingredients, categories)
        $preferences = $this->extractPreferences($paidOrders);

        return [
            'has_valid_orders' => true,
            'recency_last_order_at' => $lastOrderDate ? $lastOrderDate->toDateTimeString() : null,
            'recency_days' => $recencyDays,
            'recency_label' => $recencyLabel,
            'frequency_paid_orders' => $frequencyPaidCount,
            'total_orders' => $totalOrdersCount,
            'monetary_total_spent' => $monetaryTotalSpent,
            'monetary_avg_order' => $monetaryAvgOrder,
            'top_ingredients' => $preferences['top_ingredients'],
            'top_categories' => $preferences['top_categories'],
            'top_menus' => $preferences['top_menus'],
        ];
    }

    /**
     * Determine scoring method based on data distribution or options.
     */
    public function determineScoringMethod(Collection $validRecords, array $options = []): string
    {
        if (isset($options['force_method']) && in_array($options['force_method'], ['quintile', 'fixed_threshold'], true)) {
            return $options['force_method'];
        }

        // Need at least 5 distinct customers with valid transactions
        $count = $validRecords->count();
        if ($count < 5) {
            return 'fixed_threshold';
        }

        // Check if each metric has at least 5 distinct values and strictly ascending cutoffs
        $metrics = [
            'recency_days',
            'frequency_paid_orders',
            'monetary_total_spent',
        ];

        foreach ($metrics as $metric) {
            $values = $validRecords->pluck("raw.{$metric}")->all();
            $uniqueCount = count(array_unique($values));
            if ($uniqueCount < 5) {
                return 'fixed_threshold';
            }

            $cutoffs = $this->calculateQuintileCutoffs($values);
            // Must have 4 strictly increasing cutoffs (q20 < q40 < q60 < q80)
            if (count($cutoffs) < 4) {
                return 'fixed_threshold';
            }
            if ($cutoffs[0] >= $cutoffs[1] || $cutoffs[1] >= $cutoffs[2] || $cutoffs[2] >= $cutoffs[3]) {
                return 'fixed_threshold';
            }
        }

        return 'quintile';
    }

    /**
     * Calculate 20th, 40th, 60th, 80th percentiles for an array of values.
     * Returns array [q20, q40, q60, q80].
     */
    public function calculateQuintileCutoffs(array $values): array
    {
        if (empty($values)) {
            return [];
        }

        sort($values, SORT_NUMERIC);
        $n = count($values);

        $percentiles = [0.20, 0.40, 0.60, 0.80];
        $cutoffs = [];

        foreach ($percentiles as $p) {
            $index = $p * ($n - 1);
            $lower = (int) floor($index);
            $upper = (int) ceil($index);
            $fraction = $index - $lower;

            $val = $values[$lower] + $fraction * ($values[$upper] - $values[$lower]);
            $cutoffs[] = (float) $val;
        }

        return $cutoffs;
    }

    /**
     * Score Recency (Reversed direction: smaller days => higher score).
     */
    public function scoreRecency(?int $days, string $method = 'fixed_threshold', array $cutoffs = []): int
    {
        if ($days === null) {
            return 1;
        }

        if ($method === 'quintile' && count($cutoffs) === 4) {
            [$q20, $q40, $q60, $q80] = $cutoffs;
            if ($days <= $q20) return 5;
            if ($days <= $q40) return 4;
            if ($days <= $q60) return 3;
            if ($days <= $q80) return 2;
            return 1;
        }

        // Fixed threshold fallback
        $t = $this->thresholds['recency'];
        if ($days <= $t[5]) return 5;
        if ($days <= $t[4]) return 4;
        if ($days <= $t[3]) return 3;
        if ($days <= $t[2]) return 2;
        return 1;
    }

    public function scoreRecencyFixed(?int $days): int
    {
        return $this->scoreRecency($days, 'fixed_threshold');
    }

    /**
     * Score Frequency (Normal direction: higher count => higher score).
     */
    public function scoreFrequency(int $count, string $method = 'fixed_threshold', array $cutoffs = []): int
    {
        if ($count <= 0) {
            return 1;
        }

        if ($method === 'quintile' && count($cutoffs) === 4) {
            [$q20, $q40, $q60, $q80] = $cutoffs;
            if ($count >= $q80) return 5;
            if ($count >= $q60) return 4;
            if ($count >= $q40) return 3;
            if ($count >= $q20) return 2;
            return 1;
        }

        // Fixed threshold fallback
        $t = $this->thresholds['frequency'];
        if ($count >= $t[5]) return 5;
        if ($count >= $t[4]) return 4;
        if ($count >= $t[3]) return 3;
        if ($count >= $t[2]) return 2;
        return 1;
    }

    public function scoreFrequencyFixed(int $count): int
    {
        return $this->scoreFrequency($count, 'fixed_threshold');
    }

    /**
     * Score Monetary (Normal direction: higher spent => higher score).
     */
    public function scoreMonetary(float $spent, string $method = 'fixed_threshold', array $cutoffs = []): int
    {
        if ($spent <= 0) {
            return 1;
        }

        if ($method === 'quintile' && count($cutoffs) === 4) {
            [$q20, $q40, $q60, $q80] = $cutoffs;
            if ($spent >= $q80) return 5;
            if ($spent >= $q60) return 4;
            if ($spent >= $q40) return 3;
            if ($spent >= $q20) return 2;
            return 1;
        }

        // Fixed threshold fallback
        $t = $this->thresholds['monetary'];
        if ($spent >= $t[5]) return 5;
        if ($spent >= $t[4]) return 4;
        if ($spent >= $t[3]) return 3;
        if ($spent >= $t[2]) return 2;
        return 1;
    }

    public function scoreMonetaryFixed(float $spent): int
    {
        return $this->scoreMonetary($spent, 'fixed_threshold');
    }

    /**
     * Determine segment, description, and recommendation based on R, F, M scores.
     * Evaluates 8 discrete RFM quadrants where score 3 is seamlessly accumulated.
     */
    public function determineSegment(?int $r, ?int $f, ?int $m): array
    {
        if ($r === null || $f === null || $m === null) {
            return [
                'segment' => 'Belum Bertransaksi',
                'pattern' => 'Tanpa Transaksi',
                'segment_description' => 'Pelanggan terdaftar yang belum memiliki riwayat transaksi valid.',
                'crm_recommendation' => 'Penawaran voucher selamat datang untuk transaksi pertama.',
                'badge_color' => 'gray',
                'voucher_preset' => [
                    'code_prefix' => 'PERDANA',
                    'title' => 'Voucher Diskon Pesanan Perdana',
                    'discount_type' => 'percentage',
                    'discount_value' => 15,
                    'min_purchase' => 30000,
                    'validity_days' => 7,
                    'notes' => 'Diskon selamat datang untuk pesanan pertama.',
                    'crm_action_label' => 'Diskon Pesanan Perdana 15% (Min. Rp 30.000)',
                ],
            ];
        }

        $isRHigh = $r >= 4; // R >= 4: Tinggi (transaksi <= 14 hari), R <= 3: Rendah/Mulai Pasif
        $isFHigh = $f >= 3; // F >= 3: Tinggi (repeat order rutin), F <= 2: Rendah
        $isMHigh = $m >= 3; // M >= 3: Tinggi (belanja signifikan), M <= 2: Rendah

        // 1. R+ F+ M+ : Champions / Super Loyal
        if ($isRHigh && $isFHigh && $isMHigh) {
            return [
                'segment' => 'Champions',
                'pattern' => 'RFM Tinggi',
                'segment_description' => 'Pelanggan aktif dengan transaksi sering dan kontribusi belanja tinggi.',
                'crm_recommendation' => 'Apresiasi loyalitas, penawaran menu eksklusif, dan prioritas layanan.',
                'badge_color' => 'black',
                'voucher_preset' => [
                    'code_prefix' => 'CHAMP',
                    'title' => 'Reward Eksklusif Pelanggan Champion',
                    'discount_type' => 'percentage',
                    'discount_value' => 20,
                    'min_purchase' => 75000,
                    'validity_days' => 14,
                    'notes' => 'Apresiasi loyalitas dan kontribusi belanja tinggi.',
                    'crm_action_label' => 'Diskon Eksklusif 20% (Min. Rp 75.000)',
                ],
            ];
        }

        // 2. R+ F+ M- : Loyal Repeaters
        if ($isRHigh && $isFHigh && !$isMHigh) {
            return [
                'segment' => 'Loyal Repeaters',
                'pattern' => 'RF Tinggi, M Rendah',
                'segment_description' => 'Pelanggan aktif dan rutin memesan, namun nominal belanja per pesanan masih rendah.',
                'crm_recommendation' => 'Tawarkan paket katering mingguan atau bundling menu untuk meningkatkan nilai belanja.',
                'badge_color' => 'black',
                'voucher_preset' => [
                    'code_prefix' => 'LOYAL',
                    'title' => 'Voucher Paket Langganan Mingguan',
                    'discount_type' => 'percentage',
                    'discount_value' => 15,
                    'min_purchase' => 50000,
                    'validity_days' => 14,
                    'notes' => 'Peningkatan paket dan pemesanan rutin berulang.',
                    'crm_action_label' => 'Diskon Langganan 15% (Min. Rp 50.000)',
                ],
            ];
        }

        // 3. R+ F- M+ : Big Spenders (Baru)
        if ($isRHigh && !$isFHigh && $isMHigh) {
            return [
                'segment' => 'Big Spenders (Baru)',
                'pattern' => 'RM Tinggi, F Rendah',
                'segment_description' => 'Pelanggan baru bertransaksi dengan nominal belanja tinggi namun frekuensi pemesanan masih sedikit.',
                'crm_recommendation' => 'Dorong pemesanan berulang dengan promo paket menu untuk pesanan kedua.',
                'badge_color' => 'black',
                'voucher_preset' => [
                    'code_prefix' => 'SPEND',
                    'title' => 'Voucher Pemesanan Berulang',
                    'discount_type' => 'percentage',
                    'discount_value' => 15,
                    'min_purchase' => 75000,
                    'validity_days' => 10,
                    'notes' => 'Mendorong transaksi kedua dengan nominal belanja tinggi.',
                    'crm_action_label' => 'Diskon Repeat Order 15% (Min. Rp 75.000)',
                ],
            ];
        }

        // 4. R+ F- M- : New / Recent Customers
        if ($isRHigh && !$isFHigh && !$isMHigh) {
            return [
                'segment' => 'New / Recent Customers',
                'pattern' => 'R Tinggi, FM Rendah',
                'segment_description' => 'Pelanggan baru dengan frekuensi dan nominal transaksi tahap awal.',
                'crm_recommendation' => 'Pengenalan variasi menu katering dan promosi pemesanan berikutnya.',
                'badge_color' => 'gray',
                'voucher_preset' => [
                    'code_prefix' => 'WELCOME',
                    'title' => 'Diskon Sambutan Pembelian Berikutnya',
                    'discount_type' => 'percentage',
                    'discount_value' => 10,
                    'min_purchase' => 40000,
                    'validity_days' => 7,
                    'notes' => 'Pengenalan variasi menu katering Dova Kitchen.',
                    'crm_action_label' => 'Diskon Sambutan 10% (Min. Rp 40.000)',
                ],
            ];
        }

        // 5. R- F+ M+ : At Risk VIP
        if (!$isRHigh && $isFHigh && $isMHigh) {
            return [
                'segment' => 'At Risk VIP',
                'pattern' => 'FM Tinggi, R Rendah',
                'segment_description' => 'Pelanggan bernilai tinggi dan sering belanja di masa lalu, namun sudah lama tidak bertransaksi.',
                'crm_recommendation' => 'Pendekatan personal via WhatsApp dan insentif diskon reaktivasi.',
                'badge_color' => 'red',
                'voucher_preset' => [
                    'code_prefix' => 'VIPWIN',
                    'title' => 'Promo Reaktivasi Pelanggan VIP',
                    'discount_type' => 'percentage',
                    'discount_value' => 25,
                    'min_purchase' => 50000,
                    'validity_days' => 10,
                    'notes' => 'Reaktivasi pelanggan bernilai belanja tinggi yang sedang pasif.',
                    'crm_action_label' => 'Diskon Reaktivasi VIP 25% (Min. Rp 50.000)',
                ],
            ];
        }

        // 6. R- F+ M- : At Risk Routine
        if (!$isRHigh && $isFHigh && !$isMHigh) {
            return [
                'segment' => 'At Risk Routine',
                'pattern' => 'F Tinggi, RM Rendah',
                'segment_description' => 'Pelanggan yang sebelumnya rutin memesan namun mulai pasif.',
                'crm_recommendation' => 'Pengingat menu favorit dan penawaran khusus untuk mendorong pesanan kembali.',
                'badge_color' => 'red',
                'voucher_preset' => [
                    'code_prefix' => 'MISSYOU',
                    'title' => 'Voucher Pemesanan Kembali',
                    'discount_type' => 'percentage',
                    'discount_value' => 20,
                    'min_purchase' => 40000,
                    'validity_days' => 10,
                    'notes' => 'Penawaran kembali memesan untuk pelanggan rutin yang mulai pasif.',
                    'crm_action_label' => 'Diskon Comeback Rutin 20% (Min. Rp 40.000)',
                ],
            ];
        }

        // 7. R- F- M+ : Previously Big Spenders
        if (!$isRHigh && !$isFHigh && $isMHigh) {
            return [
                'segment' => 'Previously Big Spenders',
                'pattern' => 'M Tinggi, RF Rendah',
                'segment_description' => 'Pelanggan yang pernah melakukan transaksi besar namun frekuensi rendah dan sudah lama tidak aktif.',
                'crm_recommendation' => 'Penawaran paket katering acara atau porsi keluarga untuk mengaktifkan kembali.',
                'badge_color' => 'red',
                'voucher_preset' => [
                    'code_prefix' => 'COMEBACK',
                    'title' => 'Penawaran Spesial Pesanan Kembali',
                    'discount_type' => 'percentage',
                    'discount_value' => 20,
                    'min_purchase' => 60000,
                    'validity_days' => 10,
                    'notes' => 'Mendorong pelanggan bernilai belanja besar untuk memesan kembali.',
                    'crm_action_label' => 'Diskon Belanja Besar 20% (Min. Rp 60.000)',
                ],
            ];
        }

        // 8. R- F- M- : Hibernating / Pasif
        return [
            'segment' => 'Hibernating / Pasif',
            'pattern' => 'RFM Rendah',
            'segment_description' => 'Pelanggan pasif dengan frekuensi dan nominal belanja rendah.',
            'crm_recommendation' => 'Pengiriman informasi promo menu hemat secara berkala.',
            'badge_color' => 'gray',
            'voucher_preset' => [
                'code_prefix' => 'WINBACK',
                'title' => 'Voucher Diskon Pelanggan Pasif',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'min_purchase' => 30000,
                'validity_days' => 7,
                'notes' => 'Membangunkan kembali minat beli pelanggan.',
                'crm_action_label' => 'Diskon Bangunkan Pelanggan 15% (Min. Rp 30.000)',
            ],
        ];
    }

    /**
     * Extract favorite ingredients, categories, and menus from valid paid orders.
     */
    public function extractPreferences(Collection $paidOrders): array
    {
        $ingredientCounts = [];
        $categoryCounts = [];
        $menuCounts = [];

        foreach ($paidOrders as $order) {
            $orderItems = is_array($order) ? ($order['order_items'] ?? []) : ($order->orderItems ?? []);

            foreach ($orderItems as $item) {
                $menu = is_array($item) ? ($item['menu'] ?? null) : ($item->menu ?? null);
                if (!$menu) {
                    continue;
                }

                $menuName = is_array($menu) ? ($menu['name'] ?? 'Menu') : ($menu->name ?? 'Menu');
                $photoPath = is_array($menu) ? ($menu['photo_path'] ?? null) : ($menu->photo_path ?? null);
                $unitPrice = is_array($item) ? (float) ($item['unit_price'] ?? 0) : (float) ($item->unit_price ?? 0);
                $portionQuantity = is_array($item) ? ($item['portion_quantity'] ?? 1) : ($item->portion_quantity ?? 1);
                $portion = (int) ($portionQuantity ?: 1);

                // Menu tally
                if (!isset($menuCounts[$menuName])) {
                    $menuCounts[$menuName] = [
                        'name' => $menuName,
                        'photo_path' => $photoPath,
                        'unit_price' => $unitPrice,
                        'total_portions' => 0,
                        'times_ordered' => 0,
                    ];
                }
                $menuCounts[$menuName]['total_portions'] += $portion;
                $menuCounts[$menuName]['times_ordered'] += 1;

                // Allergens / Ingredients tally
                $allergens = is_array($menu) ? ($menu['allergens'] ?? []) : ($menu->allergens ?? []);
                foreach ($allergens as $allergen) {
                    $id = is_array($allergen) ? ($allergen['id'] ?? null) : ($allergen->id ?? null);
                    $name = is_array($allergen) ? ($allergen['name'] ?? 'Bahan') : ($allergen->name ?? 'Bahan');
                    $category = is_array($allergen) ? ($allergen['category'] ?? 'Lainnya') : ($allergen->category ?? 'Lainnya');
                    $category = $category ?: 'Lainnya';

                    if (!isset($ingredientCounts[$id])) {
                        $ingredientCounts[$id] = [
                            'id' => $id,
                            'name' => $name,
                            'category' => $category,
                            'total_portions' => 0,
                            'times_ordered' => 0,
                        ];
                    }
                    $ingredientCounts[$id]['total_portions'] += $portion;
                    $ingredientCounts[$id]['times_ordered'] += 1;

                    $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + $portion;
                }
            }
        }

        // Sort descending
        $sortedIngredients = array_values($ingredientCounts);
        usort($sortedIngredients, fn ($a, $b) => $b['total_portions'] <=> $a['total_portions']);

        $sortedMenus = array_values($menuCounts);
        usort($sortedMenus, fn ($a, $b) => $b['total_portions'] <=> $a['total_portions']);

        arsort($categoryCounts);

        return [
            'top_ingredients' => array_slice($sortedIngredients, 0, 5),
            'top_categories' => $categoryCounts,
            'top_menus' => array_slice($sortedMenus, 0, 4),
        ];
    }

    /**
     * Generate summary KPI dashboard and segment distributions.
     */
    public function generateSummary(Collection $processedUsers): array
    {
        $totalCustomers = $processedUsers->count();
        $totalRevenue = (float) $processedUsers->sum('rfm.monetary_total_spent');
        $totalTransactions = (int) $processedUsers->sum('rfm.frequency_paid_orders');

        // Active customers: has valid orders and recency <= 14 days (or score_r >= 4)
        $activeCustomers = $processedUsers->filter(function ($u) {
            $days = $u['rfm']['recency_days'] ?? null;
            return $days !== null && $days <= 14 && ($u['rfm']['frequency_paid_orders'] ?? 0) > 0;
        })->count();

        // Segments breakdown (8 Categories)
        $championsCount = $processedUsers->where('rfm.segment', 'Champions')->count();
        $loyalRepeatersCount = $processedUsers->where('rfm.segment', 'Loyal Repeaters')->count();
        $bigSpendersCount = $processedUsers->where('rfm.segment', 'Big Spenders (Baru)')->count();
        $newCustomersCount = $processedUsers->where('rfm.segment', 'New / Recent Customers')->count();
        $atRiskVipCount = $processedUsers->where('rfm.segment', 'At Risk VIP')->count();
        $atRiskRoutineCount = $processedUsers->where('rfm.segment', 'At Risk Routine')->count();
        $prevBigSpendersCount = $processedUsers->where('rfm.segment', 'Previously Big Spenders')->count();
        $hibernatingCount = $processedUsers->where('rfm.segment', 'Hibernating / Pasif')->count();
        $unpurchasedCount = $processedUsers->where('rfm.segment', 'Belum Bertransaksi')->count();

        // Combined At Risk
        $atRiskTotal = $atRiskVipCount + $atRiskRoutineCount;

        // RFM code distribution
        $rfmCodeDistribution = [];
        foreach ($processedUsers as $u) {
            $code = $u['rfm']['rfm_code'] ?? null;
            if ($code) {
                $rfmCodeDistribution[$code] = ($rfmCodeDistribution[$code] ?? 0) + 1;
            }
        }
        ksort($rfmCodeDistribution);

        return [
            // Core KPIs
            'total_customers' => $totalCustomers,
            'total_revenue' => $totalRevenue,
            'total_transactions' => $totalTransactions,
            'active_customers' => $activeCustomers,

            // 8 Segment Counts
            'champions_count' => $championsCount,
            'loyal_repeaters_count' => $loyalRepeatersCount,
            'big_spenders_count' => $bigSpendersCount,
            'new_customers_count' => $newCustomersCount,
            'at_risk_vip_count' => $atRiskVipCount,
            'at_risk_routine_count' => $atRiskRoutineCount,
            'previously_big_spenders_count' => $prevBigSpendersCount,
            'hibernating_count' => $hibernatingCount,
            'unpurchased_customers_count' => $unpurchasedCount,

            // Backward compatibility
            'at_risk_customers_count' => $atRiskTotal,
            'at_risk_customers' => $atRiskTotal,
            'loyal_customers' => $loyalRepeatersCount,
            'loyal_customers_count' => $loyalRepeatersCount,

            // RFM Code Distribution
            'rfm_code_distribution' => $rfmCodeDistribution,
        ];
    }
}
