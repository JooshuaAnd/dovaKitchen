<?php

namespace Tests\Unit;

use App\Services\RfmService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class RfmServiceTest extends TestCase
{
    protected RfmService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RfmService();
    }

    /**
     * Test Recency calculation with deterministic analysis date.
     */
    public function test_recency_calculation(): void
    {
        $analysisDate = Carbon::parse('2026-10-23 10:00:00');

        // Order today (0 days)
        $userToday = [
            'id' => 1,
            'name' => 'Alice',
            'orders' => [
                ['payment_status' => 'paid', 'created_at' => '2026-10-23 08:00:00', 'total_amount' => 50000],
            ],
        ];

        // Order 5 days ago
        $user5Days = [
            'id' => 2,
            'name' => 'Bob',
            'orders' => [
                ['payment_status' => 'paid', 'created_at' => '2026-10-18 10:00:00', 'total_amount' => 75000],
            ],
        ];

        $rawToday = $this->service->computeRawMetrics($userToday, $analysisDate);
        $raw5Days = $this->service->computeRawMetrics($user5Days, $analysisDate);

        $this->assertEquals(0, $rawToday['recency_days']);
        $this->assertEquals('Hari ini', $rawToday['recency_label']);

        $this->assertEquals(5, $raw5Days['recency_days']);
        $this->assertEquals('5 hari lalu', $raw5Days['recency_label']);
    }

    /**
     * Test Frequency counts unique valid orders, not items or portions.
     */
    public function test_frequency_calculation_counts_unique_orders(): void
    {
        $analysisDate = Carbon::parse('2026-10-23 10:00:00');

        $user = [
            'id' => 1,
            'name' => 'Charlie',
            'orders' => [
                [
                    'payment_status' => 'paid',
                    'created_at' => '2026-10-20 12:00:00',
                    'total_amount' => 120000,
                    'order_items' => [
                        ['menu' => ['name' => 'Ayam Geprek', 'allergens' => []], 'portion_quantity' => 5, 'unit_price' => 20000],
                        ['menu' => ['name' => 'Es Teh Manis', 'allergens' => []], 'portion_quantity' => 2, 'unit_price' => 10000],
                    ]
                ],
                [
                    'payment_status' => 'paid',
                    'created_at' => '2026-10-22 12:00:00',
                    'total_amount' => 30000,
                    'order_items' => [
                        ['menu' => ['name' => 'Nasi Goreng', 'allergens' => []], 'portion_quantity' => 1, 'unit_price' => 30000],
                    ]
                ]
            ],
        ];

        $raw = $this->service->computeRawMetrics($user, $analysisDate);

        // 2 distinct orders (even though portions total 8)
        $this->assertEquals(2, $raw['frequency_paid_orders']);
        $this->assertEquals(2, $raw['total_orders']);
    }

    /**
     * Test Monetary calculation avoids double counting and correctly sums valid totals.
     */
    public function test_monetary_calculation(): void
    {
        $analysisDate = Carbon::parse('2026-10-23 10:00:00');

        $user = [
            'id' => 1,
            'name' => 'Diana',
            'orders' => [
                ['payment_status' => 'paid', 'created_at' => '2026-10-01 10:00:00', 'total_amount' => 150000],
                ['payment_status' => 'paid', 'created_at' => '2026-10-10 10:00:00', 'total_amount' => 250000],
            ],
        ];

        $raw = $this->service->computeRawMetrics($user, $analysisDate);

        $this->assertEquals(400000.0, $raw['monetary_total_spent']);
        $this->assertEquals(200000.0, $raw['monetary_avg_order']);
    }

    /**
     * Test customers without valid transactions.
     */
    public function test_customer_without_valid_transactions(): void
    {
        $analysisDate = Carbon::parse('2026-10-23 10:00:00');

        $userNoOrders = [
            'id' => 1,
            'name' => 'Eve',
            'orders' => [],
        ];

        $result = $this->service->analyzeUsers([$userNoOrders], $analysisDate);
        $u = $result['users'][0];

        $this->assertNull($u['rfm']['recency_days']);
        $this->assertNull($u['rfm']['recency_last_order_at']);
        $this->assertEquals('Belum pernah beli', $u['rfm']['recency_label']);
        $this->assertEquals(0, $u['rfm']['frequency_paid_orders']);
        $this->assertEquals(0.0, $u['rfm']['monetary_total_spent']);
        $this->assertNull($u['rfm']['score_r']);
        $this->assertNull($u['rfm']['score_f']);
        $this->assertNull($u['rfm']['score_m']);
        $this->assertNull($u['rfm']['rfm_code']);
        $this->assertNull($u['rfm']['rfm_total_score']);
        $this->assertEquals('Belum Bertransaksi', $u['rfm']['segment']);
    }

    /**
     * Test pending, rejected, and awaiting_verification orders are strictly excluded.
     */
    public function test_unpaid_and_rejected_transactions_excluded(): void
    {
        $analysisDate = Carbon::parse('2026-10-23 10:00:00');

        $userWithPendingOnly = [
            'id' => 1,
            'name' => 'Frank',
            'orders' => [
                ['payment_status' => 'pending_payment', 'created_at' => '2026-10-22 10:00:00', 'total_amount' => 500000],
                ['payment_status' => 'rejected', 'created_at' => '2026-10-21 10:00:00', 'total_amount' => 400000],
                ['payment_status' => 'awaiting_verification', 'created_at' => '2026-10-20 10:00:00', 'total_amount' => 300000],
            ],
        ];

        $raw = $this->service->computeRawMetrics($userWithPendingOnly, $analysisDate);

        $this->assertFalse($raw['has_valid_orders']);
        $this->assertEquals(0, $raw['frequency_paid_orders']);
        $this->assertEquals(3, $raw['total_orders']);
        $this->assertEquals(0.0, $raw['monetary_total_spent']);
        $this->assertNull($raw['recency_days']);
    }

    /**
     * Test Target Scores: 555 (Champions).
     */
    public function test_score_555_champions(): void
    {
        $analysisDate = Carbon::parse('2026-10-23 10:00:00');

        $user = [
            'id' => 1,
            'name' => 'Grace',
            'orders' => [
                ['payment_status' => 'paid', 'created_at' => '2026-10-21 10:00:00', 'total_amount' => 500000], // 2 days ago (<= 7 => R=5)
                ['payment_status' => 'paid', 'created_at' => '2026-10-15 10:00:00', 'total_amount' => 400000],
                ['payment_status' => 'paid', 'created_at' => '2026-10-10 10:00:00', 'total_amount' => 100000],
                ['payment_status' => 'paid', 'created_at' => '2026-10-05 10:00:00', 'total_amount' => 50000],
                ['payment_status' => 'paid', 'created_at' => '2026-10-01 10:00:00', 'total_amount' => 50000],
                ['payment_status' => 'paid', 'created_at' => '2026-09-25 10:00:00', 'total_amount' => 50000],
                ['payment_status' => 'paid', 'created_at' => '2026-09-20 10:00:00', 'total_amount' => 50000],
                ['payment_status' => 'paid', 'created_at' => '2026-09-15 10:00:00', 'total_amount' => 50000],
                ['payment_status' => 'paid', 'created_at' => '2026-09-10 10:00:00', 'total_amount' => 50000],
                ['payment_status' => 'paid', 'created_at' => '2026-09-01 10:00:00', 'total_amount' => 50000], // 10 orders (>= 10 => F=5), total 1,350,000 (>= 1,000,000 => M=5)
            ],
        ];

        $result = $this->service->analyzeUsers([$user], $analysisDate, ['force_method' => 'fixed_threshold']);
        $u = $result['users'][0];

        $this->assertEquals(5, $u['rfm']['score_r']);
        $this->assertEquals(5, $u['rfm']['score_f']);
        $this->assertEquals(5, $u['rfm']['score_m']);
        $this->assertEquals('555', $u['rfm']['rfm_code']);
        $this->assertEquals(15, $u['rfm']['rfm_total_score']);
        $this->assertEquals('Champions', $u['rfm']['segment']);
    }

    /**
     * Test Target Scores: 532 (Loyal Repeaters: R High, F High, M Low).
     */
    public function test_score_532_loyal_repeaters(): void
    {
        $analysisDate = Carbon::parse('2026-10-23 10:00:00');

        $user = [
            'id' => 1,
            'name' => 'Henry',
            'orders' => [
                ['payment_status' => 'paid', 'created_at' => '2026-10-20 10:00:00', 'total_amount' => 50000], // 3 days ago (<= 7 => R=5)
                ['payment_status' => 'paid', 'created_at' => '2026-10-12 10:00:00', 'total_amount' => 50000],
                ['payment_status' => 'paid', 'created_at' => '2026-10-05 10:00:00', 'total_amount' => 50000], // 3 orders (F=3), total 150,000 (100k..250k => M=2)
            ],
        ];

        $result = $this->service->analyzeUsers([$user], $analysisDate, ['force_method' => 'fixed_threshold']);
        $u = $result['users'][0];

        $this->assertEquals(5, $u['rfm']['score_r']);
        $this->assertEquals(3, $u['rfm']['score_f']);
        $this->assertEquals(2, $u['rfm']['score_m']);
        $this->assertEquals('532', $u['rfm']['rfm_code']);
        $this->assertEquals(10, $u['rfm']['rfm_total_score']);
        $this->assertEquals('Loyal Repeaters', $u['rfm']['segment']);
    }

    /**
     * Test Target Scores: 245 (At Risk VIP).
     */
    public function test_score_245_at_risk_vip(): void
    {
        $analysisDate = Carbon::parse('2026-10-23 10:00:00');

        $user = [
            'id' => 1,
            'name' => 'Isabel',
            'orders' => [
                ['payment_status' => 'paid', 'created_at' => '2026-09-10 10:00:00', 'total_amount' => 300000], // 43 days ago (31..60 => R=2)
                ['payment_status' => 'paid', 'created_at' => '2026-08-20 10:00:00', 'total_amount' => 300000],
                ['payment_status' => 'paid', 'created_at' => '2026-08-10 10:00:00', 'total_amount' => 300000],
                ['payment_status' => 'paid', 'created_at' => '2026-08-01 10:00:00', 'total_amount' => 300000],
                ['payment_status' => 'paid', 'created_at' => '2026-07-20 10:00:00', 'total_amount' => 300000], // 5 orders (5..9 => F=4), total 1,500,000 (>= 1,000,000 => M=5)
            ],
        ];

        $result = $this->service->analyzeUsers([$user], $analysisDate, ['force_method' => 'fixed_threshold']);
        $u = $result['users'][0];

        $this->assertEquals(2, $u['rfm']['score_r']);
        $this->assertEquals(4, $u['rfm']['score_f']);
        $this->assertEquals(5, $u['rfm']['score_m']);
        $this->assertEquals('245', $u['rfm']['rfm_code']);
        $this->assertEquals(11, $u['rfm']['rfm_total_score']);
        $this->assertEquals('At Risk VIP', $u['rfm']['segment']);
    }

    /**
     * Test Target Scores: 111 (Hibernating / Pasif).
     */
    public function test_score_111_hibernating(): void
    {
        $analysisDate = Carbon::parse('2026-10-23 10:00:00');

        $user = [
            'id' => 1,
            'name' => 'Jack',
            'orders' => [
                ['payment_status' => 'paid', 'created_at' => '2026-06-01 10:00:00', 'total_amount' => 45000], // > 60 days (R=1), 1 order (F=1), < 100k (M=1)
            ],
        ];

        $result = $this->service->analyzeUsers([$user], $analysisDate, ['force_method' => 'fixed_threshold']);
        $u = $result['users'][0];

        $this->assertEquals(1, $u['rfm']['score_r']);
        $this->assertEquals(1, $u['rfm']['score_f']);
        $this->assertEquals(1, $u['rfm']['score_m']);
        $this->assertEquals('111', $u['rfm']['rfm_code']);
        $this->assertEquals(3, $u['rfm']['rfm_total_score']);
        $this->assertEquals('Hibernating / Pasif', $u['rfm']['segment']);
    }

    /**
     * Test small dataset (< 5 customers) gracefully falls back to fixed threshold.
     */
    public function test_small_dataset_falls_back_to_fixed_threshold(): void
    {
        $analysisDate = Carbon::parse('2026-10-23 10:00:00');

        $users = [
            ['id' => 1, 'orders' => [['payment_status' => 'paid', 'created_at' => '2026-10-20 10:00:00', 'total_amount' => 50000]]],
            ['id' => 2, 'orders' => [['payment_status' => 'paid', 'created_at' => '2026-10-15 10:00:00', 'total_amount' => 70000]]],
        ];

        $result = $this->service->analyzeUsers($users, $analysisDate);

        $this->assertEquals('fixed_threshold', $result['scoring_method']);
    }

    /**
     * Test identical values cause fallback to fixed threshold and consistent scores.
     */
    public function test_identical_values_tie_handling(): void
    {
        $analysisDate = Carbon::parse('2026-10-23 10:00:00');

        // 6 users, but all have identical frequency = 1 and same monetary = 50,000
        $users = [];
        for ($i = 1; $i <= 6; $i++) {
            $users[] = [
                'id' => $i,
                'name' => "User {$i}",
                'orders' => [
                    ['payment_status' => 'paid', 'created_at' => '2026-10-20 10:00:00', 'total_amount' => 50000],
                ]
            ];
        }

        $result = $this->service->analyzeUsers($users, $analysisDate);

        // Because frequency and monetary have only 1 distinct value, quintiles cannot form without ties
        $this->assertEquals('fixed_threshold', $result['scoring_method']);

        // All users must receive identical scores
        foreach ($result['users'] as $u) {
            $this->assertEquals(5, $u['rfm']['score_r']); // 3 days ago <= 7
            $this->assertEquals(1, $u['rfm']['score_f']); // 1 order
            $this->assertEquals(1, $u['rfm']['score_m']); // 50k
            $this->assertEquals('511', $u['rfm']['rfm_code']);
        }
    }

    /**
     * Test Quintile scoring when dataset has >= 5 distinct values with strictly ascending cutoffs.
     */
    public function test_quintile_scoring_when_distribution_allows(): void
    {
        $analysisDate = Carbon::parse('2026-10-23 10:00:00');

        $users = [
            ['id' => 1, 'orders' => [['payment_status' => 'paid', 'created_at' => '2026-10-22 10:00:00', 'total_amount' => 10000]]],
            ['id' => 2, 'orders' => [
                ['payment_status' => 'paid', 'created_at' => '2026-10-20 10:00:00', 'total_amount' => 20000],
                ['payment_status' => 'paid', 'created_at' => '2026-10-19 10:00:00', 'total_amount' => 20000],
            ]],
            ['id' => 3, 'orders' => [
                ['payment_status' => 'paid', 'created_at' => '2026-10-15 10:00:00', 'total_amount' => 30000],
                ['payment_status' => 'paid', 'created_at' => '2026-10-14 10:00:00', 'total_amount' => 30000],
                ['payment_status' => 'paid', 'created_at' => '2026-10-13 10:00:00', 'total_amount' => 30000],
            ]],
            ['id' => 4, 'orders' => [
                ['payment_status' => 'paid', 'created_at' => '2026-10-05 10:00:00', 'total_amount' => 40000],
                ['payment_status' => 'paid', 'created_at' => '2026-10-04 10:00:00', 'total_amount' => 40000],
                ['payment_status' => 'paid', 'created_at' => '2026-10-03 10:00:00', 'total_amount' => 40000],
                ['payment_status' => 'paid', 'created_at' => '2026-10-02 10:00:00', 'total_amount' => 40000],
            ]],
            ['id' => 5, 'orders' => [
                ['payment_status' => 'paid', 'created_at' => '2026-09-01 10:00:00', 'total_amount' => 50000],
                ['payment_status' => 'paid', 'created_at' => '2026-08-30 10:00:00', 'total_amount' => 50000],
                ['payment_status' => 'paid', 'created_at' => '2026-08-25 10:00:00', 'total_amount' => 50000],
                ['payment_status' => 'paid', 'created_at' => '2026-08-20 10:00:00', 'total_amount' => 50000],
                ['payment_status' => 'paid', 'created_at' => '2026-08-15 10:00:00', 'total_amount' => 50000],
            ]],
        ];

        $result = $this->service->analyzeUsers($users, $analysisDate);

        $this->assertEquals('quintile', $result['scoring_method']);
        $this->assertEquals(5, count($result['users']));
    }

    /**
     * Test ALL 125 combinations (R: 1-5, F: 1-5, M: 1-5) produce valid deterministic segments in 8 quadrants.
     */
    public function test_all_125_score_combinations_covered(): void
    {
        $validSegments = [
            'Champions',
            'Loyal Repeaters',
            'Big Spenders (Baru)',
            'New / Recent Customers',
            'At Risk VIP',
            'At Risk Routine',
            'Previously Big Spenders',
            'Hibernating / Pasif',
        ];

        $counts = [];

        for ($r = 1; $r <= 5; $r++) {
            for ($f = 1; $f <= 5; $f++) {
                for ($m = 1; $m <= 5; $m++) {
                    $segmentData = $this->service->determineSegment($r, $f, $m);

                    $this->assertContains($segmentData['segment'], $validSegments, "Failed for R={$r}, F={$f}, M={$m}");
                    $this->assertNotEmpty($segmentData['segment_description']);
                    $this->assertNotEmpty($segmentData['crm_recommendation']);
                    $this->assertNotEmpty($segmentData['badge_color']);

                    $counts[$segmentData['segment']] = ($counts[$segmentData['segment']] ?? 0) + 1;
                }
            }
        }

        // Sum of all combinations must equal exactly 125
        $this->assertEquals(125, array_sum($counts));

        // Test mid-score (3) accumulation examples:
        // 353: R=3 (Low), F=5 (High), M=3 (High) -> At Risk VIP
        $this->assertEquals('At Risk VIP', $this->service->determineSegment(3, 5, 3)['segment']);

        // 335: R=3 (Low), F=3 (High), M=5 (High) -> At Risk VIP
        $this->assertEquals('At Risk VIP', $this->service->determineSegment(3, 3, 5)['segment']);

        // 333: R=3 (Low), F=3 (High), M=3 (High) -> At Risk VIP
        $this->assertEquals('At Risk VIP', $this->service->determineSegment(3, 3, 3)['segment']);

        // 323: R=3 (Low), F=2 (Low), M=3 (High) -> Previously Big Spenders
        $this->assertEquals('Previously Big Spenders', $this->service->determineSegment(3, 2, 3)['segment']);

        // 233: R=2 (Low), F=3 (High), M=3 (High) -> At Risk VIP
        $this->assertEquals('At Risk VIP', $this->service->determineSegment(2, 3, 3)['segment']);

        // 433: R=4 (High), F=3 (High), M=3 (High) -> Champions
        $this->assertEquals('Champions', $this->service->determineSegment(4, 3, 3)['segment']);
    }

    /**
     * Test KPI Dashboard Summary aggregation and consistency.
     */
    public function test_kpi_summary_consistency(): void
    {
        $analysisDate = Carbon::parse('2026-10-23 10:00:00');

        $users = [
            // User 1: Champion (R=5, F=10, M=1.5M)
            [
                'id' => 1,
                'name' => 'U1',
                'orders' => array_fill(0, 10, ['payment_status' => 'paid', 'created_at' => '2026-10-21 10:00:00', 'total_amount' => 150000]),
            ],
            // User 2: Belum Bertransaksi
            [
                'id' => 2,
                'name' => 'U2',
                'orders' => [],
            ],
            // User 3: Hibernating (R=1, F=1, M=50k)
            [
                'id' => 3,
                'name' => 'U3',
                'orders' => [
                    ['payment_status' => 'paid', 'created_at' => '2026-05-01 10:00:00', 'total_amount' => 50000],
                ],
            ]
        ];

        $result = $this->service->analyzeUsers($users, $analysisDate, ['force_method' => 'fixed_threshold']);
        $summary = $result['summary'];

        $this->assertEquals(3, $summary['total_customers']);
        $this->assertEquals(1550000.0, $summary['total_revenue']);
        $this->assertEquals(11, $summary['total_transactions']); // 10 from U1 + 1 from U3
        $this->assertEquals(1, $summary['active_customers']); // U1 (2 days ago <= 14)
        $this->assertEquals(1, $summary['champions_count']);
        $this->assertEquals(1, $summary['hibernating_count']);
        $this->assertEquals(1, $summary['unpurchased_customers_count']);
    }

    /**
     * Test analyzeSingleUser scores deterministically using fixed thresholds.
     */
    public function test_analyze_single_user(): void
    {
        $analysisDate = Carbon::parse('2026-10-23 10:00:00');

        $user = [
            'id' => 99,
            'name' => 'Single User Test',
            'orders' => [
                ['payment_status' => 'paid', 'created_at' => '2026-10-21 10:00:00', 'total_amount' => 500000],
                ['payment_status' => 'paid', 'created_at' => '2026-10-15 10:00:00', 'total_amount' => 500000],
            ],
        ];

        $res = $this->service->analyzeSingleUser($user, $analysisDate);

        $this->assertTrue($res['has_valid_orders']);
        $this->assertEquals(5, $res['score_r']); // 2 days ago <= 7
        $this->assertEquals(2, $res['score_f']); // 2 orders
        $this->assertEquals(5, $res['score_m']); // 1,000,000 spent >= 750,000
        $this->assertEquals('525', $res['rfm_code']);
        $this->assertNotEmpty($res['segment']);
    }
}

