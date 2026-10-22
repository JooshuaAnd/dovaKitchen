<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { 
    Calendar as CalendarIcon, 
    ChevronLeft, 
    ChevronRight, 
    Printer, 
    Users, 
    Utensils, 
    Phone, 
    Mail, 
    Search, 
    AlertTriangle, 
    ExternalLink, 
    CheckCircle2, 
    ChefHat, 
    X,
    Filter,
    Clock,
    ShoppingBag,
    Eye
} from 'lucide-vue-next';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    weekDates: {
        type: Array,
        default: () => []
    },
    currentWeekStart: String
});

// Navigation URLs
const getNextWeekUrl = () => {
    return route('admin.kitchen-report.index', { date: dayjs(props.currentWeekStart).add(7, 'day').format('YYYY-MM-DD') });
};

const getPrevWeekUrl = () => {
    return route('admin.kitchen-report.index', { date: dayjs(props.currentWeekStart).subtract(7, 'day').format('YYYY-MM-DD') });
};

const getTodayUrl = () => {
    return route('admin.kitchen-report.index', { date: dayjs().format('YYYY-MM-DD') });
};

// Tab selection state: default to the first day with orders or Monday (index 0)
const selectedDayIndex = ref(0);
const showAllDays = ref(false);
const searchQuery = ref('');
const selectedMenuFilter = ref('all');

// Order Detail Modal state
const isDetailModalOpen = ref(false);
const selectedOrder = ref(null);
const selectedOrderDay = ref(null);

const openOrderDetailModal = (order, day) => {
    selectedOrder.value = order;
    selectedOrderDay.value = day || activeDay.value;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    setTimeout(() => {
        selectedOrder.value = null;
        selectedOrderDay.value = null;
    }, 200);
};

// Jump to day in detail section below
const scrollToDayDetail = (index) => {
    selectedDayIndex.value = index;
    showAllDays.value = false;
    const el = document.getElementById('rincian-pemesan-section');
    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
    }
};

// Format currency
const formatPrice = (price) => {
    if (!price && price !== 0) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(price);
};

// Format WhatsApp link
const getWhatsAppUrl = (phone, customerName, orderCode) => {
    if (!phone) return null;
    let cleaned = phone.replace(/[^0-9]/g, '');
    if (cleaned.startsWith('0')) {
        cleaned = '62' + cleaned.substring(1);
    }
    const message = encodeURIComponent(`Halo Kak ${customerName}, kami dari Dova Kitchen terkait pesanan catering #${orderCode}.`);
    return `https://wa.me/${cleaned}?text=${message}`;
};

// Trigger Print
const printReport = () => {
    window.print();
};

// Current active day data for tabs
const activeDay = computed(() => {
    if (!props.weekDates || props.weekDates.length === 0) return null;
    return props.weekDates[selectedDayIndex.value] || props.weekDates[0];
});

// All available menus across the week or selected day for filter dropdown
const availableMenus = computed(() => {
    if (showAllDays.value) {
        const set = new Map();
        props.weekDates.forEach(day => {
            (day.menus || []).forEach(m => {
                if (!set.has(m.id)) set.set(m.id, m.name);
            });
        });
        return Array.from(set.entries()).map(([id, name]) => ({ id, name }));
    } else if (activeDay.value) {
        return (activeDay.value.menus || []).map(m => ({ id: m.id, name: m.name }));
    }
    return [];
});

// Filter helper for customer orders
const filterOrders = (orders) => {
    if (!orders || orders.length === 0) return [];
    
    let result = orders;

    // Filter by search query
    if (searchQuery.value.trim() !== '') {
        const q = searchQuery.value.toLowerCase().trim();
        result = result.filter(order => {
            const nameMatch = order.customer?.name?.toLowerCase().includes(q);
            const codeMatch = order.order_code?.toLowerCase().includes(q);
            const phoneMatch = order.customer?.phone?.toLowerCase().includes(q);
            const menuMatch = order.items?.some(it => it.menu_name?.toLowerCase().includes(q));
            return nameMatch || codeMatch || phoneMatch || menuMatch;
        });
    }

    // Filter by specific menu
    if (selectedMenuFilter.value !== 'all') {
        const menuId = Number(selectedMenuFilter.value);
        result = result.filter(order => {
            return order.items?.some(it => Number(it.menu_id) === menuId);
        });
    }

    return result;
};

// Total weekly orders count
const totalWeeklyOrders = computed(() => {
    return props.weekDates.reduce((acc, day) => acc + (day.total_customers || 0), 0);
});
</script>

<template>
    <Head title="Laporan Pemesanan Dapur" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 print:py-0 print:max-w-none print:px-0">
            
            <!-- Header Section (Screen Only) -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4 print:hidden">
                <div>
                    <div class="flex items-center space-x-3 mb-1">
                        <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center text-orange-600 shadow-sm">
                            <ChefHat class="w-6 h-6" />
                        </div>
                        <h1 class="text-3xl font-black text-gray-900 tracking-tight">Laporan Pemesanan Dapur</h1>
                    </div>
                </div>
                
                <!-- Action Controls -->
                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <!-- Week Navigator -->
                    <div class="flex items-center bg-white px-3 py-1.5 rounded-xl border border-gray-200 shadow-sm">
                        <Link :href="getPrevWeekUrl()" class="p-1.5 text-gray-500 hover:text-orange-600 rounded-lg hover:bg-orange-50 transition-colors" title="Minggu Sebelumnya">
                            <ChevronLeft class="w-5 h-5" />
                        </Link>
                        <div class="text-xs sm:text-sm font-bold text-gray-800 px-3 flex items-center whitespace-nowrap">
                            <CalendarIcon class="w-4 h-4 mr-2 text-orange-500" />
                            {{ dayjs(currentWeekStart).format('DD MMM') }} - {{ dayjs(currentWeekStart).add(4, 'day').format('DD MMM YYYY') }}
                        </div>
                        <Link :href="getNextWeekUrl()" class="p-1.5 text-gray-500 hover:text-orange-600 rounded-lg hover:bg-orange-50 transition-colors" title="Minggu Berikutnya">
                            <ChevronRight class="w-5 h-5" />
                        </Link>
                    </div>

                    <!-- Today / This Week Quick Button -->
                    <Link :href="getTodayUrl()" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-colors shadow-sm">
                        Hari Ini
                    </Link>

                    <!-- Print Button -->
                    <button 
                        @click="printReport" 
                        type="button" 
                        class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-bold rounded-xl shadow-sm shadow-orange-200 transition-all cursor-pointer"
                    >
                        <Printer class="w-4 h-4 mr-2" />
                        Cetak Laporan
                    </button>
                </div>
            </div>

            <!-- Print Header (Print Only) -->
            <div class="hidden print:block mb-6 text-center border-b-2 border-gray-900 pb-4">
                <h2 class="text-2xl font-black text-gray-900 uppercase tracking-wide">DOVA KITCHEN</h2>
                <h3 class="text-lg font-bold text-gray-800">Laporan Pemesanan Dapur & Rincian Pelanggan</h3>
                <p class="text-sm text-gray-600 font-medium mt-1">
                    Periode: {{ dayjs(currentWeekStart).format('DD MMMM YYYY') }} s/d {{ dayjs(currentWeekStart).add(4, 'day').format('DD MMMM YYYY') }}
                </p>
                <div class="mt-2 text-xs text-gray-500">
                    Dicetak pada: {{ dayjs().format('DD MMMM YYYY, HH:mm') }} WIB
                </div>
            </div>


            <!-- SECTION 1: RINGKASAN PORSI HARIAN (5-Day Grid for Kitchen Staff) -->
            <div class="mb-10">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-black text-gray-900 flex items-center">
                            <Utensils class="w-5 h-5 mr-2 text-orange-600" />
                            Ringkasan Porsi Dapur (Senin - Jumat)
                        </h2>
                        <p class="text-xs text-gray-500 font-medium">
                            Jumlah porsi yang harus disiapkan oleh pihak Dova Kitchen untuk setiap menu
                        </p>
                    </div>
                </div>

                <!-- 5-Column Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 print:grid-cols-5 print:gap-2">
                    <div 
                        v-for="(day, idx) in weekDates" 
                        :key="day.date" 
                        @click="scrollToDayDetail(idx)"
                        role="button"
                        tabindex="0"
                        @keydown.enter="scrollToDayDetail(idx)"
                        :class="[
                            'bg-white rounded-2xl shadow-sm border transition-all duration-200 flex flex-col overflow-hidden cursor-pointer group hover:shadow-md select-none',
                            selectedDayIndex === idx && !showAllDays 
                                ? 'border-orange-500 ring-2 ring-orange-200 shadow-md' 
                                : 'border-gray-200 hover:border-orange-400 hover:-translate-y-0.5'
                        ]"
                        :title="'Klik untuk melihat rincian pemesan hari ' + dayjs(day.date).format('dddd, DD MMMM')"
                    >
                        <!-- Day Header Card -->
                        <div class="bg-gradient-to-r from-gray-50 to-slate-50 group-hover:from-orange-50/60 group-hover:to-amber-50/40 px-4 py-3 border-b border-gray-200 flex items-center justify-between transition-colors print:bg-white print:border-b-2 print:border-gray-900">
                            <div>
                                <h3 class="font-black text-gray-900 group-hover:text-orange-600 transition-colors capitalize text-base">{{ dayjs(day.date).format('dddd') }}</h3>
                                <p class="text-xs font-semibold text-gray-500">{{ dayjs(day.date).format('DD MMMM') }}</p>
                            </div>
                            <!-- Portion count badge -->
                            <div class="text-right">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-orange-100 text-orange-800 border border-orange-200 group-hover:bg-orange-600 group-hover:text-white transition-colors shadow-xs">
                                    {{ day.total_portions || 0 }} Porsi
                                </span>
                            </div>
                        </div>
                        
                        <!-- Menu Items in Day Card -->
                        <div class="p-4 flex-1 flex flex-col justify-between">
                            <div v-if="day.menus.length === 0" class="h-full flex flex-col items-center justify-center text-center py-8 print:py-2">
                                <p class="text-xs text-gray-400 italic">Libur / Tidak ada menu</p>
                            </div>
                            
                            <div v-else class="space-y-3">
                                <div 
                                    v-for="menu in day.menus" 
                                    :key="menu.id" 
                                    class="p-2.5 bg-gray-50 group-hover:bg-orange-50/30 rounded-xl border border-gray-100 group-hover:border-orange-200/60 flex flex-col space-y-2 transition-colors print:bg-white print:border-gray-200 print:p-2"
                                >
                                    <div class="flex items-start space-x-2.5">
                                        <img 
                                            v-if="menu.photo_path" 
                                            :src="menu.photo_path" 
                                            :alt="menu.name"
                                            class="w-10 h-10 rounded-lg object-cover flex-shrink-0 border border-gray-200 print:hidden" 
                                        />
                                        <div v-else class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center text-orange-600 flex-shrink-0 print:hidden">
                                            <Utensils class="w-5 h-5" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-extrabold text-gray-900 leading-snug truncate" :title="menu.name">
                                                {{ menu.name }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Portion highlight pill -->
                                    <div class="bg-orange-50 text-orange-800 border border-orange-100 rounded-lg py-1 px-2.5 text-center flex items-center justify-between print:bg-gray-100 print:text-gray-900 print:border-gray-300">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-orange-600">Dipesan:</span>
                                        <span class="text-sm font-black text-orange-700">{{ menu.total_portions }} <span class="text-xs font-bold">porsi</span></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Action: View Customer Details for this day -->
                            <div class="mt-4 pt-3 border-t border-gray-100 print:hidden">
                                <button
                                    @click.stop="scrollToDayDetail(idx)"
                                    type="button"
                                    class="w-full flex items-center justify-center space-x-1.5 py-2 px-3 rounded-xl text-xs font-bold transition-all bg-gray-100 group-hover:bg-orange-600 text-gray-700 group-hover:text-white cursor-pointer"
                                >
                                    <Users class="w-3.5 h-3.5 text-gray-500 group-hover:text-white" />
                                    <span>Lihat Pemesan</span>
                                    <span class="ml-1 px-1.5 py-0.2 bg-white text-gray-800 group-hover:bg-orange-700 group-hover:text-white rounded-md text-[10px] font-black">
                                        {{ day.total_customers || 0 }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: RINCIAN PEMESANAN PELANGGAN PER HARI (Detailed Breakdown) -->
            <div id="rincian-pemesan-section" class="scroll-mt-8 pt-4">
                <div class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6 print:border-0 print:shadow-none print:p-0">
                    
                    <!-- Section Title & Subtitle -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between pb-6 border-b border-gray-100 gap-4">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2.5 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-black uppercase tracking-wider">
                                    Detail Pelanggan
                                </span>
                            </div>
                            <h2 class="text-2xl font-black text-gray-900 mt-1 flex items-center">
                                <Users class="w-6 h-6 mr-2 text-blue-600" />
                                Rincian Pesanan
                            </h2>
                            <p class="text-sm text-gray-500 font-medium mt-0.5">
                                Detail Informasi Pemesanan Pelanggan
                            </p>
                        </div>

                        <!-- Print Shortcut for Detailed List -->
                        <div class="flex items-center gap-2 print:hidden">
                            <button
                                @click="printReport"
                                class="inline-flex items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition-colors cursor-pointer"
                            >
                                <Printer class="w-4 h-4 mr-1.5 text-gray-500" />
                                Cetak Rincian
                            </button>
                        </div>
                    </div>

                    <!-- Day Selector Tabs (Senin - Jumat & Semua Hari) -->
                    <div class="mt-6 flex flex-wrap gap-2 print:hidden">
                        <button
                            v-for="(day, idx) in weekDates"
                            :key="day.date"
                            @click="selectedDayIndex = idx; showAllDays = false;"
                            :class="[
                                'px-4 py-2.5 rounded-2xl font-bold text-xs sm:text-sm transition-all flex items-center space-x-2 cursor-pointer',
                                selectedDayIndex === idx && !showAllDays
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-200 scale-102'
                                    : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'
                            ]"
                        >
                            <span>{{ dayjs(day.date).format('dddd') }}</span>
                            <span class="text-xs opacity-75 font-normal">({{ dayjs(day.date).format('DD MMM') }})</span>
                            <span 
                                :class="[
                                    'px-1.5 py-0.5 rounded-full text-[10px] font-black',
                                    selectedDayIndex === idx && !showAllDays 
                                        ? 'bg-blue-700 text-white' 
                                        : 'bg-gray-200 text-gray-800'
                                ]"
                            >
                                {{ day.total_customers || 0 }}
                            </span>
                        </button>

                        <!-- All Days Tab Toggle -->
                        <button
                            @click="showAllDays = true"
                            :class="[
                                'px-4 py-2.5 rounded-2xl font-bold text-xs sm:text-sm transition-all flex items-center space-x-1.5 cursor-pointer',
                                showAllDays
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-200 scale-102'
                                    : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'
                            ]"
                        >
                            <span>Semua Hari (Senin - Jumat)</span>
                            <span 
                                :class="[
                                    'px-1.5 py-0.5 rounded-full text-[10px] font-black',
                                    showAllDays 
                                        ? 'bg-blue-700 text-white' 
                                        : 'bg-gray-200 text-gray-800'
                                ]"
                            >
                                {{ totalWeeklyOrders }}
                            </span>
                        </button>
                    </div>

                    <!-- Search and Filter Bar -->
                    <div class="mt-6 flex flex-col sm:flex-row items-center gap-3 print:hidden">
                        <!-- Search Input -->
                        <div class="relative flex-1 w-full">
                            <Search class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari nama pemesan, kode pesanan, nomor telepon, atau menu..."
                                class="w-full pl-10 pr-10 py-2.5 text-xs sm:text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all font-medium text-gray-800"
                            />
                            <button 
                                v-if="searchQuery" 
                                @click="searchQuery = ''"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-0.5"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Menu Filter Dropdown -->
                        <div class="w-full sm:w-64 relative flex-shrink-0">
                            <select
                                v-model="selectedMenuFilter"
                                class="w-full py-2.5 pl-3 pr-8 text-xs sm:text-sm bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium text-gray-800 cursor-pointer"
                            >
                                <option value="all">Semua Menu</option>
                                <option v-for="menu in availableMenus" :key="menu.id" :value="menu.id">
                                    {{ menu.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Active Day Orders Display (When a single day is selected) -->
                    <div v-if="!showAllDays && activeDay" class="mt-6">
                        <!-- Day Highlight Banner -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 rounded-2xl p-4 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                            <div>
                                <h3 class="text-lg font-black text-blue-950 capitalize flex items-center">
                                    <CalendarIcon class="w-5 h-5 mr-2 text-blue-600" />
                                    Pesanan Hari {{ dayjs(activeDay.date).format('dddd, DD MMMM YYYY') }}
                                </h3>
                                <p class="text-xs text-blue-700 font-semibold mt-0.5">
                                    Daftar seluruh pelanggan yang telah membayar dan harus disiapkan makanannya pada hari ini
                                </p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="bg-white px-3 py-1 rounded-xl text-xs font-black text-blue-900 border border-blue-200 shadow-xs">
                                    {{ (filterOrders(activeDay.customer_orders) || []).length }} Pemesan
                                </span>
                                <span class="bg-blue-600 px-3 py-1 rounded-xl text-xs font-black text-white shadow-xs">
                                    {{ activeDay.total_portions || 0 }} Total Porsi
                                </span>
                            </div>
                        </div>

                        <!-- Orders List / Empty States -->
                        <div v-if="!activeDay.customer_orders || activeDay.customer_orders.length === 0" class="text-center py-12 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                            <ShoppingBag class="w-12 h-12 text-gray-300 mx-auto mb-2" />
                            <h4 class="text-sm font-bold text-gray-700">Tidak ada pesanan untuk hari ini</h4>
                            <p class="text-xs text-gray-400 mt-1">Belum ada pelanggan yang memesan atau pembayaran belum berstatus 'paid'.</p>
                        </div>

                        <div v-else-if="filterOrders(activeDay.customer_orders).length === 0" class="text-center py-12 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                            <Search class="w-10 h-10 text-gray-300 mx-auto mb-2" />
                            <h4 class="text-sm font-bold text-gray-700">Tidak ada data pemesan yang cocok</h4>
                            <p class="text-xs text-gray-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter menu Anda.</p>
                            <button @click="searchQuery = ''; selectedMenuFilter = 'all';" class="mt-3 text-xs font-bold text-blue-600 hover:underline">
                                Reset Filter
                            </button>
                        </div>

                        <!-- Compact Table / List of Customer Names -->
                        <div v-else class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50/80">
                                        <tr>
                                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-12">No</th>
                                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Pemesan</th>
                                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No. Telepon</th>
                                            <th scope="col" class="px-5 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Chat WA</th>
                                            <th scope="col" class="px-5 py-3.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kode Order</th>
                                            <th scope="col" class="px-5 py-3.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Total Porsi</th>
                                            <th scope="col" class="px-5 py-3.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        <tr 
                                            v-for="(order, idx) in filterOrders(activeDay.customer_orders)" 
                                            :key="order.order_id || idx"
                                            @click="openOrderDetailModal(order, activeDay)"
                                            class="hover:bg-blue-50/60 transition-colors cursor-pointer group"
                                        >
                                            <td class="px-5 py-4 whitespace-nowrap text-xs font-semibold text-gray-400">
                                                {{ idx + 1 }}
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-xs flex-shrink-0">
                                                        {{ (order.customer.name || 'P').charAt(0).toUpperCase() }}
                                                    </div>
                                                    <div>
                                                        <div class="flex items-center space-x-2">
                                                            <span class="font-bold text-sm text-gray-900 group-hover:text-blue-600 transition-colors">
                                                                {{ order.customer.name }}
                                                            </span>
                                                            <span 
                                                                v-if="order.customer.allergens && order.customer.allergens.length > 0" 
                                                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700"
                                                                title="Memiliki Pantangan Alergi"
                                                            >
                                                                <AlertTriangle class="w-3 h-3 mr-0.5 text-red-600" />
                                                                Alergi
                                                            </span>
                                                        </div>
                                                        <span class="text-xs text-gray-400 font-normal block">{{ order.customer.email || 'Tanpa email' }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap text-xs font-medium text-gray-700">
                                                {{ order.customer.phone || '-' }}
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                                <a 
                                                    v-if="order.customer.phone" 
                                                    :href="getWhatsAppUrl(order.customer.phone, order.customer.name, order.order_code)" 
                                                    target="_blank" 
                                                    rel="noopener noreferrer" 
                                                    @click.stop
                                                    class="inline-flex items-center px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white border border-emerald-200 rounded-xl text-xs font-bold transition-all shadow-2xs hover:shadow-xs"
                                                    title="Hubungi Pelanggan via WhatsApp"
                                                >
                                                    💬 Hubungi WA
                                                </a>
                                                <span v-else class="text-xs text-gray-400 italic">-</span>
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-mono font-bold bg-gray-100 text-gray-800">
                                                    #{{ order.order_code }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-black bg-orange-100 text-orange-900">
                                                    {{ order.total_portions }} Porsi
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 whitespace-nowrap text-right">
                                                <button 
                                                    @click.stop="openOrderDetailModal(order, activeDay)"
                                                    type="button"
                                                    class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer group-hover:bg-blue-600 group-hover:text-white"
                                                >
                                                    <Eye class="w-3.5 h-3.5 mr-1" />
                                                    <span>Lihat Detail</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- All Days Display (When "Semua Hari" is clicked) -->
                    <div v-else-if="showAllDays" class="mt-6 space-y-6">
                        <div v-for="day in weekDates" :key="day.date" class="border border-gray-200 rounded-2xl overflow-hidden bg-white shadow-xs">
                            <!-- Day Section Header -->
                            <div class="bg-gray-100/80 px-5 py-3 border-b border-gray-200 flex items-center justify-between">
                                <h3 class="font-black text-gray-900 capitalize text-sm sm:text-base flex items-center">
                                    <CalendarIcon class="w-4 h-4 mr-2 text-blue-600" />
                                    {{ dayjs(day.date).format('dddd, DD MMMM YYYY') }}
                                </h3>
                                <div class="flex items-center space-x-2 text-xs font-bold text-gray-700">
                                    <span>{{ day.total_customers || 0 }} Pemesan</span>
                                    <span>•</span>
                                    <span class="text-orange-600">{{ day.total_portions || 0 }} Porsi</span>
                                </div>
                            </div>

                            <div v-if="!day.customer_orders || day.customer_orders.length === 0" class="py-6 text-center text-xs text-gray-400 italic">
                                Tidak ada pesanan untuk hari ini
                            </div>

                            <div v-else class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50/50">
                                        <tr>
                                            <th scope="col" class="px-5 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-12">No</th>
                                            <th scope="col" class="px-5 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Pemesan</th>
                                            <th scope="col" class="px-5 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No. Telepon</th>
                                            <th scope="col" class="px-5 py-2.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Chat WA</th>
                                            <th scope="col" class="px-5 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kode Order</th>
                                            <th scope="col" class="px-5 py-2.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Total Porsi</th>
                                            <th scope="col" class="px-5 py-2.5 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        <tr 
                                            v-for="(order, idx) in filterOrders(day.customer_orders)" 
                                            :key="order.order_id || idx"
                                            @click="openOrderDetailModal(order, day)"
                                            class="hover:bg-blue-50/60 transition-colors cursor-pointer group"
                                        >
                                            <td class="px-5 py-3 whitespace-nowrap text-xs font-semibold text-gray-400">
                                                {{ idx + 1 }}
                                            </td>
                                            <td class="px-5 py-3 whitespace-nowrap">
                                                <div class="flex items-center space-x-2.5">
                                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-white font-bold text-xs shadow-xs flex-shrink-0">
                                                        {{ (order.customer.name || 'P').charAt(0).toUpperCase() }}
                                                    </div>
                                                    <span class="font-bold text-sm text-gray-900 group-hover:text-blue-600 transition-colors">
                                                        {{ order.customer.name }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-5 py-3 whitespace-nowrap text-xs text-gray-700 font-medium">
                                                {{ order.customer.phone || '-' }}
                                            </td>
                                            <td class="px-5 py-3 whitespace-nowrap text-center">
                                                <a 
                                                    v-if="order.customer.phone" 
                                                    :href="getWhatsAppUrl(order.customer.phone, order.customer.name, order.order_code)" 
                                                    target="_blank" 
                                                    rel="noopener noreferrer" 
                                                    @click.stop
                                                    class="inline-flex items-center px-2.5 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white border border-emerald-200 rounded-lg text-xs font-bold transition-all shadow-2xs hover:shadow-xs"
                                                    title="Hubungi Pelanggan via WhatsApp"
                                                >
                                                    💬 Hubungi WA
                                                </a>
                                                <span v-else class="text-xs text-gray-400 italic">-</span>
                                            </td>
                                            <td class="px-5 py-3 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono font-bold bg-gray-100 text-gray-800">
                                                    #{{ order.order_code }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3 whitespace-nowrap text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-black bg-orange-100 text-orange-900">
                                                    {{ order.total_portions }} Porsi
                                                </span>
                                            </td>
                                            <td class="px-5 py-3 whitespace-nowrap text-right">
                                                <button 
                                                    @click.stop="openOrderDetailModal(order, day)"
                                                    type="button"
                                                    class="inline-flex items-center px-2.5 py-1 bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white rounded-lg text-xs font-bold transition-all shadow-xs cursor-pointer group-hover:bg-blue-600 group-hover:text-white"
                                                >
                                                    <Eye class="w-3.5 h-3.5 mr-1" />
                                                    <span>Lihat Detail</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Modal Detail Pesanan Pelanggan -->
            <div v-if="isDetailModalOpen && selectedOrder" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity" @click="closeDetailModal"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                    <!-- Modal Card -->
                    <div class="relative z-10 inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-gray-100">
                        <!-- Modal Header -->
                        <div class="bg-gradient-to-r from-gray-50 to-slate-50 px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white flex items-center justify-center font-black text-xl shadow-sm flex-shrink-0">
                                    {{ (selectedOrder.customer?.name || 'P').charAt(0).toUpperCase() }}
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-lg font-black text-gray-900 leading-tight">
                                            {{ selectedOrder.customer?.name }}
                                        </h3>
                                        <span class="px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-gray-200 text-gray-800">
                                            #{{ selectedOrder.order_code }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 font-medium mt-0.5">
                                        Layanan: {{ selectedOrderDay ? dayjs(selectedOrderDay.date).format('dddd, DD MMMM YYYY') : '-' }}
                                    </p>
                                </div>
                            </div>
                            <button 
                                @click="closeDetailModal"
                                class="text-gray-400 hover:text-gray-600 p-1.5 rounded-full hover:bg-gray-200/60 transition-colors cursor-pointer"
                            >
                                <X class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 space-y-5">
                            <!-- Customer Info Bar -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-gray-50 p-3.5 rounded-2xl border border-gray-100 text-xs">
                                <div>
                                    <span class="text-gray-400 font-semibold uppercase tracking-wider text-[10px] block">No. Handphone:</span>
                                    <div class="flex items-center space-x-2 mt-0.5">
                                        <span class="font-bold text-gray-800">{{ selectedOrder.customer?.phone || '-' }}</span>
                                        <a 
                                            v-if="selectedOrder.customer?.phone"
                                            :href="getWhatsAppUrl(selectedOrder.customer?.phone, selectedOrder.customer?.name, selectedOrder.order_code)"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center text-[11px] font-bold text-emerald-600 hover:underline"
                                        >
                                            💬 Chat WA
                                        </a>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-gray-400 font-semibold uppercase tracking-wider text-[10px] block">Email:</span>
                                    <span class="font-bold text-gray-800 mt-0.5 block truncate">{{ selectedOrder.customer?.email || '-' }}</span>
                                </div>
                            </div>

                            <!-- Allergen Alert if customer has allergens -->
                            <div 
                                v-if="selectedOrder.customer?.allergens && selectedOrder.customer.allergens.length > 0"
                                class="p-3 bg-red-50 border border-red-200 rounded-2xl flex items-start space-x-2 text-xs text-red-800"
                            >
                                <AlertTriangle class="w-4 h-4 text-red-600 flex-shrink-0 mt-0.5" />
                                <div>
                                    <span class="font-black">Catatan Pantangan Alergi:</span>
                                    <p class="font-medium mt-0.5">{{ selectedOrder.customer.allergens.join(', ') }}</p>
                                </div>
                            </div>

                            <!-- Detail Menu Table -->
                            <div>
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2.5 flex items-center">
                                    <Utensils class="w-3.5 h-3.5 mr-1 text-orange-500" />
                                    Menu & Porsi yang Dipesan
                                </h4>
                                <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-2xs">
                                    <table class="min-w-full divide-y divide-gray-100 text-xs">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2.5 text-left font-bold text-gray-500">Menu</th>
                                                <th class="px-4 py-2.5 text-center font-bold text-gray-500">Porsi</th>
                                                <th class="px-4 py-2.5 text-right font-bold text-gray-500">Harga</th>
                                                <th class="px-4 py-2.5 text-right font-bold text-gray-500">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="item in selectedOrder.items" :key="item.id" class="hover:bg-gray-50/50">
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center space-x-2.5">
                                                        <img 
                                                            v-if="item.menu_photo" 
                                                            :src="item.menu_photo" 
                                                            :alt="item.menu_name"
                                                            class="w-9 h-9 rounded-lg object-cover border border-gray-100" 
                                                        />
                                                        <div v-else class="w-9 h-9 rounded-lg bg-orange-100 flex items-center justify-center text-orange-600">
                                                            <Utensils class="w-4 h-4" />
                                                        </div>
                                                        <span class="font-bold text-gray-900">{{ item.menu_name }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <span class="inline-flex items-center px-2 py-0.5 bg-orange-100 text-orange-900 rounded-md font-black text-xs">
                                                        {{ item.portion_quantity }} porsi
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right text-gray-600 font-medium">
                                                    {{ formatPrice(item.unit_price) }}
                                                </td>
                                                <td class="px-4 py-3 text-right font-bold text-gray-900">
                                                    {{ formatPrice(item.subtotal) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot class="bg-gray-50/80 font-bold border-t border-gray-200">
                                            <tr>
                                                <td class="px-4 py-2.5 text-gray-700">Total Porsi:</td>
                                                <td class="px-4 py-2.5 text-center text-orange-600 font-black">
                                                    {{ selectedOrder.total_portions }} Porsi
                                                </td>
                                                <td class="px-4 py-2.5 text-right text-gray-700">Total:</td>
                                                <td class="px-4 py-2.5 text-right text-gray-900 font-black">
                                                    {{ formatPrice(selectedOrder.items?.reduce((a, b) => a + (b.subtotal || 0), 0)) }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-end">
                            <button 
                                type="button" 
                                @click="closeDetailModal"
                                class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-xs font-bold rounded-xl transition-colors cursor-pointer"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRINT ONLY: Clean Comprehensive Printable Table of All Customers Per Day -->
            <div class="hidden print:block mt-8">
                <h3 class="text-lg font-black text-gray-900 mb-4 pb-1 border-b border-gray-800">
                    RINCIAN DATA PEMESAN HARIAN
                </h3>
                
                <div v-for="day in weekDates" :key="'print-' + day.date" class="mb-6 page-break-inside-avoid">
                    <div class="bg-gray-200 px-3 py-1.5 font-black text-sm text-gray-900 flex justify-between border border-gray-400">
                        <span>{{ dayjs(day.date).format('dddd, DD MMMM YYYY') }}</span>
                        <span>Total: {{ day.total_portions || 0 }} Porsi ({{ day.total_customers || 0 }} Pemesan)</span>
                    </div>

                    <table class="min-w-full border-collapse border border-gray-400 text-xs mt-1">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border border-gray-400 px-2 py-1 text-left w-8">No</th>
                                <th class="border border-gray-400 px-2 py-1 text-left w-48">Nama Pemesan</th>
                                <th class="border border-gray-400 px-2 py-1 text-left w-28">No. Telepon</th>
                                <th class="border border-gray-400 px-2 py-1 text-left w-28">Kode Order</th>
                                <th class="border border-gray-400 px-2 py-1 text-left">Detail Menu & Jumlah Porsi</th>
                                <th class="border border-gray-400 px-2 py-1 text-center w-20">Total Porsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!day.customer_orders || day.customer_orders.length === 0">
                                <td colspan="6" class="border border-gray-400 px-2 py-3 text-center italic text-gray-500">
                                    Tidak ada pesanan
                                </td>
                            </tr>
                            <tr v-for="(order, pIdx) in day.customer_orders" :key="'p-ord-' + (order.order_id || pIdx)">
                                <td class="border border-gray-400 px-2 py-1 text-center">{{ pIdx + 1 }}</td>
                                <td class="border border-gray-400 px-2 py-1 font-bold">
                                    {{ order.customer.name }}
                                    <div v-if="order.customer.allergens && order.customer.allergens.length > 0" class="text-[10px] text-red-600 font-semibold">
                                        (Alergi: {{ order.customer.allergens.join(', ') }})
                                    </div>
                                </td>
                                <td class="border border-gray-400 px-2 py-1">{{ order.customer.phone || '-' }}</td>
                                <td class="border border-gray-400 px-2 py-1 font-mono text-[10px]">{{ order.order_code }}</td>
                                <td class="border border-gray-400 px-2 py-1">
                                    <div v-for="it in order.items" :key="it.id" class="text-[11px]">
                                        • <strong>{{ it.portion_quantity }}x</strong> {{ it.menu_name }}
                                    </div>
                                </td>
                                <td class="border border-gray-400 px-2 py-1 text-center font-black">{{ order.total_portions }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Print Footer -->
            <div class="hidden print:block mt-8 text-xs text-gray-500 text-center border-t border-gray-300 pt-3">
                Dova Kitchen • Dokumen Internal Dapur & Pemesanan • Dicetak otomatis dari sistem
            </div>

        </div>
    </AdminLayout>
</template>

<style>
@media print {
    body {
        background-color: white !important;
        font-size: 11pt !important;
    }
    aside, header, nav, .md\:ml-64, .pt-16 {
        display: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    main {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    .page-break-inside-avoid {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>
