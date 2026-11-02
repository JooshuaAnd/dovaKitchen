<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { 
    Users, 
    Trash2, 
    Eye, 
    Search, 
    ChevronLeft, 
    ChevronRight,
    MessageCircle,
    DollarSign,
    ShoppingCart,
    Clock,
    Activity,
    CheckCircle2
} from 'lucide-vue-next';
import dayjs from 'dayjs';
import UserDetailModal from './Partials/UserDetailModal.vue';
import WhatsAppModal from './Partials/WhatsAppModal.vue';

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
    summary: {
        type: Object,
        default: () => ({})
    },
    voucherTemplates: {
        type: Array,
        default: () => []
    }
});

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

// Filter & Sort state
const searchQuery = ref('');
const selectedSegmentFilter = ref('all');
const sortBy = ref('monetary_desc');

// Pagination state
const perPage = ref(10);
const currentPage = ref(1);

// Detail Modal state
const isDetailModalOpen = ref(false);
const selectedUser = ref(null);

const openDetailModal = (user) => {
    selectedUser.value = user;
    isDetailModalOpen.value = true;
};

// WhatsApp Modal state
const isWaModalOpen = ref(false);
const waSelectedUser = ref(null);

const openWaModal = (user) => {
    waSelectedUser.value = user;
    isWaModalOpen.value = true;
};

// Delete user
const confirmDelete = (user) => {
    if (confirm(`Yakin ingin menghapus data kustomer ${user.name}? Semua riwayat pesanan juga akan terhapus.`)) {
        router.delete(route('admin.users.destroy', user.id), {
            preserveScroll: true,
            onSuccess: () => {
                if (isDetailModalOpen.value && selectedUser.value?.id === user.id) {
                    isDetailModalOpen.value = false;
                }
            }
        });
    }
};

// Filtered and sorted users
const filteredUsers = computed(() => {
    let result = [...props.users];

    // Search query
    if (searchQuery.value.trim() !== '') {
        const q = searchQuery.value.toLowerCase().trim();
        result = result.filter(u => {
            const nameMatch = u.name?.toLowerCase().includes(q);
            const emailMatch = u.email?.toLowerCase().includes(q);
            const phoneMatch = u.phone?.toLowerCase().includes(q);
            const rfmCodeMatch = u.rfm?.rfm_code?.toLowerCase().includes(q);
            return nameMatch || emailMatch || phoneMatch || rfmCodeMatch;
        });
    }

    // Segment filter
    if (selectedSegmentFilter.value !== 'all') {
        if (selectedSegmentFilter.value === 'at_risk_any') {
            result = result.filter(u => u.rfm?.segment?.startsWith('At Risk'));
        } else {
            result = result.filter(u => u.rfm?.segment === selectedSegmentFilter.value);
        }
    }

    // Sorting
    result.sort((a, b) => {
        if (sortBy.value === 'monetary_desc') {
            return (b.rfm?.monetary_total_spent || 0) - (a.rfm?.monetary_total_spent || 0);
        } else if (sortBy.value === 'monetary_asc') {
            return (a.rfm?.monetary_total_spent || 0) - (b.rfm?.monetary_total_spent || 0);
        } else if (sortBy.value === 'frequency_desc') {
            return (b.rfm?.frequency_paid_orders || 0) - (a.rfm?.frequency_paid_orders || 0);
        } else if (sortBy.value === 'recency_asc') {
            const aDays = a.rfm?.recency_days ?? 999999;
            const bDays = b.rfm?.recency_days ?? 999999;
            return aDays - bDays;
        } else if (sortBy.value === 'rfm_score_desc') {
            return (b.rfm?.rfm_total_score || 0) - (a.rfm?.rfm_total_score || 0);
        } else if (sortBy.value === 'created_desc') {
            return new Date(b.created_at) - new Date(a.created_at);
        }
        return 0;
    });

    return result;
});

// Reset page when filter changes
watch([searchQuery, selectedSegmentFilter, perPage], () => {
    currentPage.value = 1;
});

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / perPage.value) || 1);

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredUsers.value.slice(start, start + perPage.value);
});

const startItem = computed(() => {
    if (filteredUsers.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const endItem = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredUsers.value.length);
});

// 2-Tone Professional Badge Styling
const getSegmentBadgeStyle = (segment) => {
    if (segment === 'At Risk VIP' || segment === 'At Risk Routine' || segment === 'Previously Big Spenders') {
        return 'bg-red-50 text-red-700 border border-red-300';
    }
    if (segment === 'Champions' || segment === 'Loyal Repeaters' || segment === 'Big Spenders (Baru)') {
        return 'bg-black text-white border border-black';
    }
    return 'bg-gray-100 text-gray-800 border border-gray-300';
};

const getSegmentSubtitle = (segment) => {
    const map = {
        'Champions': 'RFM Tinggi (Aktif & Belanja Tinggi)',
        'Loyal Repeaters': 'RF Tinggi, M Rendah (Sering Order)',
        'Big Spenders (Baru)': 'RM Tinggi, F Rendah (Order Besar)',
        'New / Recent Customers': 'R Tinggi, FM Rendah (Pelanggan Baru)',
        'At Risk VIP': 'FM Tinggi, R Rendah (VIP Pasif)',
        'At Risk Routine': 'F Tinggi, RM Rendah (Rutin Pasif)',
        'Previously Big Spenders': 'M Tinggi, RF Rendah (Pernah Belanja Besar)',
        'Hibernating / Pasif': 'RFM Rendah (Lama Tidak Belanja)',
        'Belum Bertransaksi': 'Belum Memiliki Transaksi Valid'
    };
    return map[segment] || 'Status Kustomer';
};

const filterBySegment = (segmentKey) => {
    selectedSegmentFilter.value = segmentKey;
};
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">
            <!-- Header Title -->
            <div class="border-b border-gray-200 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-black text-gray-900 tracking-tight flex items-center">
                        <Users class="w-6 h-6 mr-2 text-red-600" />
                        Manajemen Pengguna dan Analisis RFM
                    </h1>
                    <p class="text-xs text-gray-500 mt-1">
                        Daftar kustomer, segmentasi matriks RFM, dan pengelolaan voucher diskon personal.
                    </p>
                </div>
            </div>

            <!-- Flash Message -->
            <div v-if="$page.props.flash?.success" class="p-3 bg-white border border-black rounded flex items-center text-xs font-bold text-gray-900">
                <CheckCircle2 class="w-4 h-4 text-black mr-2 shrink-0" />
                <span>{{ $page.props.flash.success }}</span>
            </div>

            <!-- 1. CORE KPI SUMMARY CARDS (Flat 2-Tone: Hitam, Putih, Merah) -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Total Kustomer -->
                <div class="bg-white p-4 border border-gray-300 rounded-lg flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total Pelanggan</div>
                        <div class="text-2xl font-black text-gray-900 mt-1">{{ summary.total_customers || 0 }}</div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Akun terdaftar</div>
                    </div>
                    <div class="w-9 h-9 border border-gray-300 rounded flex items-center justify-center text-gray-700 bg-gray-50">
                        <Users class="w-4 h-4" />
                    </div>
                </div>

                <!-- Total Pendapatan Valid -->
                <div class="bg-white p-4 border border-gray-300 rounded-lg flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Total Pendapatan</div>
                        <div class="text-2xl font-black text-gray-900 mt-1">{{ formatPrice(summary.total_revenue) }}</div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Transaksi lunas</div>
                    </div>
                    <div class="w-9 h-9 border border-gray-300 rounded flex items-center justify-center text-gray-700 bg-gray-50">
                        <DollarSign class="w-4 h-4" />
                    </div>
                </div>

                <!-- Total Transaksi Valid -->
                <div class="bg-white p-4 border border-gray-300 rounded-lg flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Transaksi Valid</div>
                        <div class="text-2xl font-black text-gray-900 mt-1">{{ summary.total_transactions || 0 }} <span class="text-xs font-normal text-gray-500">Order</span></div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Pesanan berhasil</div>
                    </div>
                    <div class="w-9 h-9 border border-gray-300 rounded flex items-center justify-center text-gray-700 bg-gray-50">
                        <ShoppingCart class="w-4 h-4" />
                    </div>
                </div>

                <!-- Pelanggan Aktif -->
                <div class="bg-white p-4 border border-gray-300 rounded-lg flex items-center justify-between">
                    <div>
                        <div class="text-[11px] font-bold text-gray-500 uppercase tracking-wider">Pelanggan Aktif</div>
                        <div class="text-2xl font-black text-gray-900 mt-1">{{ summary.active_customers || 0 }}</div>
                        <div class="text-[11px] text-gray-500 mt-0.5">Order &le; 14 hari terakhir</div>
                    </div>
                    <div class="w-9 h-9 border border-gray-300 rounded flex items-center justify-center text-gray-700 bg-gray-50">
                        <Activity class="w-4 h-4" />
                    </div>
                </div>
            </div>

            <!-- 2. RFM 8 SEGMENTS GRID (Flat Professional Cards) -->
            <div class="bg-white p-4 border border-gray-300 rounded-lg space-y-3">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <h2 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                            Distribusi 8 Segmen RFM Pelanggan
                        </h2>
                        <p class="text-[11px] text-gray-500 mt-0.5">Klik kotak untuk menyaring daftar pelanggan di bawah.</p>
                    </div>
                    <button 
                        v-if="selectedSegmentFilter !== 'all'"
                        @click="selectedSegmentFilter = 'all'" 
                        class="text-[11px] font-bold text-red-600 hover:underline"
                    >
                        Tampilkan Semua Segmen
                    </button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2 pt-1">
                    <!-- Champions -->
                    <button 
                        @click="filterBySegment('Champions')"
                        class="p-2.5 border text-left rounded transition-colors"
                        :class="selectedSegmentFilter === 'Champions' ? 'bg-black text-white border-black' : 'bg-white border-gray-300 hover:border-black text-gray-900'"
                    >
                        <div class="text-[10px] font-bold truncate">Champions</div>
                        <div class="text-lg font-black mt-0.5">{{ summary.champions_count || 0 }}</div>
                        <div class="text-[9px] opacity-75 truncate">RFM Tinggi</div>
                    </button>

                    <!-- Loyal Repeaters -->
                    <button 
                        @click="filterBySegment('Loyal Repeaters')"
                        class="p-2.5 border text-left rounded transition-colors"
                        :class="selectedSegmentFilter === 'Loyal Repeaters' ? 'bg-black text-white border-black' : 'bg-white border-gray-300 hover:border-black text-gray-900'"
                    >
                        <div class="text-[10px] font-bold truncate">Loyal</div>
                        <div class="text-lg font-black mt-0.5">{{ summary.loyal_repeaters_count || 0 }}</div>
                        <div class="text-[9px] opacity-75 truncate">RF Tinggi</div>
                    </button>

                    <!-- Big Spenders (Baru) -->
                    <button 
                        @click="filterBySegment('Big Spenders (Baru)')"
                        class="p-2.5 border text-left rounded transition-colors"
                        :class="selectedSegmentFilter === 'Big Spenders (Baru)' ? 'bg-black text-white border-black' : 'bg-white border-gray-300 hover:border-black text-gray-900'"
                    >
                        <div class="text-[10px] font-bold truncate">Big Spender</div>
                        <div class="text-lg font-black mt-0.5">{{ summary.big_spenders_count || 0 }}</div>
                        <div class="text-[9px] opacity-75 truncate">RM Tinggi</div>
                    </button>

                    <!-- New Customers -->
                    <button 
                        @click="filterBySegment('New / Recent Customers')"
                        class="p-2.5 border text-left rounded transition-colors"
                        :class="selectedSegmentFilter === 'New / Recent Customers' ? 'bg-black text-white border-black' : 'bg-white border-gray-300 hover:border-black text-gray-900'"
                    >
                        <div class="text-[10px] font-bold truncate">Baru</div>
                        <div class="text-lg font-black mt-0.5">{{ summary.new_customers_count || 0 }}</div>
                        <div class="text-[9px] opacity-75 truncate">R Tinggi</div>
                    </button>

                    <!-- At Risk VIP -->
                    <button 
                        @click="filterBySegment('At Risk VIP')"
                        class="p-2.5 border text-left rounded transition-colors"
                        :class="selectedSegmentFilter === 'At Risk VIP' ? 'bg-red-600 text-white border-red-600' : 'bg-white border-red-300 hover:border-red-600 text-red-700'"
                    >
                        <div class="text-[10px] font-bold truncate">At Risk VIP</div>
                        <div class="text-lg font-black mt-0.5">{{ summary.at_risk_vip_count || 0 }}</div>
                        <div class="text-[9px] opacity-75 truncate">FM Tinggi Pasif</div>
                    </button>

                    <!-- At Risk Routine -->
                    <button 
                        @click="filterBySegment('At Risk Routine')"
                        class="p-2.5 border text-left rounded transition-colors"
                        :class="selectedSegmentFilter === 'At Risk Routine' ? 'bg-red-600 text-white border-red-600' : 'bg-white border-red-300 hover:border-red-600 text-red-700'"
                    >
                        <div class="text-[10px] font-bold truncate">At Risk Rutin</div>
                        <div class="text-lg font-black mt-0.5">{{ summary.at_risk_routine_count || 0 }}</div>
                        <div class="text-[9px] opacity-75 truncate">F Tinggi Pasif</div>
                    </button>

                    <!-- Previously Big Spenders -->
                    <button 
                        @click="filterBySegment('Previously Big Spenders')"
                        class="p-2.5 border text-left rounded transition-colors"
                        :class="selectedSegmentFilter === 'Previously Big Spenders' ? 'bg-red-600 text-white border-red-600' : 'bg-white border-red-300 hover:border-red-600 text-red-700'"
                    >
                        <div class="text-[10px] font-bold truncate">Dulu Belanja</div>
                        <div class="text-lg font-black mt-0.5">{{ summary.previously_big_spenders_count || 0 }}</div>
                        <div class="text-[9px] opacity-75 truncate">M Tinggi Pasif</div>
                    </button>

                    <!-- Hibernating / Pasif -->
                    <button 
                        @click="filterBySegment('Hibernating / Pasif')"
                        class="p-2.5 border text-left rounded transition-colors"
                        :class="selectedSegmentFilter === 'Hibernating / Pasif' ? 'bg-black text-white border-black' : 'bg-white border-gray-300 hover:border-black text-gray-900'"
                    >
                        <div class="text-[10px] font-bold truncate">Hibernasi</div>
                        <div class="text-lg font-black mt-0.5">{{ summary.hibernating_count || 0 }}</div>
                        <div class="text-[9px] opacity-75 truncate">RFM Rendah</div>
                    </button>
                </div>
            </div>

            <!-- Search & Filters Toolbar -->
            <div class="bg-white rounded-lg border border-gray-300 p-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-2.5">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <Search class="w-4 h-4 absolute left-3 top-2.5 text-gray-400" />
                        <input 
                            v-model="searchQuery" 
                            type="text" 
                            placeholder="Cari nama, email, no HP, atau kode RFM..." 
                            class="w-full pl-9 pr-3 py-1.5 text-xs bg-white border border-gray-300 rounded outline-none focus:border-black"
                        >
                    </div>

                    <!-- RFM Segment Filter -->
                    <div class="sm:w-60">
                        <select 
                            v-model="selectedSegmentFilter" 
                            class="w-full text-xs bg-white border border-gray-300 rounded py-1.5 px-2.5 outline-none focus:border-black cursor-pointer"
                        >
                            <option value="all">Semua Segmen RFM</option>
                            <option value="Champions">Champions (RFM Tinggi)</option>
                            <option value="Loyal Repeaters">Loyal Repeaters (RF Tinggi)</option>
                            <option value="Big Spenders (Baru)">Big Spenders (Baru)</option>
                            <option value="New / Recent Customers">New / Recent Customers</option>
                            <option value="At Risk VIP">At Risk VIP</option>
                            <option value="At Risk Routine">At Risk Routine</option>
                            <option value="Previously Big Spenders">Previously Big Spenders</option>
                            <option value="Hibernating / Pasif">Hibernating / Pasif</option>
                            <option value="Belum Bertransaksi">Belum Bertransaksi</option>
                        </select>
                    </div>

                    <!-- Sorting -->
                    <div class="sm:w-52">
                        <select 
                            v-model="sortBy" 
                            class="w-full text-xs bg-white border border-gray-300 rounded py-1.5 px-2.5 outline-none focus:border-black cursor-pointer"
                        >
                            <option value="monetary_desc">Monetary Tertinggi</option>
                            <option value="monetary_asc">Monetary Terendah</option>
                            <option value="recency_asc">Recency Terbaru</option>
                            <option value="frequency_desc">Frequency Terbanyak</option>
                            <option value="rfm_score_desc">Total Skor RFM</option>
                            <option value="created_desc">Pendaftaran Terbaru</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table Wrapper -->
            <div class="bg-white rounded-lg border border-gray-300 overflow-hidden">
                <!-- Top Table Bar -->
                <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between text-xs text-gray-600 gap-2 font-medium">
                    <div class="flex items-center space-x-2">
                        <span>Tampilkan</span>
                        <select 
                            v-model="perPage" 
                            class="bg-white border border-gray-300 rounded py-0.5 px-2 text-xs font-bold text-gray-800 outline-none"
                        >
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                        <span>data per halaman</span>
                    </div>

                    <div>
                        Menampilkan 
                        <span class="font-bold text-gray-900">{{ startItem }} – {{ endItem }}</span> 
                        dari 
                        <span class="font-bold text-gray-900">{{ filteredUsers.length }}</span> 
                        total pelanggan
                    </div>
                </div>

                <!-- Flat Table Grid -->
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="bg-gray-100 border-b border-gray-300 font-bold text-gray-700">
                                <th scope="col" class="py-2.5 px-3 text-center border-r border-gray-200 w-12">
                                    No
                                </th>
                                <th scope="col" class="py-2.5 px-4 border-r border-gray-200 min-w-[200px]">
                                    Pelanggan / Akun
                                </th>
                                <th scope="col" class="py-2.5 px-4 border-r border-gray-200 min-w-[210px]">
                                    Segmentasi & Kode RFM
                                </th>
                                <th scope="col" class="py-2.5 px-4 border-r border-gray-200 min-w-[170px]">
                                    Terakhir Beli (Recency)
                                </th>
                                <th scope="col" class="py-2.5 px-4 border-r border-gray-200 min-w-[200px]">
                                    Frequency & Monetary
                                </th>
                                <th scope="col" class="py-2.5 px-4 border-r border-gray-200 min-w-[160px]">
                                    Profil Diet / Pantangan
                                </th>
                                <th scope="col" class="py-2.5 px-3 text-center min-w-[110px]">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-gray-800 bg-white">
                            <tr 
                                v-for="(user, idx) in paginatedUsers" 
                                :key="user.id" 
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <!-- No -->
                                <td class="py-3 px-3 text-center border-r border-gray-200 font-medium text-gray-500">
                                    {{ (currentPage - 1) * perPage + idx + 1 }}
                                </td>

                                <!-- Customer Details -->
                                <td class="py-3 px-4 border-r border-gray-200">
                                    <div class="font-bold text-gray-900">
                                        {{ user.name }}
                                    </div>
                                    <div class="text-[11px] text-gray-600 font-mono mt-0.5">
                                        {{ user.phone || 'Nomor HP belum diisi' }}
                                    </div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">
                                        {{ user.email }}
                                    </div>
                                </td>

                                <!-- RFM Segment & 3-Digit Code -->
                                <td class="py-3 px-4 border-r border-gray-200">
                                    <div class="flex items-center space-x-1.5">
                                        <span 
                                            class="inline-block px-2 py-0.5 rounded font-bold text-[11px]"
                                            :class="getSegmentBadgeStyle(user.rfm?.segment)"
                                        >
                                            {{ user.rfm?.segment || 'Belum Bertransaksi' }}
                                        </span>
                                        <span 
                                            v-if="user.rfm?.rfm_code"
                                            class="font-mono font-bold text-[11px] px-1 py-0.2 bg-gray-100 border border-gray-300 rounded text-gray-900"
                                        >
                                            {{ user.rfm.rfm_code }}
                                        </span>
                                    </div>

                                    <div v-if="user.rfm?.score_r" class="text-[10px] text-gray-500 font-mono mt-1">
                                        R:{{ user.rfm.score_r }} · F:{{ user.rfm.score_f }} · M:{{ user.rfm.score_m }} (Skor {{ user.rfm.rfm_total_score }})
                                    </div>
                                    <div v-else class="text-[10px] text-gray-400 italic mt-0.5">
                                        Belum memiliki skor
                                    </div>

                                    <div class="text-[10px] text-gray-500 mt-0.5 truncate max-w-[200px]" :title="user.rfm?.segment_description">
                                        {{ getSegmentSubtitle(user.rfm?.segment) }}
                                    </div>
                                </td>

                                <!-- Recency -->
                                <td class="py-3 px-4 border-r border-gray-200">
                                    <div class="font-bold text-gray-900">
                                        {{ user.rfm?.recency_label || '-' }}
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">
                                        {{ user.rfm?.recency_last_order_at ? formatDateTime(user.rfm.recency_last_order_at) : 'Belum pernah order' }}
                                    </div>
                                </td>

                                <!-- Frequency & Monetary -->
                                <td class="py-3 px-4 border-r border-gray-200">
                                    <div class="flex items-center space-x-1.5 mb-0.5 font-bold text-[11px]">
                                        <span>{{ formatPrice(user.rfm?.monetary_total_spent) }}</span>
                                        <span class="text-gray-400">·</span>
                                        <span class="text-gray-700">{{ user.rfm?.frequency_paid_orders || 0 }}x Lunas</span>
                                    </div>
                                    <div class="text-[11px] text-gray-500">
                                        Rata-rata: {{ formatPrice(user.rfm?.monetary_avg_order) }} / order
                                    </div>
                                </td>

                                <!-- Dietary Profile -->
                                <td class="py-3 px-4 border-r border-gray-200">
                                    <div class="mb-0.5">
                                        <span 
                                            v-if="user.allergens && user.allergens.length > 0"
                                            class="inline-block px-1.5 py-0.2 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200"
                                        >
                                            {{ user.allergens.length }} Pantangan
                                        </span>
                                        <span 
                                            v-else
                                            class="inline-block px-1.5 py-0.2 rounded text-[10px] font-medium bg-gray-100 text-gray-700"
                                        >
                                            Bebas Alergi
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-gray-500 truncate max-w-[150px]">
                                        <template v-if="user.allergens && user.allergens.length > 0">
                                            {{ user.allergens.map(a => a.name).join(', ') }}
                                        </template>
                                        <template v-else>
                                            Tidak ada pantangan
                                        </template>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-3 text-center">
                                    <div class="flex items-center justify-center space-x-1.5">
                                        <!-- Detail Button (Eye) -->
                                        <button 
                                            @click="openDetailModal(user)" 
                                            class="p-1 text-gray-700 hover:text-black hover:bg-gray-100 rounded border border-gray-300 transition-colors" 
                                            title="Lihat Detail Analisis"
                                        >
                                            <Eye class="w-3.5 h-3.5" />
                                        </button>

                                        <!-- WhatsApp Button -->
                                        <button 
                                            v-if="user.phone" 
                                            @click="openWaModal(user)" 
                                            class="p-1 text-red-600 hover:text-red-700 hover:bg-red-50 rounded border border-red-200 transition-colors" 
                                            title="Kirim Pesan WhatsApp"
                                        >
                                            <MessageCircle class="w-3.5 h-3.5" />
                                        </button>

                                        <!-- Delete Button -->
                                        <button 
                                            @click="confirmDelete(user)" 
                                            class="p-1 text-gray-400 hover:text-red-600 hover:bg-gray-100 rounded border border-gray-300 transition-colors" 
                                            title="Hapus Pelanggan"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="7" class="py-10 text-center text-gray-500">
                                    <p class="text-xs font-bold text-gray-800">Tidak ada data pelanggan yang sesuai</p>
                                    <p class="text-[11px] text-gray-400 mt-0.5">Ubah kata kunci pencarian atau filter segmen.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Bottom Bar -->
                <div v-if="totalPages > 1" class="px-4 py-2.5 bg-gray-50 border-t border-gray-200 flex items-center justify-between text-xs">
                    <div class="text-gray-500">
                        Halaman {{ currentPage }} dari {{ totalPages }}
                    </div>

                    <div class="flex items-center space-x-1">
                        <button 
                            @click="currentPage--" 
                            :disabled="currentPage === 1"
                            class="p-1 border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed text-gray-700"
                        >
                            <ChevronLeft class="w-3.5 h-3.5" />
                        </button>

                        <button 
                            v-for="page in totalPages" 
                            :key="page"
                            @click="currentPage = page"
                            class="px-2.5 py-0.5 text-xs font-bold border rounded transition-colors"
                            :class="currentPage === page ? 'bg-black text-white border-black' : 'border-gray-300 text-gray-700 hover:bg-gray-100'"
                        >
                            {{ page }}
                        </button>

                        <button 
                            @click="currentPage++" 
                            :disabled="currentPage === totalPages"
                            class="p-1 border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed text-gray-700"
                        >
                            <ChevronRight class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Analisis Modal (Body Scroll Locked) -->
        <UserDetailModal 
            :show="isDetailModalOpen" 
            :user="selectedUser" 
            :voucher-templates="voucherTemplates"
            @close="isDetailModalOpen = false" 
            @delete="confirmDelete"
        />

        <!-- WhatsApp Modal -->
        <WhatsAppModal 
            :show="isWaModalOpen" 
            :user="waSelectedUser" 
            @close="isWaModalOpen = false" 
        />
    </AdminLayout>
</template>
