<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { 
    ReceiptText,
    CheckCircle2, 
    XCircle, 
    Search, 
    ExternalLink, 
    Eye, 
    ChevronLeft, 
    ChevronRight, 
    Clock, 
    MessageCircle, 
    AlertTriangle,
    Image as ImageIcon,
    X,
    Filter,
    Calendar,
    Utensils,
    Check,
    CreditCard
} from 'lucide-vue-next';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

dayjs.locale('id');

const props = defineProps({
    orders: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    status: ''
});

// Filters & Pagination State
const searchQuery = ref('');
const statusFilter = ref('all');
const perPage = ref(10);
const currentPage = ref(1);

// Formatting & URL helpers
const getProofUrl = (path) => {
    if (!path) return '';
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }
    if (path.startsWith('/storage/')) {
        return path;
    }
    return '/storage/' + path.replace(/^\/+/, '');
};

const openProofModal = (proofPath, orderCode) => {
    const url = getProofUrl(proofPath);
    Swal.fire({
        title: `<div class="text-base font-black text-gray-900 pb-2 border-b border-gray-200 text-left">Bukti Pembayaran #${orderCode}</div>`,
        imageUrl: url,
        imageAlt: 'Bukti Transfer Pembayaran',
        imageHeight: 400,
        showCloseButton: true,
        showCancelButton: true,
        cancelButtonText: 'Tutup',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Buka Ukuran Penuh ↗',
        confirmButtonColor: '#111827',
        customClass: {
            popup: 'rounded-2xl border border-gray-300 shadow-2xl p-5',
            title: 'p-0 m-0',
            image: 'rounded-xl object-contain border border-gray-200 my-3 bg-gray-50 max-h-[420px]',
            confirmButton: 'rounded-xl px-4 py-2 font-bold text-xs sm:text-sm',
            cancelButton: 'rounded-xl px-4 py-2 font-bold text-xs sm:text-sm'
        }
    }).then((res) => {
        if (res.isConfirmed) {
            window.open(url, '_blank');
        }
    });
};

// Detail Modal State & Scroll Lock
const isDetailModalOpen = ref(false);
const selectedOrder = ref(null);

const openOrderDetailModal = (order) => {
    selectedOrder.value = order;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    setTimeout(() => {
        if (!isDetailModalOpen.value) {
            selectedOrder.value = null;
        }
    }, 200);
};

// Lock body scroll when detail modal is open to prevent background scrolling
watch(isDetailModalOpen, (isOpen) => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }
});

onUnmounted(() => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});

const approveOrder = (order, closeModal = false) => {
    form.status = 'paid';
    form.put(route('admin.orders.verify', order.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (closeModal) closeDetailModal();
        },
    });
};

const handleApproveFromModal = () => {
    if (selectedOrder.value) approveOrder(selectedOrder.value, true);
};

// Formatting helpers
const formatPrice = (price) => {
    if (!price && price !== 0) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(price);
};

const formatDate = (date) => {
    if (!date) return '-';
    return dayjs(date).format('DD MMM YYYY, HH:mm');
};

const formatServiceDate = (date) => {
    if (!date) return '-';
    return dayjs(date).format('dddd, DD MMM YYYY');
};

const getWhatsAppUrl = (phone, customerName, orderCode) => {
    if (!phone) return null;
    let cleaned = phone.replace(/[^0-9]/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.substring(1);
    }
    const message = encodeURIComponent(`Halo Kak ${customerName}, kami dari Dova Kitchen terkait verifikasi pesanan katering #${orderCode}.`);
    return `https://wa.me/${cleaned}?text=${message}`;
};

// Status definitions & styling tokens matching the admin theme
const statusMeta = {
    'all': { label: 'Semua Status' },
    'awaiting_verification': {
        label: 'Perlu Validasi',
        badgeBg: 'bg-amber-50 text-amber-800 border-amber-200',
        dot: 'bg-amber-500',
        description: 'Bukti transfer sudah diunggah dan menunggu persetujuan admin.'
    },
    'paid': {
        label: 'Disetujui & Diproses',
        badgeBg: 'bg-emerald-50 text-emerald-800 border-emerald-200',
        dot: 'bg-emerald-600',
        description: 'Pembayaran telah disetujui admin dan pesanan siap diproses dapur.'
    },
    'rejected': {
        label: 'Ditolak',
        badgeBg: 'bg-rose-50 text-rose-800 border-rose-200',
        dot: 'bg-rose-500',
        description: 'Bukti pembayaran tidak valid atau pesanan dibatalkan.'
    }
};

const getStatusBadge = (status) => {
    return statusMeta[status] || {
        label: status,
        badgeBg: 'bg-gray-100 text-gray-800 border-gray-300',
        dot: 'bg-gray-500'
    };
};



// Calculate total portions for an order
const getOrderTotalPortions = (order) => {
    if (!order.order_items || order.order_items.length === 0) return 0;
    return order.order_items.reduce((acc, item) => acc + (Number(item.portion_quantity) || 0), 0);
};

// Filtered Orders
const filteredOrders = computed(() => {
    let result = [...props.orders];

    // Status filter
    if (statusFilter.value !== 'all') {
        result = result.filter(order => order.payment_status === statusFilter.value);
    }

    // Search query
    if (searchQuery.value.trim() !== '') {
        const q = searchQuery.value.toLowerCase().trim();
        result = result.filter(order => {
            const codeMatch = order.order_code?.toLowerCase().includes(q);
            const nameMatch = order.user?.name?.toLowerCase().includes(q);
            const emailMatch = order.user?.email?.toLowerCase().includes(q);
            const phoneMatch = order.user?.phone?.toLowerCase().includes(q);
            const itemsMatch = order.order_items?.some(it => it.menu?.name?.toLowerCase().includes(q));
            return codeMatch || nameMatch || emailMatch || phoneMatch || itemsMatch;
        });
    }

    return result;
});

// Paginated Orders
const totalPages = computed(() => {
    if (perPage.value === 'all') return 1;
    return Math.max(1, Math.ceil(filteredOrders.value.length / Number(perPage.value)));
});

const paginatedOrders = computed(() => {
    if (perPage.value === 'all') return filteredOrders.value;
    const limit = Number(perPage.value);
    const start = (currentPage.value - 1) * limit;
    return filteredOrders.value.slice(start, start + limit);
});

const startItemIndex = computed(() => {
    if (filteredOrders.value.length === 0) return 0;
    if (perPage.value === 'all') return 1;
    return (currentPage.value - 1) * Number(perPage.value) + 1;
});

const endItemIndex = computed(() => {
    if (perPage.value === 'all') return filteredOrders.value.length;
    return Math.min(currentPage.value * Number(perPage.value), filteredOrders.value.length);
});

// Approval action with SweetAlert2
const confirmVerifyOrder = (order) => {
    Swal.fire({
        title: 'Setujui Pesanan?',
        html: `
            <div class="text-left text-xs sm:text-sm space-y-2 text-gray-700">
                <p>Apakah Anda yakin ingin menyetujui pesanan <strong class="text-gray-900 font-mono">#${order.order_code}</strong>?</p>
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-200 mt-2 text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Pemesan:</span>
                        <span class="font-bold text-gray-900">${order.user?.name || '-'}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Total Tagihan:</span>
                        <span class="font-bold text-gray-900">${formatPrice(order.total_amount)}</span>
                    </div>
                </div>
            </div>
        `,
        icon: 'question',
        iconColor: '#111827',
        showCancelButton: true,
        confirmButtonColor: '#111827',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Setujui',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-2xl border border-gray-300 shadow-xl',
            confirmButton: 'rounded-xl px-5 py-2.5 font-bold text-xs sm:text-sm',
            cancelButton: 'rounded-xl px-5 py-2.5 font-bold text-xs sm:text-sm'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            form.status = 'paid';
            form.put(route('admin.orders.verify', order.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Pesanan Disetujui!',
                        text: `Pesanan #${order.order_code} telah disetujui dan siap diproses.`,
                        icon: 'success',
                        iconColor: '#111827',
                        confirmButtonColor: '#111827',
                        customClass: {
                            popup: 'rounded-2xl border border-gray-300 shadow-xl',
                            confirmButton: 'rounded-xl px-5 py-2.5 font-bold text-sm'
                        }
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Verifikasi Pesanan" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <div class="flex items-center space-x-3 mb-1">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 border border-gray-300 flex items-center justify-center text-gray-800 shadow-2xs">
                            <ReceiptText class="w-5 h-5" />
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">
                            Verifikasi Pesanan
                        </h1>
                    </div>
                    <p class="text-xs text-gray-500 font-medium">
                        Verifikasi bukti pembayaran transfer bank pesanan katering dan pantau status transaksi pelanggan.
                    </p>
                </div>
            </div>



            <!-- Toolbar: Per Page, Filter Status, and Search Bar -->
            <div class="bg-white p-4 rounded-xl border border-gray-300 shadow-2xs mb-6">
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                    
                    <!-- Left: Per Page Selector -->
                    <div class="flex items-center space-x-2 text-xs text-gray-600 font-medium">
                        <span>Tampilkan</span>
                        <select 
                            v-model="perPage" 
                            @change="currentPage = 1"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg px-2.5 py-1.5 focus:ring-1 focus:ring-gray-900 focus:border-gray-900 font-bold"
                        >
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option value="all">Semua</option>
                        </select>
                        <span>data per halaman</span>
                    </div>

                    <!-- Right: Search & Status Filter -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        
                        <!-- Status Filter Dropdown -->
                        <div class="relative min-w-[170px]">
                            <select 
                                v-model="statusFilter"
                                @change="currentPage = 1"
                                class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-xs rounded-xl px-3 py-2 font-medium focus:ring-1 focus:ring-gray-900 focus:border-gray-900"
                            >
                                <option value="all">Semua Status</option>
                                <option value="awaiting_verification">Perlu Validasi</option>
                                <option value="paid">Disetujui & Diproses</option>
                            </select>
                        </div>

                        <!-- Search Input -->
                        <div class="relative min-w-[240px] sm:w-72">
                            <input 
                                v-model="searchQuery"
                                @input="currentPage = 1"
                                type="text"
                                placeholder="Cari kode order, nama, no HP..."
                                class="w-full pl-9 pr-8 py-2 bg-gray-50 border border-gray-300 rounded-xl text-xs text-gray-900 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-gray-900 focus:border-gray-900 transition-all font-medium"
                            />
                            <Search class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" />
                            <button 
                                v-if="searchQuery" 
                                @click="searchQuery = ''; currentPage = 1"
                                class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 text-xs font-bold"
                            >
                                ✕
                            </button>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Table Card Container Matching Users & Menus Architecture -->
            <div class="bg-white rounded-xl shadow-2xs border border-gray-300 overflow-hidden">
                <!-- Top Counter Bar -->
                <div class="px-5 py-3.5 bg-gray-50/50 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between text-xs sm:text-sm text-gray-600 gap-2 font-medium">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Daftar Verifikasi Pesanan</span>
                    </div>

                    <div>
                        Menampilkan 
                        <span class="font-bold text-gray-900">{{ startItemIndex }} – {{ endItemIndex }}</span> 
                        dari 
                        <span class="font-bold text-gray-900">{{ filteredOrders.length }}</span> 
                        total pesanan
                    </div>
                </div>

                <!-- Structured Table with Clean Grid Lines -->
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-left">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-300 text-xs font-bold text-gray-700 select-none">
                                <th scope="col" class="py-3.5 px-3 text-center border-r border-gray-200 w-12 text-[11px] uppercase tracking-wider">
                                    No
                                </th>
                                <th scope="col" class="py-3.5 px-4 border-r border-gray-200 min-w-[170px] text-[11px] uppercase tracking-wider">
                                    Info Pesanan
                                </th>
                                <th scope="col" class="py-3.5 px-4 border-r border-gray-200 min-w-[210px] text-[11px] uppercase tracking-wider">
                                    Pelanggan
                                </th>
                                <th scope="col" class="py-3.5 px-4 border-r border-gray-200 min-w-[200px] text-[11px] uppercase tracking-wider">
                                    Menu Dipesan
                                </th>
                                <th scope="col" class="py-3.5 px-4 border-r border-gray-200 min-w-[150px] text-[11px] uppercase tracking-wider">
                                    Total Tagihan
                                </th>
                                <th scope="col" class="py-3.5 px-4 text-center border-r border-gray-200 min-w-[130px] text-[11px] uppercase tracking-wider">
                                    Bukti Transfer
                                </th>
                                <th scope="col" class="py-3.5 px-4 border-r border-gray-200 min-w-[160px] text-[11px] uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="py-3.5 px-4 text-center min-w-[170px] text-[11px] uppercase tracking-wider">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-xs sm:text-sm text-gray-800 bg-white">
                            <tr 
                                v-for="(order, idx) in paginatedOrders" 
                                :key="order.id" 
                                class="hover:bg-slate-50/80 transition-colors group"
                            >
                                <!-- No -->
                                <td class="py-3.5 px-3 text-center border-r border-gray-200 font-medium text-gray-500">
                                    {{ (currentPage - 1) * (perPage === 'all' ? 0 : Number(perPage)) + idx + 1 }}
                                </td>

                                <!-- Info Pesanan (Order Code, Created At, Portion count) -->
                                <td class="py-3.5 px-4 border-r border-gray-200">
                                    <div class="flex items-center space-x-1.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-gray-100 text-gray-800 border border-gray-300">
                                            #{{ order.order_code }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1 flex items-center">
                                        <Clock class="w-3.5 h-3.5 mr-1 text-gray-400 shrink-0" />
                                        <span>{{ formatDate(order.created_at) }}</span>
                                    </div>
                                    <div class="text-[11px] text-gray-400 font-medium mt-0.5">
                                        {{ getOrderTotalPortions(order) }} total porsi
                                    </div>
                                </td>

                                <!-- Customer Details (Name, Phone + WA, Email, Allergens) -->
                                <td class="py-3.5 px-4 border-r border-gray-200">
                                    <div class="flex items-start space-x-2.5">
                                        <div class="w-8 h-8 rounded-lg bg-gray-200 flex items-center justify-center text-gray-700 font-bold text-xs shrink-0 mt-0.5">
                                            {{ (order.user?.name || 'P').charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center space-x-1.5 flex-wrap gap-y-1">
                                                <span class="font-bold text-sm text-gray-900 group-hover:text-black">
                                                    {{ order.user?.name || 'Pelanggan' }}
                                                </span>
                                                <span 
                                                    v-if="order.user?.allergens && order.user.allergens.length > 0" 
                                                    class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200"
                                                    title="Memiliki catatan alergi"
                                                >
                                                    <AlertTriangle class="w-3 h-3 mr-0.5 text-red-600" />
                                                    Alergi
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-400 truncate mt-0.5">
                                                {{ order.user?.email || '-' }}
                                            </div>
                                            <!-- Phone + WA Quick Action -->
                                            <div class="mt-1 flex items-center space-x-2">
                                                <span class="text-xs font-medium text-gray-600 font-mono">
                                                    {{ order.user?.phone || '-' }}
                                                </span>
                                                <a 
                                                    v-if="order.user?.phone" 
                                                    :href="getWhatsAppUrl(order.user.phone, order.user.name, order.order_code)" 
                                                    target="_blank" 
                                                    rel="noopener noreferrer" 
                                                    @click.stop
                                                    class="inline-flex items-center px-1.5 py-0.5 bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 rounded text-[10px] font-semibold transition-all shadow-2xs" 
                                                    title="Hubungi via WhatsApp"
                                                >
                                                    <MessageCircle class="w-3 h-3 mr-1 text-gray-600" /> WA
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Order Items Summary -->
                                <td class="py-3.5 px-4 border-r border-gray-200">
                                    <div v-if="order.order_items && order.order_items.length > 0" class="space-y-1">
                                        <div 
                                            v-for="(item, itemIdx) in order.order_items.slice(0, 2)" 
                                            :key="item.id || itemIdx"
                                            class="text-xs text-gray-800 flex items-center justify-between"
                                        >
                                            <span class="font-medium truncate max-w-[130px]" :title="item.menu?.name">
                                                {{ item.menu?.name || 'Menu Katering' }}
                                            </span>
                                            <span class="font-bold text-gray-900 bg-gray-100 px-1.5 py-0.2 rounded text-[11px] shrink-0 border border-gray-200">
                                                {{ item.portion_quantity }}x
                                            </span>
                                        </div>
                                        <button 
                                            v-if="order.order_items.length > 2"
                                            @click="openOrderDetailModal(order)"
                                            type="button"
                                            class="text-[11px] text-gray-500 hover:text-gray-900 font-bold hover:underline block cursor-pointer"
                                        >
                                            + {{ order.order_items.length - 2 }} menu lainnya...
                                        </button>
                                    </div>
                                    <div v-else class="text-xs text-gray-400 italic">
                                        Tidak ada item rincian
                                    </div>
                                </td>

                                <!-- Total Bayar & Diskon -->
                                <td class="py-3.5 px-4 border-r border-gray-200 whitespace-nowrap">
                                    <div class="font-black text-sm text-gray-900">
                                        {{ formatPrice(order.total_amount) }}
                                    </div>
                                    <div v-if="order.discount_amount > 0" class="text-[11px] text-rose-600 font-medium mt-0.5">
                                        Hemat -{{ formatPrice(order.discount_amount) }}
                                    </div>
                                    <div v-else class="text-[11px] text-gray-400 mt-0.5">
                                        Tanpa diskon
                                    </div>
                                </td>

                                <!-- Bukti Transfer / Payment Proof -->
                                <td class="py-3.5 px-4 text-center border-r border-gray-200 whitespace-nowrap">
                                    <div v-if="order.payment_proof_path || order.payment_proof_url" class="flex flex-col items-center justify-center space-y-1.5">
                                        <!-- Thumbnail Button -->
                                        <button 
                                            @click="openProofModal(order.payment_proof_url || order.payment_proof_path, order.order_code)"
                                            type="button"
                                            class="relative group/thumb w-12 h-12 rounded-lg overflow-hidden border border-gray-300 hover:border-gray-900 transition-all shadow-2xs cursor-pointer block bg-gray-100"
                                            title="Klik untuk memperbesar bukti pembayaran"
                                        >
                                            <img 
                                                :src="getProofUrl(order.payment_proof_url || order.payment_proof_path)" 
                                                alt="Bukti Transfer"
                                                class="w-full h-full object-cover group-hover/thumb:scale-105 transition-transform" 
                                            />
                                            <div class="absolute inset-0 bg-black/30 opacity-0 group-hover/thumb:opacity-100 transition-opacity flex items-center justify-center text-white">
                                                <Eye class="w-4 h-4" />
                                            </div>
                                        </button>
                                        <button 
                                            @click="openProofModal(order.payment_proof_url || order.payment_proof_path, order.order_code)"
                                            type="button"
                                            class="text-[11px] font-bold text-gray-700 hover:text-black underline cursor-pointer"
                                        >
                                            Lihat Bukti
                                        </button>
                                    </div>
                                    <div v-else class="flex flex-col items-center justify-center text-gray-400 py-1">
                                        <ImageIcon class="w-5 h-5 text-gray-300 mb-0.5" />
                                        <span class="text-[11px] italic">Belum Ada</span>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3.5 px-4 border-r border-gray-200 whitespace-nowrap">
                                    <span 
                                        :class="[
                                            'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border',
                                            getStatusBadge(order.payment_status).badgeBg
                                        ]"
                                    >
                                        <span 
                                            :class="[
                                                'w-1.5 h-1.5 rounded-full mr-1.5 shrink-0',
                                                getStatusBadge(order.payment_status).dot
                                            ]"
                                        ></span>
                                        {{ getStatusBadge(order.payment_status).label }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <button 
                                            v-if="order.payment_status === 'awaiting_verification'"
                                            @click="approveOrder(order)" 
                                            type="button"
                                            class="inline-flex items-center px-2.5 py-1.5 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-lg shadow-2xs transition-all cursor-pointer" 
                                            title="Setujui Pesanan"
                                        >
                                            <CheckCircle2 class="w-3.5 h-3.5 mr-1 text-emerald-400" />
                                            Setujui
                                        </button>

                                        <!-- Detail Button -->
                                        <button 
                                            @click="openOrderDetailModal(order)"
                                            type="button"
                                            class="inline-flex items-center px-2.5 py-1.5 bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 rounded-lg text-xs font-semibold transition-all shadow-2xs cursor-pointer"
                                            title="Lihat Detail Pesanan"
                                        >
                                            <Eye class="w-3.5 h-3.5 mr-1 text-gray-500" />
                                            <span>Detail</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredOrders.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <ReceiptText class="w-10 h-10 text-gray-300 mb-2" />
                                        <p class="text-sm font-bold text-gray-700">Tidak ada pesanan ditemukan</p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            {{ searchQuery || statusFilter !== 'all' ? 'Coba sesuaikan kata kunci pencarian atau filter status transaksi.' : 'Belum ada data pesanan yang masuk.' }}
                                        </p>
                                        <button 
                                            v-if="searchQuery || statusFilter !== 'all'" 
                                            @click="searchQuery = ''; statusFilter = 'all'; currentPage = 1;"
                                            class="mt-3 text-xs font-bold text-gray-900 hover:underline cursor-pointer"
                                        >
                                            Reset Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer Matching Menus & KitchenReport -->
                <div 
                    v-if="filteredOrders.length > 0" 
                    class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-600"
                >
                    <div class="font-medium">
                        Menampilkan <span class="font-bold text-gray-900">{{ startItemIndex }}</span> sampai <span class="font-bold text-gray-900">{{ endItemIndex }}</span> dari <span class="font-bold text-gray-900">{{ filteredOrders.length }}</span> pesanan
                    </div>

                    <div v-if="totalPages > 1" class="flex items-center space-x-1.5">
                        <button 
                            @click="currentPage--"
                            :disabled="currentPage === 1"
                            class="px-2.5 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-bold hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
                        >
                            <ChevronLeft class="w-4 h-4" />
                        </button>

                        <span class="px-3 py-1 font-bold text-gray-800">
                            Halaman {{ currentPage }} dari {{ totalPages }}
                        </span>

                        <button 
                            @click="currentPage++"
                            :disabled="currentPage === totalPages"
                            class="px-2.5 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-bold hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer"
                        >
                            <ChevronRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Detail Pesanan Proposional (Scroll-Locked & Beautifully Themed) -->
            <div 
                v-if="isDetailModalOpen && selectedOrder" 
                class="fixed inset-0 z-50 overflow-y-auto" 
                role="dialog" 
                aria-modal="true"
            >
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-4">
                    <!-- Backdrop with smooth blur and click-to-close -->
                    <div 
                        class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" 
                        @click="closeDetailModal"
                    ></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <!-- Modal Card (max-w-3xl for balanced, proportional reading) -->
                    <div 
                        class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-gray-300"
                        @click.stop
                    >
                        <!-- Header -->
                        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-11 h-11 rounded-xl bg-gray-200 text-gray-800 border border-gray-300 flex items-center justify-center font-black text-base shrink-0 shadow-2xs">
                                    {{ (selectedOrder.user?.name || 'P').charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2.5">
                                        <h3 class="text-base sm:text-lg font-black text-gray-900 leading-tight">
                                            Rincian Pesanan #{{ selectedOrder.order_code }}
                                        </h3>
                                        <span 
                                            :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border',
                                                getStatusBadge(selectedOrder.payment_status).badgeBg
                                            ]"
                                        >
                                            <span 
                                                :class="[
                                                    'w-1.5 h-1.5 rounded-full mr-1.5 shrink-0',
                                                    getStatusBadge(selectedOrder.payment_status).dot
                                                ]"
                                            ></span>
                                            {{ getStatusBadge(selectedOrder.payment_status).label }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 font-medium mt-0.5">
                                        Dibuat pada: {{ formatDate(selectedOrder.created_at) }}
                                    </p>
                                </div>
                            </div>
                            <button 
                                @click="closeDetailModal"
                                type="button"
                                class="text-gray-400 hover:text-gray-700 p-1.5 rounded-lg hover:bg-gray-200 transition-colors cursor-pointer"
                                title="Tutup Modal"
                            >
                                <X class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Body (Proportional layout with internal scroll if needed) -->
                        <div class="p-6 space-y-5 max-h-[72vh] overflow-y-auto">
                            <!-- Data Pemesan -->
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-2">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">
                                    Data Pemesan
                                </span>
                                <div>
                                    <h4 class="font-bold text-gray-900 text-sm">
                                        {{ selectedOrder.user?.name || '-' }}
                                    </h4>
                                    <p class="text-xs text-gray-500 mt-0.5 truncate">
                                        {{ selectedOrder.user?.email || '-' }}
                                    </p>
                                </div>
                                <div class="pt-2 border-t border-gray-200 flex items-center justify-between text-xs">
                                    <span class="font-mono font-bold text-gray-800">
                                        {{ selectedOrder.user?.phone || '-' }}
                                    </span>
                                    <a 
                                        v-if="selectedOrder.user?.phone"
                                        :href="getWhatsAppUrl(selectedOrder.user.phone, selectedOrder.user.name, selectedOrder.order_code)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center px-2 py-1 bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 rounded-lg text-xs font-bold shadow-2xs transition-all cursor-pointer"
                                    >
                                        <MessageCircle class="w-3.5 h-3.5 mr-1 text-emerald-600" />
                                        Chat WhatsApp
                                    </a>
                                </div>
                            </div>

                            <!-- Allergen Alert (if applicable) -->
                            <div 
                                v-if="selectedOrder.user?.allergens && selectedOrder.user.allergens.length > 0"
                                class="p-3.5 bg-red-50 border border-red-200 rounded-xl flex items-start space-x-2.5 text-xs text-red-900"
                            >
                                <AlertTriangle class="w-4 h-4 text-red-600 shrink-0 mt-0.5" />
                                <div>
                                    <span class="font-black">Catatan Pantangan Alergi Pemesan:</span>
                                    <p class="font-medium mt-0.5 text-red-800">
                                        {{ selectedOrder.user.allergens.map(a => a.name).join(', ') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Daftar Menu yang Dipesan -->
                            <div>
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2.5 flex items-center">
                                    <Utensils class="w-3.5 h-3.5 mr-1.5 text-gray-700" />
                                    Daftar Menu & Hari Layanan
                                </h4>
                                <div class="border border-gray-200 rounded-xl overflow-hidden shadow-2xs">
                                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                                        <thead class="bg-gray-50 font-bold text-gray-600">
                                            <tr>
                                                <th class="py-2.5 px-3.5 text-left border-r border-gray-200">Menu</th>
                                                <th class="py-2.5 px-3.5 text-left border-r border-gray-200">Hari Layanan</th>
                                                <th class="py-2.5 px-2.5 text-center border-r border-gray-200">Porsi</th>
                                                <th class="py-2.5 px-3.5 text-right border-r border-gray-200">Harga Satuan</th>
                                                <th class="py-2.5 px-3.5 text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            <tr 
                                                v-for="item in selectedOrder.order_items" 
                                                :key="item.id"
                                                class="hover:bg-gray-50/70 transition-colors"
                                            >
                                                <td class="py-2.5 px-3.5 text-left border-r border-gray-200">
                                                    <span class="font-bold text-gray-900 block">
                                                        {{ item.menu?.name || 'Menu Katering' }}
                                                    </span>
                                                </td>
                                                <td class="py-2.5 px-3.5 text-left border-r border-gray-200 text-gray-600 font-medium whitespace-nowrap">
                                                    {{ formatServiceDate(item.service_date) }}
                                                </td>
                                                <td class="py-2.5 px-2.5 text-center border-r border-gray-200">
                                                    <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 text-gray-900 rounded font-bold text-xs border border-gray-200">
                                                        {{ item.portion_quantity }}x
                                                    </span>
                                                </td>
                                                <td class="py-2.5 px-3.5 text-right text-gray-600 border-r border-gray-200 whitespace-nowrap">
                                                    {{ formatPrice(item.unit_price) }}
                                                </td>
                                                <td class="py-2.5 px-3.5 text-right font-bold text-gray-900 whitespace-nowrap">
                                                    {{ formatPrice(item.subtotal) }}
                                                </td>
                                            </tr>
                                            <tr v-if="!selectedOrder.order_items || selectedOrder.order_items.length === 0">
                                                <td colspan="5" class="py-6 text-center text-gray-400 italic">
                                                    Tidak ada rincian item pesanan.
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-gray-50 font-bold border-t border-gray-200">
                                            <tr>
                                                <td colspan="2" class="py-2 px-3.5 text-gray-700 border-r border-gray-200">
                                                    Total Ringkasan:
                                                </td>
                                                <td class="py-2 px-2.5 text-center text-gray-900 font-black border-r border-gray-200">
                                                    {{ getOrderTotalPortions(selectedOrder) }}x
                                                </td>
                                                <td class="py-2 px-3.5 text-right text-gray-500 border-r border-gray-200">
                                                    Subtotal:
                                                </td>
                                                <td class="py-2 px-3.5 text-right text-gray-900 font-black">
                                                    {{ formatPrice(selectedOrder.subtotal_amount) }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- Bukti Transfer Pembayaran -->
                            <div class="border border-gray-200 rounded-xl p-4 bg-gray-50">
                                <div class="flex items-center justify-between mb-2.5">
                                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center">
                                        <CreditCard class="w-3.5 h-3.5 mr-1.5 text-gray-600" />
                                        Bukti Transfer Pembayaran
                                    </span>
                                    <button 
                                        v-if="selectedOrder.payment_proof_url || selectedOrder.payment_proof_path"
                                        @click="openProofModal(selectedOrder.payment_proof_url || selectedOrder.payment_proof_path, selectedOrder.order_code)"
                                        type="button"
                                        class="text-xs text-gray-900 hover:text-black font-bold flex items-center hover:underline cursor-pointer"
                                    >
                                        Buka Ukuran Penuh
                                        <ExternalLink class="w-3.5 h-3.5 ml-1" />
                                    </button>
                                </div>

                                <div 
                                    v-if="selectedOrder.payment_proof_url || selectedOrder.payment_proof_path"
                                    class="flex justify-center bg-white p-3 rounded-xl border border-gray-200 shadow-2xs cursor-pointer group"
                                    @click="openProofModal(selectedOrder.payment_proof_url || selectedOrder.payment_proof_path, selectedOrder.order_code)"
                                    title="Klik untuk memperbesar bukti transfer"
                                >
                                    <img 
                                        :src="getProofUrl(selectedOrder.payment_proof_url || selectedOrder.payment_proof_path)" 
                                        alt="Bukti Transfer Pembayaran" 
                                        class="max-h-64 object-contain rounded-lg group-hover:scale-[1.01] transition-transform duration-150" 
                                    />
                                </div>
                                <div v-else class="flex flex-col items-center justify-center py-6 text-gray-400 bg-white rounded-xl border border-dashed border-gray-300">
                                    <ImageIcon class="w-8 h-8 text-gray-300 mb-1" />
                                    <span class="text-xs italic">Pelanggan belum mengunggah bukti pembayaran transfer.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div class="text-xs text-gray-500 font-medium">
                                Kode Referensi: <span class="font-mono font-bold text-gray-900">#{{ selectedOrder.order_code }}</span>
                            </div>

                            <div class="flex items-center space-x-2.5 w-full sm:w-auto justify-end">
                                <button 
                                    @click="closeDetailModal"
                                    type="button"
                                    class="px-4 py-2 bg-white hover:bg-gray-100 text-gray-700 border border-gray-300 rounded-xl text-xs sm:text-sm font-bold shadow-2xs transition-colors cursor-pointer"
                                >
                                    Tutup
                                </button>
                                <button 
                                    v-if="selectedOrder.payment_status === 'awaiting_verification'"
                                    @click="handleApproveFromModal"
                                    type="button"
                                    class="px-4 py-2 bg-gray-900 hover:bg-black text-white rounded-xl text-xs sm:text-sm font-bold shadow-2xs transition-all flex items-center cursor-pointer"
                                >
                                    <CheckCircle2 class="w-4 h-4 mr-1.5 text-emerald-400" />
                                    Setujui Pesanan Ini
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
