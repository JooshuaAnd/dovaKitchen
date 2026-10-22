<script setup>
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';
import { FileText, ArrowRight, CheckCircle } from 'lucide-vue-next';
import dayjs from 'dayjs';

defineProps({
    orders: Array
});

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
    <Head title="Riwayat Pesanan" />

    <CustomerLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <!-- Flash Notification -->
            <div v-if="$page.props.flash?.success" class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-4 flex items-center shadow-xs">
                <CheckCircle class="w-5 h-5 mr-3 text-emerald-600 flex-shrink-0" />
                <span class="text-sm font-semibold">{{ $page.props.flash.success }}</span>
            </div>

            <h1 class="text-3xl font-black text-gray-900 mb-8">Riwayat Pesanan</h1>

            <div v-if="orders.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-dashed border-gray-300">
                <FileText class="w-12 h-12 text-gray-300 mb-4" />
                <p class="text-xl font-bold text-gray-900 mb-2">Belum ada pesanan</p>
                <p class="text-gray-500 mb-6">Sepertinya Anda belum membuat pesanan katering apa pun.</p>
                <Link :href="route('catalog')" class="bg-red-600 text-white px-6 py-3 rounded-xl font-bold shadow-sm hover:bg-red-700 transition-colors">
                    Lihat Menu
                </Link>
            </div>

            <div v-else class="space-y-6">
                <div v-for="order in orders" :key="order.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Order Header -->
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-500 font-medium mb-1">ID Pesanan: <span class="text-gray-900 font-bold">{{ order.order_code }}</span></p>
                            <p class="text-xs text-gray-400">{{ formatDate(order.created_at) }}</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div :class="['px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider', getStatusBadge(order.payment_status).bg, getStatusBadge(order.payment_status).text]">
                                {{ getStatusBadge(order.payment_status).label }}
                            </div>
                            <h3 class="text-xl font-black text-gray-900">{{ formatPrice(order.total_amount) }}</h3>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="px-6 py-5">
                        <ul class="divide-y divide-gray-100">
                            <li v-for="item in order.order_items" :key="item.id" class="py-3 flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ item.menu.name }}</p>
                                    <p class="text-xs text-gray-500">{{ item.menu.service_day }} &bull; {{ formatDate(item.menu.service_date) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-900 font-medium">{{ item.portion_quantity }} x {{ formatPrice(item.unit_price) }}</p>
                                </div>
                            </li>
                        </ul>

                        <div v-if="order.discount_amount > 0" class="mt-4 pt-3 border-t border-gray-100 flex justify-between items-center text-xs font-bold text-red-600 bg-red-50 p-2.5 rounded-lg">
                            <span>{{ order.discount_type || 'Diskon Ulang Tahun (20%)' }}</span>
                            <span>-{{ formatPrice(order.discount_amount) }}</span>
                        </div>
                    </div>
                    
                    <!-- Order Action -->
                    <div v-if="order.payment_status === 'pending_payment'" class="px-6 py-4 border-t border-gray-100 bg-gray-50 text-right">
                        <Link :href="route('orders.show', order.id)" class="inline-flex items-center space-x-2 text-sm font-bold text-blue-600 hover:text-blue-700">
                            <span>Unggah Bukti Pembayaran</span>
                            <ArrowRight class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
