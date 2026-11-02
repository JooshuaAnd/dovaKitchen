<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { 
    Calendar as CalendarIcon, 
    ChevronLeft, 
    ChevronRight, 
    Plus, 
    X, 
    Utensils, 
    Search,
    Check,
    CheckCircle2,
    CalendarDays
} from 'lucide-vue-next';
import dayjs from 'dayjs';
import 'dayjs/locale/id';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

dayjs.locale('id');

const props = defineProps({
    weekDates: {
        type: Array,
        default: () => []
    },
    availableMenus: {
        type: Array,
        default: () => []
    },
    currentWeekStart: String
});

const getNextWeekUrl = () => {
    return route('admin.weekly-management.index', { date: dayjs(props.currentWeekStart).add(7, 'day').format('YYYY-MM-DD') });
};

const getPrevWeekUrl = () => {
    return route('admin.weekly-management.index', { date: dayjs(props.currentWeekStart).subtract(7, 'day').format('YYYY-MM-DD') });
};

const getTodayUrl = () => {
    return route('admin.weekly-management.index', { date: dayjs().format('YYYY-MM-DD') });
};

const isModalOpen = ref(false);
const activeDate = ref(null);
const activeDayName = ref('');
const selectedMenuIds = ref([]);
const modalSearchQuery = ref('');

const openModal = (date, dayName, currentMenus) => {
    activeDate.value = date;
    activeDayName.value = dayName;
    selectedMenuIds.value = (currentMenus || []).map(m => m.id);
    modalSearchQuery.value = '';
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => {
        activeDate.value = null;
        activeDayName.value = '';
        selectedMenuIds.value = [];
        modalSearchQuery.value = '';
    }, 150);
};

const toggleMenu = (menuId) => {
    const index = selectedMenuIds.value.indexOf(menuId);
    if (index > -1) {
        selectedMenuIds.value.splice(index, 1);
    } else {
        selectedMenuIds.value.push(menuId);
    }
};

const selectAllMenus = () => {
    selectedMenuIds.value = filteredAvailableMenus.value.map(m => m.id);
};

const clearAllMenus = () => {
    selectedMenuIds.value = [];
};

// Filtered available menus inside modal
const filteredAvailableMenus = computed(() => {
    if (!modalSearchQuery.value.trim()) {
        return props.availableMenus;
    }
    const q = modalSearchQuery.value.toLowerCase().trim();
    return props.availableMenus.filter(m => {
        return m.name?.toLowerCase().includes(q) || 
               m.description?.toLowerCase().includes(q) ||
               m.allergens?.some(a => a.name?.toLowerCase().includes(q));
    });
});

const formatPrice = (price) => {
    if (!price && price !== 0) return '-';
    return new Intl.NumberFormat('id-ID', { 
        style: 'currency', 
        currency: 'IDR', 
        maximumFractionDigits: 0 
    }).format(price);
};

const form = useForm({
    date: '',
    menu_ids: []
});

const saveSchedule = () => {
    form.date = activeDate.value;
    form.menu_ids = selectedMenuIds.value;
    form.post(route('admin.weekly-management.update'), {
        preserveScroll: true,
        onSuccess: () => {
            const savedDayName = activeDayName.value;
            closeModal();
            Swal.fire({
                title: 'Jadwal Tersimpan',
                text: `Jadwal menu untuk hari ${savedDayName} berhasil diperbarui.`,
                icon: 'success',
                iconColor: '#111827',
                confirmButtonColor: '#111827',
                timer: 2000,
                timerProgressBar: true,
                customClass: {
                    popup: 'rounded-2xl border border-gray-300 shadow-xl',
                    confirmButton: 'rounded-xl px-5 py-2.5 font-bold text-sm'
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Manajemen Menu Mingguan" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div>
                    <div class="flex items-center space-x-3 mb-1">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 border border-gray-300 flex items-center justify-center text-gray-800 shadow-2xs">
                            <CalendarDays class="w-5 h-5" />
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">
                            Manajemen Menu Mingguan
                        </h1>
                    </div>
                    <p class="text-xs text-gray-500 font-medium">
                        Atur katalog menu yang disajikan dan tersedia untuk pre-order setiap hari kerja (Senin - Jumat).
                    </p>
                </div>
                
                <!-- Action Controls / Week Navigator -->
                <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                    <!-- Week Range Switcher -->
                    <div class="flex items-center bg-white px-2.5 py-1 rounded-xl border border-gray-300 shadow-2xs">
                        <Link :href="getPrevWeekUrl()" class="p-1.5 text-gray-500 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition-colors" title="Minggu Sebelumnya">
                            <ChevronLeft class="w-4 h-4" />
                        </Link>
                        <div class="text-xs sm:text-sm font-bold text-gray-800 px-3 flex items-center whitespace-nowrap">
                            <CalendarIcon class="w-3.5 h-3.5 mr-2 text-gray-500" />
                            {{ dayjs(currentWeekStart).format('DD MMM') }} - {{ dayjs(currentWeekStart).add(4, 'day').format('DD MMM YYYY') }}
                        </div>
                        <Link :href="getNextWeekUrl()" class="p-1.5 text-gray-500 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition-colors" title="Minggu Berikutnya">
                            <ChevronRight class="w-4 h-4" />
                        </Link>
                    </div>

                    <!-- Quick "Hari Ini" Button -->
                    <Link :href="getTodayUrl()" class="px-3.5 py-2 bg-white hover:bg-gray-100 text-gray-700 text-xs font-bold rounded-xl border border-gray-300 transition-colors shadow-2xs">
                        Hari Ini
                    </Link>
                </div>
            </div>

            <!-- 5-Day Weekly Columns (Senin - Jumat) -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div 
                    v-for="day in weekDates" 
                    :key="day.date" 
                    class="bg-white rounded-xl shadow-2xs border border-gray-300 overflow-hidden flex flex-col hover:border-gray-400 transition-all duration-200"
                >
                    <!-- Day Column Header -->
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-gray-900 capitalize text-sm">{{ day.day_name }}</h3>
                            <p class="text-[11px] font-medium text-gray-500">{{ dayjs(day.date).format('DD MMMM') }}</p>
                        </div>
                        
                        <!-- Manage Button -->
                        <button 
                            @click="openModal(day.date, day.day_name, day.menus)" 
                            class="inline-flex items-center px-2.5 py-1.5 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-lg shadow-2xs transition-all cursor-pointer" 
                            title="Atur Menu untuk hari ini"
                        >
                            <Plus class="w-3.5 h-3.5 mr-1" />
                            Atur
                        </button>
                    </div>

                    <!-- Day Content: Menu List -->
                    <div class="p-3.5 flex-1 flex flex-col justify-between">
                        <!-- Empty State -->
                        <div v-if="!day.menus || day.menus.length === 0" class="h-full flex flex-col items-center justify-center text-center py-8">
                            <Utensils class="w-8 h-8 text-gray-300 mb-1.5" />
                            <p class="text-xs text-gray-400 font-medium italic">Belum ada menu</p>
                            <button 
                                @click="openModal(day.date, day.day_name, day.menus)"
                                class="mt-2 text-xs font-bold text-gray-800 hover:text-black underline cursor-pointer"
                            >
                                + Tambahkan menu
                            </button>
                        </div>

                        <!-- Active Menus List -->
                        <div v-else class="space-y-2.5">
                            <div 
                                v-for="menu in day.menus" 
                                :key="menu.id" 
                                class="p-2.5 bg-gray-50 rounded-lg border border-gray-200 flex items-start space-x-2.5 hover:bg-gray-100/70 transition-colors"
                            >
                                <!-- Menu Photo Thumbnail -->
                                <div class="w-10 h-10 rounded-md overflow-hidden bg-gray-200 border border-gray-200 flex items-center justify-center flex-shrink-0">
                                    <img 
                                        v-if="menu.photo_path" 
                                        :src="menu.photo_path" 
                                        :alt="menu.name" 
                                        class="w-full h-full object-cover" 
                                    />
                                    <Utensils v-else class="w-4 h-4 text-gray-400" />
                                </div>

                                <!-- Menu Details -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-gray-900 truncate" :title="menu.name">
                                        {{ menu.name }}
                                    </p>
                                    <p class="text-[11px] font-semibold text-gray-700 mt-0.5">
                                        {{ formatPrice(menu.price) }}
                                    </p>

                                    <!-- Allergen tags preview -->
                                    <div v-if="menu.allergens && menu.allergens.length > 0" class="flex flex-wrap gap-1 mt-1">
                                        <span 
                                            v-for="a in menu.allergens.slice(0, 2)" 
                                            :key="a.id"
                                            class="inline-block px-1.5 py-0.2 text-[9px] font-medium bg-gray-200 text-gray-700 rounded"
                                        >
                                            {{ a.name }}
                                        </span>
                                        <span v-if="menu.allergens.length > 2" class="text-[9px] text-gray-400 font-medium">
                                            +{{ menu.allergens.length - 2 }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Menu counter footer -->
                        <div v-if="day.menus && day.menus.length > 0" class="mt-3 pt-2 border-t border-gray-100 flex items-center justify-between text-[11px] text-gray-500 font-medium">
                            <span>Total varian</span>
                            <span class="font-bold text-gray-800">{{ day.menus.length }} menu</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Kelola Menu Modal (Monochromatic Clean Design) -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity" @click="closeModal"></div>
            
            <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-300 w-full max-w-2xl max-h-[90vh] flex flex-col z-10 animate-in fade-in zoom-in-95 duration-150">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <div>
                        <h2 class="text-lg font-black text-gray-900 tracking-tight flex items-center">
                            <CalendarIcon class="w-4 h-4 mr-2 text-gray-700" />
                            Atur Menu: {{ activeDayName }}
                        </h2>
                        <p class="text-xs text-gray-500 font-medium mt-0.5">
                            {{ dayjs(activeDate).format('DD MMMM YYYY') }} — Pilih menu yang disajikan pada hari ini
                        </p>
                    </div>
                    <button 
                        @click="closeModal" 
                        class="p-1.5 text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Toolbar: Search & Quick Selection -->
                <div class="px-6 py-3 bg-gray-50 border-b border-gray-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <input 
                            v-model="modalSearchQuery" 
                            type="text" 
                            placeholder="Cari menu untuk ditambahkan..."
                            class="w-full pl-8 pr-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-gray-900 focus:border-gray-900"
                        />
                        <Search class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" />
                        <button 
                            v-if="modalSearchQuery" 
                            @click="modalSearchQuery = ''"
                            class="absolute right-2.5 top-2 text-gray-400 hover:text-gray-600 text-xs font-bold"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Bulk Actions -->
                    <div class="flex items-center space-x-2 text-xs">
                        <button 
                            type="button" 
                            @click="selectAllMenus" 
                            class="text-gray-800 hover:text-black font-bold cursor-pointer underline"
                        >
                            Pilih Semua
                        </button>
                        <span class="text-gray-300">|</span>
                        <button 
                            type="button" 
                            @click="clearAllMenus" 
                            class="text-gray-500 hover:text-gray-800 font-medium cursor-pointer"
                        >
                            Kosongkan
                        </button>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-gray-200 text-gray-800 ml-2">
                            {{ selectedMenuIds.length }} dipilih
                        </span>
                    </div>
                </div>
                
                <!-- Modal Body: Available Menus Grid -->
                <div class="p-6 overflow-y-auto flex-1 max-h-[55vh]">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div 
                            v-for="menu in filteredAvailableMenus" 
                            :key="menu.id"
                            @click="toggleMenu(menu.id)"
                            :class="[
                                'flex items-start space-x-3.5 p-3 rounded-xl border transition-all cursor-pointer select-none',
                                selectedMenuIds.includes(menu.id) 
                                    ? 'border-gray-900 bg-gray-50 ring-2 ring-gray-900/10 shadow-2xs' 
                                    : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50/50'
                            ]"
                        >
                            <!-- Thumbnail -->
                            <div class="w-14 h-14 rounded-lg overflow-hidden bg-gray-100 border border-gray-200 flex items-center justify-center flex-shrink-0">
                                <img 
                                    v-if="menu.photo_path" 
                                    :src="menu.photo_path" 
                                    :alt="menu.name" 
                                    class="w-full h-full object-cover" 
                                />
                                <Utensils v-else class="w-5 h-5 text-gray-400" />
                            </div>

                            <!-- Details -->
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-bold text-gray-900 truncate">{{ menu.name }}</p>
                                <p class="text-xs font-semibold text-gray-700 mt-0.5">{{ formatPrice(menu.price) }}</p>
                                <p v-if="menu.description" class="text-[11px] text-gray-500 mt-1 line-clamp-1">{{ menu.description }}</p>
                                
                                <div v-if="menu.allergens && menu.allergens.length > 0" class="flex flex-wrap gap-1 mt-1.5">
                                    <span 
                                        v-for="a in menu.allergens.slice(0, 2)" 
                                        :key="a.id"
                                        class="inline-block px-1.5 py-0.2 text-[9px] font-medium bg-gray-200 text-gray-700 rounded"
                                    >
                                        {{ a.name }}
                                    </span>
                                </div>
                            </div>

                            <!-- Checkbox icon -->
                            <div class="flex-shrink-0 mt-0.5">
                                <div 
                                    :class="[
                                        'w-5 h-5 rounded-md border flex items-center justify-center transition-colors',
                                        selectedMenuIds.includes(menu.id) 
                                            ? 'bg-gray-900 border-gray-900 text-white' 
                                            : 'border-gray-300 bg-white'
                                    ]"
                                >
                                    <Check v-if="selectedMenuIds.includes(menu.id)" class="w-3.5 h-3.5" />
                                </div>
                            </div>
                        </div>

                        <!-- Empty search state -->
                        <div v-if="filteredAvailableMenus.length === 0" class="col-span-full py-10 text-center">
                            <Utensils class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                            <p class="text-xs font-bold text-gray-700">Tidak ada menu yang sesuai</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Coba ubah kata kunci pencarian</p>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50 rounded-b-2xl">
                    <span class="text-xs text-gray-500 font-medium">
                        {{ selectedMenuIds.length }} menu dipilih untuk {{ activeDayName }}
                    </span>

                    <div class="flex items-center space-x-2.5">
                        <button 
                            @click="closeModal" 
                            type="button" 
                            class="px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-200 bg-white border border-gray-300 rounded-xl transition-colors cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            @click="saveSchedule" 
                            :disabled="form.processing" 
                            type="button" 
                            class="inline-flex items-center px-5 py-2 bg-gray-900 hover:bg-black text-white text-xs font-bold rounded-xl shadow-xs focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 transition-all disabled:opacity-50 cursor-pointer"
                        >
                            <span v-if="form.processing" class="w-3.5 h-3.5 mr-1.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <CheckCircle2 v-else class="w-3.5 h-3.5 mr-1.5" />
                            Simpan Jadwal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
