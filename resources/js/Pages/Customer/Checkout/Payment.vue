<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { UploadCloud, CheckCircle, Ticket } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
    vouchers: {
        type: Array,
        default: () => [],
    }
});

const form = useForm({
    payment_proof: null,
});

const voucherForm = useForm({ voucher_code: '' });

const previewUrl = ref(null);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.payment_proof = file;
        previewUrl.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('payment-proof.store', props.order.id), {
        preserveScroll: true,
    });
};

const applyVoucher = () => {
    voucherForm.put(route('orders.voucher.apply', props.order.id), { preserveScroll: true });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(price);
};
</script>

<template>
    <Head title="Verifikasi Pembayaran" />

    <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto space-y-8">
            
            <div v-if="order.payment_status === 'awaiting_verification'" class="bg-blue-50 border border-blue-200 text-blue-800 rounded-xl p-4 flex items-center shadow-sm">
                <CheckCircle class="w-6 h-6 mr-3 text-blue-600" />
                <div>
                    <h4 class="font-bold">Bukti Pembayaran Diunggah</h4>
                    <p class="text-sm">Kami sedang memverifikasi pembayaran Anda. Pesanan Anda akan segera dikonfirmasi.</p>
                </div>
            </div>

            <!-- Header -->
            <div class="bg-white shadow-sm rounded-2xl p-6 md:p-8 border border-gray-100">
                <h2 class="text-2xl font-black text-gray-900 mb-2">Detail Pembayaran</h2>
                <p class="text-gray-500">ID Pesanan: <span class="font-mono font-bold text-gray-900">{{ order.order_code }}</span></p>
                
                <div class="mt-6 border-t border-gray-100 pt-4 space-y-2 text-sm">
                    <div v-if="order.discount_amount > 0" class="flex justify-between items-center text-gray-600">
                        <span>Subtotal Tagihan</span>
                        <span class="font-bold text-gray-900">{{ formatPrice(order.subtotal_amount || (parseFloat(order.total_amount) + parseFloat(order.discount_amount))) }}</span>
                    </div>
                    
                    <div v-if="order.discount_amount > 0" class="flex justify-between items-center text-red-600 font-bold bg-red-50 p-2.5 rounded-lg">
                        <span>{{ order.discount_type || 'Diskon Ulang Tahun' }}</span>
                        <span>-{{ formatPrice(order.discount_amount) }}</span>
                    </div>

                    <div class="flex justify-between items-end pt-2 border-t border-gray-100">
                        <span class="text-gray-700 font-bold text-base">Total Tagihan Yang Harus Ditransfer</span>
                        <span class="text-3xl font-black text-red-600">{{ formatPrice(order.total_amount) }}</span>
                    </div>
                </div>
            </div>

            <div v-if="vouchers.length > 0 && order.discount_amount == 0 && order.payment_status === 'pending_payment'" class="rounded-2xl border border-indigo-200 bg-indigo-50 p-6 shadow-sm">
                <div class="flex items-start gap-3">
                    <Ticket class="mt-0.5 h-6 w-6 shrink-0 text-indigo-600" />
                    <div class="flex-1">
                        <h3 class="font-bold text-indigo-950">Anda memiliki voucher yang bisa digunakan</h3>
                        <p class="mt-1 text-sm text-indigo-800">Pilih voucher untuk menghitung ulang total pembayaran.</p>
                        <form @submit.prevent="applyVoucher" class="mt-4 flex flex-col gap-2 sm:flex-row">
                            <select v-model="voucherForm.voucher_code" class="min-w-0 flex-1 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-sm font-medium text-gray-900">
                                <option value="">Pilih voucher</option>
                                <option v-for="voucher in vouchers" :key="voucher.id" :value="voucher.code">
                                    {{ voucher.code }} — {{ voucher.formatted_discount }} (min. {{ formatPrice(voucher.min_purchase) }})
                                </option>
                            </select>
                            <button :disabled="!voucherForm.voucher_code || voucherForm.processing" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white disabled:opacity-50">Gunakan</button>
                        </form>
                        <p v-if="voucherForm.errors.voucher_code" class="mt-2 text-xs font-medium text-red-600">{{ voucherForm.errors.voucher_code }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Bank Info -->
                <div class="bg-white shadow-sm rounded-2xl p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Detail Transfer Bank</h3>
                    <p class="text-sm text-gray-600 mb-6">Silakan transfer sejumlah nominal di atas ke rekening bank berikut:</p>
                    
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 space-y-3">
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Nama Bank</p>
                            <p class="font-bold text-gray-900">BCA (Bank Central Asia)</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Nomor Rekening</p>
                            <p class="font-mono font-bold text-xl text-blue-600">8930 1234 5678</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Nama Rekening</p>
                            <p class="font-bold text-gray-900">PT Dova Kitchen Catering</p>
                        </div>
                    </div>
                </div>

                <!-- Upload Section -->
                <div class="bg-white shadow-sm rounded-2xl p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Unggah Bukti Pembayaran</h3>
                    
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="relative border-2 border-dashed border-gray-300 rounded-xl p-6 hover:bg-gray-50 transition-colors text-center" :class="{ 'border-blue-400 bg-blue-50': previewUrl }">
                            <input 
                                type="file" 
                                @change="handleFileChange" 
                                accept="image/*" 
                                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                :disabled="order.payment_status === 'awaiting_verification' || order.payment_status === 'paid'"
                            />
                            
                            <div v-if="previewUrl" class="space-y-3">
                                <img :src="previewUrl" class="max-h-40 mx-auto rounded-lg shadow-sm" />
                                <p class="text-sm font-medium text-blue-600">File dipilih</p>
                            </div>
                            <div v-else class="space-y-2">
                                <UploadCloud class="w-10 h-10 text-gray-400 mx-auto" />
                                <p class="text-sm font-semibold text-gray-700">Klik atau seret gambar ke sini</p>
                                <p class="text-xs text-gray-500">PNG, JPG up to 2MB</p>
                            </div>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="!form.payment_proof || form.processing || order.payment_status === 'awaiting_verification' || order.payment_status === 'paid'"
                            class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-xl shadow-sm hover:bg-blue-700 disabled:opacity-50 transition-colors"
                        >
                            {{ form.processing ? 'Mengunggah...' : 'Kirim Bukti Pembayaran' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
