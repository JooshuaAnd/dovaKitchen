<script setup>
import { computed, ref, watch, onUnmounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { 
    Mail, 
    Phone, 
    X, 
    Clock, 
    DollarSign, 
    ShoppingCart, 
    Check, 
    Copy, 
    Plus, 
    Trash2, 
    MessageCircle,
    User,
    CheckCircle2
} from 'lucide-vue-next';
import dayjs from 'dayjs';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    user: {
        type: Object,
        default: null,
    },
    voucherTemplates: {
        type: Array,
        default: () => [],
    }
});

const emit = defineEmits(['close', 'delete']);

const formatDate = (date) => {
    return date ? dayjs(date).format('DD MMM YYYY') : '-';
};

const formatDateTime = (date) => {
    return date ? dayjs(date).format('DD MMM YYYY, HH:mm') : '-';
};

const formatPrice = (price) => {
    if (!price && price !== 0) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(price);
};

// Lock background scroll when modal is open
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

onUnmounted(() => {
    document.body.style.overflow = '';
});

const close = () => {
    document.body.style.overflow = '';
    emit('close');
};

const confirmDelete = () => {
    document.body.style.overflow = '';
    emit('delete', props.user);
};

// 2-Tone Professional Badge (Black, Red, Neutral Gray)
const getSegmentBadgeClass = (segment) => {
    if (segment === 'At Risk VIP' || segment === 'At Risk Routine' || segment === 'Previously Big Spenders') {
        return 'bg-red-50 text-red-700 border border-red-300';
    }
    if (segment === 'Champions' || segment === 'Loyal Repeaters' || segment === 'Big Spenders (Baru)') {
        return 'bg-black text-white border border-black';
    }
    return 'bg-gray-100 text-gray-800 border border-gray-300';
};

// ============================================
// STATUS & PENGIRIMAN DISKON OTOMATIS CRM RFM
// ============================================
const copiedCode = ref(null);
const assigningTemplateId = ref(null);
const matchingTemplates = computed(() => props.voucherTemplates.filter(template =>
    template.rfm_segment === props.user?.rfm?.segment && new Date(template.valid_until) >= new Date()
));

const assignTemplate = (template) => {
    assigningTemplateId.value = template.id;
    router.post(route('admin.users.vouchers.assign', [props.user.id, template.id]), {}, {
        preserveScroll: true,
        onFinish: () => assigningTemplateId.value = null,
    });
};

const sendAutomaticDiscountWhatsApp = () => {
    if (!props.user?.phone) {
        alert('Nomor HP pelanggan belum diisi.');
        return;
    }

    let phone = props.user.phone.replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) {
        phone = '62' + phone.substring(1);
    }

    const preset = props.user?.rfm?.voucher_preset;
    const discountText = preset 
        ? (preset.discount_type === 'percentage' ? `${preset.discount_value}%` : formatPrice(preset.discount_value))
        : '15%';
    const minSpendText = preset ? formatPrice(preset.min_purchase) : 'Rp 50.000';
    const segmentName = props.user?.rfm?.segment || 'Pelanggan Setia';

    const text = `Halo Kak ${props.user.name}, terima kasih telah menjadi bagian dari Dova Kitchen! Berdasarkan apresiasi kami untuk kategori pelanggan ${segmentName}, Anda berhak mendapatkan *Diskon Otomatis ${discountText}* (minimal belanja ${minSpendText}). Diskon ini sudah langsung aktif di akun Kakak dan otomatis terpotong saat memesan di katalog katering kami. Silakan cek menu lezat minggu ini di website Dova Kitchen!`;

    window.open(`https://wa.me/${phone}?text=${encodeURIComponent(text)}`, '_blank');
};

const deleteVoucher = (voucher) => {
    if (confirm(`Hapus voucher ${voucher.code}?`)) {
        router.delete(route('admin.vouchers.destroy', voucher.id), {
            preserveScroll: true
        });
    }
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    copiedCode.value = text;
    setTimeout(() => {
        copiedCode.value = null;
    }, 2000);
};

const sendVoucherWhatsApp = (voucher) => {
    if (!props.user?.phone) {
        alert('Nomor HP pelanggan belum diisi.');
        return;
    }

    let phone = props.user.phone.replace(/[^0-9]/g, '');
    if (phone.startsWith('0')) {
        phone = '62' + phone.substring(1);
    }

    const discountText = voucher.discount_type === 'percentage' 
        ? `${Math.round(voucher.discount_value)}%` 
        : formatPrice(voucher.discount_value);

    const msg = `Halo ${props.user.name},\n\n` +
        `Dova Kitchen memberikan voucher khusus ${voucher.title} untuk Anda:\n` +
        `- Diskon: ${discountText}\n` +
        `- Kode Voucher: ${voucher.code}\n` +
        `- Masa Berlaku: s.d. ${formatDate(voucher.valid_until)}\n` +
        (voucher.min_purchase > 0 ? `- Minimal Belanja: ${formatPrice(voucher.min_purchase)}\n\n` : `\n`) +
        `Gunakan kode ini saat melakukan checkout di website Dova Kitchen. Terima kasih.`;

    window.open(`https://wa.me/${phone}?text=${encodeURIComponent(msg)}`, '_blank');
};
</script>

<template>
    <!-- Modal Backdrop (Fixed & Body Scroll Locked) -->
    <div 
        v-if="show" 
        class="fixed inset-0 z-50 overflow-y-auto bg-black/60 flex items-center justify-center p-3 sm:p-5"
        role="dialog" 
        aria-modal="true"
    >
        <!-- Modal Card: Clean, Flat, Professional 2-Tone (No Gradients, No Heavy Shadows) -->
        <div 
            class="relative w-full max-w-3xl bg-white border border-gray-300 rounded-xl overflow-hidden my-auto max-h-[90vh] flex flex-col"
            @click.stop
        >
            <!-- Header Bar -->
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between bg-white shrink-0">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-red-600 text-white flex items-center justify-center font-bold text-sm">
                        DK
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 tracking-tight">Detail dan Analisis Pelanggan</h2>
                        <p class="text-xs text-gray-500">Profil akun, metrik RFM, dan pengelolaan voucher CRM</p>
                    </div>
                </div>
                <button 
                    @click="close" 
                    class="p-1.5 rounded-lg text-gray-400 hover:text-black hover:bg-gray-100 transition-colors"
                    title="Tutup"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Scrollable Content Body -->
            <div v-if="user" class="p-5 overflow-y-auto space-y-4 text-xs text-gray-800">
                
                <!-- 1. Customer Info & RFM Status Box -->
                <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="text-base font-bold text-gray-900">{{ user.name }}</h3>
                                <span 
                                    class="px-2 py-0.5 rounded text-[11px] font-bold"
                                    :class="getSegmentBadgeClass(user.rfm?.segment)"
                                >
                                    {{ user.rfm?.segment || 'Belum Bertransaksi' }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 mt-1 text-gray-600">
                                <span class="flex items-center">
                                    <Mail class="w-3.5 h-3.5 mr-1 text-gray-400" /> {{ user.email }}
                                </span>
                                <span class="flex items-center">
                                    <Phone class="w-3.5 h-3.5 mr-1 text-gray-400" /> {{ user.phone || 'Nomor HP belum diisi' }}
                                </span>
                            </div>
                        </div>

                        <!-- 3-Digit Score Badge -->
                        <div class="sm:text-right shrink-0">
                            <div class="text-[11px] font-medium text-gray-500">Kode RFM:</div>
                            <div class="font-mono text-lg font-black text-gray-900 tracking-wider">
                                {{ user.rfm?.rfm_code || '-' }}
                            </div>
                            <div v-if="user.rfm?.score_r" class="text-[10px] text-gray-500 font-mono mt-0.5">
                                R:{{ user.rfm.score_r }} · F:{{ user.rfm.score_f }} · M:{{ user.rfm.score_m }} (Total: {{ user.rfm.rfm_total_score }}/15)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Characteristic & CRM Recommendation -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="p-3 border border-gray-200 rounded-lg bg-white">
                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">
                            Karakteristik Segmen
                        </div>
                        <p class="text-xs text-gray-800 leading-relaxed">
                            {{ user.rfm?.segment_description || 'Belum ada riwayat pesanan yang valid.' }}
                        </p>
                    </div>

                    <div class="p-3 border border-gray-200 rounded-lg bg-white">
                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1">
                            Rekomendasi Tindakan CRM
                        </div>
                        <p class="text-xs text-gray-900 font-semibold leading-relaxed">
                            {{ user.rfm?.crm_recommendation || 'Berikan penawaran diskon untuk transaksi pertama.' }}
                        </p>
                    </div>
                </div>

                <!-- 3. RFM Raw Metrics Flat Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Recency -->
                    <div class="p-3 border border-gray-200 rounded-lg bg-white">
                        <div class="flex items-center justify-between text-gray-500 font-bold text-[11px] mb-1">
                            <span>Recency (Terakhir Beli)</span>
                            <span v-if="user.rfm?.score_r" class="px-1.5 py-0.2 bg-gray-100 text-gray-800 rounded font-mono font-bold text-[10px]">
                                Skor {{ user.rfm.score_r }}
                            </span>
                        </div>
                        <div class="text-base font-bold text-gray-900">
                            {{ user.rfm?.recency_label || '-' }}
                        </div>
                        <div class="text-[11px] text-gray-500 mt-0.5">
                            {{ user.rfm?.recency_last_order_at ? formatDateTime(user.rfm.recency_last_order_at) : 'Belum pernah order' }}
                        </div>
                    </div>

                    <!-- Frequency -->
                    <div class="p-3 border border-gray-200 rounded-lg bg-white">
                        <div class="flex items-center justify-between text-gray-500 font-bold text-[11px] mb-1">
                            <span>Frequency (Jumlah Order)</span>
                            <span v-if="user.rfm?.score_f" class="px-1.5 py-0.2 bg-gray-100 text-gray-800 rounded font-mono font-bold text-[10px]">
                                Skor {{ user.rfm.score_f }}
                            </span>
                        </div>
                        <div class="text-base font-bold text-gray-900">
                            {{ user.rfm?.frequency_paid_orders || 0 }} Transaksi Lunas
                        </div>
                        <div class="text-[11px] text-gray-500 mt-0.5">
                            Dari {{ user.rfm?.total_orders || 0 }} total pesanan
                        </div>
                    </div>

                    <!-- Monetary -->
                    <div class="p-3 border border-gray-200 rounded-lg bg-white">
                        <div class="flex items-center justify-between text-gray-500 font-bold text-[11px] mb-1">
                            <span>Monetary (Total Belanja)</span>
                            <span v-if="user.rfm?.score_m" class="px-1.5 py-0.2 bg-gray-100 text-gray-800 rounded font-mono font-bold text-[10px]">
                                Skor {{ user.rfm.score_m }}
                            </span>
                        </div>
                        <div class="text-base font-bold text-gray-900">
                            {{ formatPrice(user.rfm?.monetary_total_spent) }}
                        </div>
                        <div class="text-[11px] text-gray-500 mt-0.5">
                            Rata-rata: {{ formatPrice(user.rfm?.monetary_avg_order) }} / order
                        </div>
                    </div>
                </div>

                <!-- 4. Automatic RFM CRM Discount Section -->
                <div class="border border-gray-200 rounded-lg p-4 bg-white space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-gray-200">
                        <div>
                            <h4 class="font-bold text-gray-900 text-xs">Status Diskon Otomatis CRM (Berdasarkan RFM)</h4>
                            <p class="text-[11px] text-gray-500">Diskon ditentukan dan aktif otomatis di akun pelanggan sesuai hasil kalkulasi segmen RFM tanpa perlu terbitkan manual</p>
                        </div>
                        <button 
                            type="button"
                            @click="sendAutomaticDiscountWhatsApp"
                            class="px-3 py-1.5 bg-neutral-900 hover:bg-black text-white text-xs font-bold rounded flex items-center justify-center space-x-1.5 transition-colors self-start sm:self-auto"
                        >
                            <MessageCircle class="w-3.5 h-3.5 text-white" />
                            <span>Kirim Info ke WhatsApp</span>
                        </button>
                    </div>

                    <!-- Active Automatic Segment Reward Card -->
                    <div v-if="user.rfm?.voucher_preset" class="p-3.5 bg-neutral-50 border border-neutral-300 rounded-lg space-y-3">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center space-x-2">
                                <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 bg-black text-white rounded">
                                    Segmen: {{ user.rfm.segment }}
                                </span>
                                <span class="text-[10px] font-bold px-2 py-0.5 bg-red-600 text-white rounded">
                                    Status: Aktif Otomatis di Akun
                                </span>
                            </div>
                            <span class="text-xs font-mono font-bold text-gray-700">
                                Pola: {{ user.rfm.pattern || '-' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1">
                            <div class="p-2.5 bg-white border border-neutral-200 rounded">
                                <div class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Besar Diskon Otomatis</div>
                                <div class="text-base font-extrabold text-red-600 mt-0.5">
                                    {{ user.rfm.voucher_preset.discount_type === 'percentage' ? `${user.rfm.voucher_preset.discount_value}%` : formatPrice(user.rfm.voucher_preset.discount_value) }}
                                </div>
                            </div>
                            <div class="p-2.5 bg-white border border-neutral-200 rounded">
                                <div class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Minimal Belanja</div>
                                <div class="text-base font-extrabold text-gray-900 mt-0.5">
                                    {{ formatPrice(user.rfm.voucher_preset.min_purchase) }}
                                </div>
                            </div>
                            <div class="p-2.5 bg-white border border-neutral-200 rounded">
                                <div class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Masa Rekomendasi</div>
                                <div class="text-base font-extrabold text-gray-900 mt-0.5">
                                    {{ user.rfm.voucher_preset.validity_days }} Hari
                                </div>
                            </div>
                        </div>

                        <div class="text-xs text-gray-700 bg-white p-2.5 border border-neutral-200 rounded space-y-1">
                            <div class="font-bold text-gray-900">Rekomendasi Tindakan CRM:</div>
                            <div>{{ user.rfm.crm_recommendation }}</div>
                            <div class="text-[11px] text-gray-500 pt-0.5">{{ user.rfm.voucher_preset.notes }}</div>
                        </div>
                    </div>

                    <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                        <div class="font-bold text-gray-900 text-xs">Terbitkan Voucher dari Template</div>
                        <p class="mt-0.5 text-[11px] text-gray-500">Hanya template untuk segmen {{ user.rfm?.segment || 'Belum Bertransaksi' }} yang ditampilkan.</p>
                        <div v-if="matchingTemplates.length" class="mt-2 space-y-2">
                            <div v-for="template in matchingTemplates" :key="template.id" class="flex items-center justify-between gap-2 rounded border border-gray-200 bg-white p-2">
                                <div class="min-w-0"><div class="font-semibold text-gray-900">{{ template.title }}</div><div class="text-[10px] text-gray-500">{{ template.discount_type === 'percentage' ? Math.round(template.discount_value) + '%' : formatPrice(template.discount_value) }} · min. {{ formatPrice(template.min_purchase) }}</div></div>
                                <button type="button" :disabled="assigningTemplateId === template.id" @click="assignTemplate(template)" class="shrink-0 rounded bg-black px-2.5 py-1.5 text-[11px] font-bold text-white disabled:opacity-50">{{ assigningTemplateId === template.id ? 'Memproses...' : 'Pilih Voucher' }}</button>
                            </div>
                        </div>
                        <p v-else class="mt-2 text-[11px] italic text-gray-400">Belum ada template voucher aktif untuk segmen ini.</p>
                    </div>

                    <!-- Voucher List Table / Cards -->
                    <div v-if="user.vouchers && user.vouchers.length > 0" class="space-y-2">
                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                            Daftar Voucher Pelanggan:
                        </div>
                        <div class="divide-y divide-gray-200 border border-gray-200 rounded-lg overflow-hidden bg-white">
                            <div 
                                v-for="v in user.vouchers" 
                                :key="v.id"
                                class="p-3 flex items-center justify-between gap-3 text-xs"
                            >
                                <div class="space-y-0.5">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-mono font-bold px-1.5 py-0.5 bg-gray-100 border border-gray-300 rounded text-gray-900">
                                            {{ v.code }}
                                        </span>
                                        <span 
                                            class="px-1.5 py-0.2 rounded text-[10px] font-bold"
                                            :class="{
                                                'bg-black text-white': !v.is_used && !v.is_expired,
                                                'bg-gray-200 text-gray-700': v.is_used,
                                                'bg-red-50 text-red-700 border border-red-200': v.is_expired && !v.is_used
                                            }"
                                        >
                                            {{ v.is_used ? 'Sudah Dipakai' : (v.is_expired ? 'Kedaluwarsa' : 'Aktif') }}
                                        </span>
                                    </div>
                                    <div class="font-medium text-gray-900">{{ v.title || 'Voucher CRM' }}</div>
                                    <div class="text-[11px] text-gray-500">
                                        Diskon: <strong class="text-gray-900">{{ v.discount_type === 'percentage' ? Math.round(v.discount_value) + '%' : formatPrice(v.discount_value) }}</strong>
                                        · Min. Belanja: {{ formatPrice(v.min_purchase) }}
                                        · Berlaku s.d. {{ formatDate(v.valid_until) }}
                                    </div>
                                </div>

                                <div class="flex items-center space-x-1.5 shrink-0">
                                    <button 
                                        type="button"
                                        @click="copyToClipboard(v.code)"
                                        class="p-1.5 border border-gray-300 rounded hover:bg-gray-100 text-gray-700"
                                        title="Salin Kode"
                                    >
                                        <Check v-if="copiedCode === v.code" class="w-3.5 h-3.5 text-black" />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                    </button>

                                    <button 
                                        v-if="!v.is_used && user.phone"
                                        type="button"
                                        @click="sendVoucherWhatsApp(v)"
                                        class="p-1.5 bg-red-600 hover:bg-red-700 text-white rounded"
                                        title="Kirim via WhatsApp"
                                    >
                                        <MessageCircle class="w-3.5 h-3.5" />
                                    </button>

                                    <button 
                                        v-if="!v.is_used"
                                        type="button"
                                        @click="deleteVoucher(v)"
                                        class="p-1.5 border border-red-300 text-red-600 hover:bg-red-50 rounded"
                                        title="Hapus Voucher"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-3 text-gray-400 italic text-[11px]">
                        Belum ada voucher khusus untuk pelanggan ini.
                    </div>
                </div>

                <!-- 5. Dietary / Allergens Profile -->
                <div class="p-3 border border-gray-200 rounded-lg bg-white">
                    <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">
                        Profil Pantangan / Alergi Pelanggan
                    </div>
                    <div v-if="user.allergens && user.allergens.length > 0" class="flex flex-wrap gap-1.5">
                        <span 
                            v-for="a in user.allergens" 
                            :key="a.id"
                            class="px-2 py-0.5 rounded bg-red-50 text-red-700 border border-red-200 text-xs font-semibold"
                        >
                            {{ a.name }}
                        </span>
                    </div>
                    <div v-else class="text-gray-500 text-xs">
                        Bebas Alergi (Tidak memiliki pantangan bahan makanan tercatat).
                    </div>
                </div>

            </div>

            <!-- Footer Bar -->
            <div class="px-5 py-3 border-t border-gray-200 bg-gray-50 flex items-center justify-between shrink-0">
                <button 
                    type="button"
                    @click="confirmDelete"
                    class="text-xs font-bold text-red-600 hover:text-red-800"
                >
                    Hapus Akun Pelanggan
                </button>

                <button 
                    type="button"
                    @click="close"
                    class="px-4 py-1.5 bg-black hover:bg-gray-800 text-white text-xs font-bold rounded transition-colors"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>
