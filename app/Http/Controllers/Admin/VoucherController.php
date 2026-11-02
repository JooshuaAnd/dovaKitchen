<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherTemplate;
use App\Services\RfmService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class VoucherController extends Controller
{
    private const RFM_SEGMENTS = [
        'Belum Bertransaksi',
        'Champions',
        'Loyal Repeaters',
        'Big Spenders (Baru)',
        'New / Recent Customers',
        'At Risk VIP',
        'At Risk Routine',
        'Previously Big Spenders',
        'Hibernating / Pasif',
    ];

    private const RFM_CODE_PREFIXES = [
        'Belum Bertransaksi' => 'PERDANA',
        'Champions' => 'CHAMP',
        'Loyal Repeaters' => 'LOYAL',
        'Big Spenders (Baru)' => 'SPEND',
        'New / Recent Customers' => 'WELCOME',
        'At Risk VIP' => 'VIPWIN',
        'At Risk Routine' => 'MISSYOU',
        'Previously Big Spenders' => 'COMEBACK',
        'Hibernating / Pasif' => 'WINBACK',
    ];

    public function __construct(private RfmService $rfmService)
    {
    }

    public function index(Request $request)
    {
        $selectedSegment = $request->query('rfm_segment');
        if ($selectedSegment !== null && !in_array($selectedSegment, self::RFM_SEGMENTS, true)) {
            abort(422, 'Kategori RFM tidak valid.');
        }

        return Inertia::render('Admin/Vouchers/Index', [
            'segments' => self::RFM_SEGMENTS,
            'selectedSegment' => $selectedSegment ?? '',
            'templates' => VoucherTemplate::query()
                ->when($selectedSegment, fn ($query) => $query->where('rfm_segment', $selectedSegment))
                ->latest()
                ->get(),
        ]);
    }

    public function storeSegment(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'rfm_segment' => ['required', 'string', Rule::in(self::RFM_SEGMENTS)],
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:1',
            'min_purchase' => 'nullable|numeric|min:0',
            'valid_until' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:500',
        ]);

        VoucherTemplate::create([
            ...$validated,
            'code_prefix' => self::RFM_CODE_PREFIXES[$validated['rfm_segment']],
            'min_purchase' => $validated['min_purchase'] ?? 0,
            'valid_until' => Carbon::parse($validated['valid_until'])->endOfDay(),
        ]);

        return back()->with('success', "Template voucher untuk segmen {$validated['rfm_segment']} berhasil dibuat.");
    }

    public function assignTemplate(User $user, VoucherTemplate $voucherTemplate)
    {
        abort_if($voucherTemplate->valid_until->isPast(), 422, 'Template voucher sudah kedaluwarsa.');

        $rfm = $this->rfmService->analyzeSingleUser($user->load('orders'), Carbon::now('Asia/Jakarta'));
        abort_unless($rfm['segment'] === $voucherTemplate->rfm_segment, 422, 'Voucher ini tidak sesuai dengan segmen RFM pelanggan.');

        Voucher::create([
            'user_id' => $user->id,
            'code' => $this->generateVoucherCode($voucherTemplate, $user),
            'discount_type' => $voucherTemplate->discount_type,
            'discount_value' => $voucherTemplate->discount_value,
            'min_purchase' => $voucherTemplate->min_purchase,
            'valid_until' => $voucherTemplate->valid_until,
            'rfm_segment' => $voucherTemplate->rfm_segment,
            'title' => $voucherTemplate->title,
            'notes' => $voucherTemplate->notes,
        ]);

        return back()->with('success', "Voucher {$voucherTemplate->title} berhasil diterbitkan untuk {$user->name}.");
    }
    /**
     * Issue a new personalized CRM voucher for a customer based on RFM insights.
     */
    public function store(Request $request, User $user)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:vouchers,code',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:1',
            'min_purchase' => 'nullable|numeric|min:0',
            'valid_until' => 'required|date',
            'rfm_segment' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $code = strtoupper(trim($validated['code']));

        $voucher = Voucher::create([
            'user_id' => $user->id,
            'code' => $code,
            'discount_type' => $validated['discount_type'],
            'discount_value' => (float) $validated['discount_value'],
            'min_purchase' => (float) ($validated['min_purchase'] ?? 0),
            'valid_until' => Carbon::parse($validated['valid_until'])->endOfDay(),
            'rfm_segment' => $validated['rfm_segment'] ?? null,
            'title' => $validated['title'] ?? ('Voucher CRM Segmen ' . ($validated['rfm_segment'] ?? 'Spesial')),
            'notes' => $validated['notes'] ?? null,
            'is_used' => false,
        ]);

        return redirect()->back()->with('success', "Voucher {$voucher->code} berhasil diterbitkan untuk {$user->name}!");
    }

    /**
     * Delete an unused voucher.
     */
    public function destroy(Voucher $voucher)
    {
        if ($voucher->is_used) {
            return redirect()->back()->with('error', 'Voucher yang sudah terpakai tidak dapat dihapus.');
        }

        $voucher->delete();

        return redirect()->back()->with('success', 'Voucher berhasil dibatalkan dan dihapus.');
    }

    public function destroyTemplate(VoucherTemplate $voucherTemplate)
    {
        $voucherTemplate->delete();

        return back()->with('success', 'Template voucher berhasil dihapus.');
    }

    private function generateVoucherCode(VoucherTemplate $template, User $user): string
    {
        do {
            $code = "{$template->code_prefix}-{$user->id}-" . Str::upper(Str::random(6));
        } while (Voucher::where('code', $code)->exists());

        return $code;
    }
}
