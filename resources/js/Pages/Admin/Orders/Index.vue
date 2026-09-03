<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { CheckCircle, XCircle, Search, ExternalLink } from 'lucide-vue-next';
import dayjs from 'dayjs';

defineProps({
    orders: Array
});

const form = useForm({
    status: ''
});

const verifyOrder = (id, status) => {
    if (confirm(`Apakah Anda yakin ingin mengubah status pesanan ini menjadi ${status.toUpperCase()}?`)) {
        form.status = status;
        form.put(route('admin.orders.verify', id), {
            preserveScroll: true
        });
    }
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(price);
};

const formatDate = (date) => {
    return dayjs(date).format('DD MMM YYYY, HH:mm');
};

const getStatusBadge = (status) => {
    const states = {
        'pending_payment': { bg: 'bg-yellow-100', text: 'text-yellow-800', label: 'Menunggu Pembayaran' },
        'awaiting_verification': { bg: 'bg-blue-100', text: 'text-blue-800', label: 'Menunggu Verifikasi' },
        'paid': { bg: 'bg-green-100', text: 'text-green-800', label: 'Dibayar & Diproses' },
        'rejected': { bg: 'bg-red-100', text: 'text-red-800', label: 'Ditolak' },
    };
    return states[status] || { bg: 'bg-gray-100', text: 'text-gray-800', label: status };
};
</script>

<template>
    <Head title="Verifikasi Pesanan" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-3xl font-black text-gray-900">Verifikasi Pesanan</h1>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Info Pesanan</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kustomer</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Bayar</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bukti Pembayaran</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ order.order_code }}</div>
                                    <div class="text-xs text-gray-500">{{ formatDate(order.created_at) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900">{{ order.user.name }}</div>
                                    <div class="text-xs text-gray-500">{{ order.user.email }}</div>
                                    <div v-if="order.user.phone" class="text-xs text-blue-600 font-semibold mt-0.5">📞 {{ order.user.phone }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="font-black text-gray-900">{{ formatPrice(order.total_amount) }}</div>
                                    <div v-if="order.discount_amount > 0" class="text-xs text-red-600 font-medium">
                                        Hemat: -{{ formatPrice(order.discount_amount) }} ({{ order.discount_type }})
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a v-if="order.payment_proof_path" :href="order.payment_proof_path" target="_blank" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-900">
                                        <ExternalLink class="w-4 h-4 mr-1" /> Lihat Bukti
                                    </a>
                                    <span v-else class="text-sm text-gray-400 italic">Tidak ada bukti</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-2 py-1 inline-flex text-xs leading-5 font-bold rounded-full uppercase tracking-wider', getStatusBadge(order.payment_status).bg, getStatusBadge(order.payment_status).text]">
                                        {{ getStatusBadge(order.payment_status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div v-if="order.payment_status === 'awaiting_verification'" class="flex items-center justify-end space-x-2">
                                        <button @click="verifyOrder(order.id, 'paid')" class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-700 hover:bg-green-200 rounded-md transition-colors" title="Verifikasi">
                                            <CheckCircle class="w-4 h-4 mr-1" /> Verifikasi
                                        </button>
                                        <button @click="verifyOrder(order.id, 'rejected')" class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-700 hover:bg-red-200 rounded-md transition-colors" title="Tolak">
                                            <XCircle class="w-4 h-4 mr-1" /> Tolak
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 italic">Tidak ada aksi yang diperlukan</span>
                                </td>
                            </tr>
                            <tr v-if="orders.length === 0">
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    Belum ada pesanan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
