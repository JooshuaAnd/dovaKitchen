<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { 
    Plus, 
    Edit2, 
    Trash2, 
    Utensils, 
    Search, 
    ChevronLeft, 
    ChevronRight,
    CheckCircle2,
    XCircle,
    Info
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

const props = defineProps({
    menus: {
        type: Array,
        default: () => []
    }
});

const form = useForm({});

// Search & Pagination State
const searchQuery = ref('');
const perPage = ref(10);
const currentPage = ref(1);

// Currency Formatter
const formatPrice = (price) => {
    if (!price && price !== 0) return '-';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(price);
};

// Filtered Menus
const filteredMenus = computed(() => {
    let result = [...props.menus];

    // Search filter
    if (searchQuery.value.trim() !== '') {
        const q = searchQuery.value.toLowerCase().trim();
        result = result.filter(menu => {
            const nameMatch = menu.name?.toLowerCase().includes(q);
            const descMatch = menu.description?.toLowerCase().includes(q);
            const allergenMatch = menu.allergens?.some(a => a.name?.toLowerCase().includes(q));
            return nameMatch || descMatch || allergenMatch;
        });
    }

    return result;
});

// Paginated Menus
const totalPages = computed(() => {
    if (perPage.value === 'all') return 1;
    return Math.max(1, Math.ceil(filteredMenus.value.length / Number(perPage.value)));
});

const paginatedMenus = computed(() => {
    if (perPage.value === 'all') return filteredMenus.value;
    const limit = Number(perPage.value);
    const start = (currentPage.value - 1) * limit;
    return filteredMenus.value.slice(start, start + limit);
});

const startItemIndex = computed(() => {
    if (filteredMenus.value.length === 0) return 0;
    if (perPage.value === 'all') return 1;
    return (currentPage.value - 1) * Number(perPage.value) + 1;
});

const endItemIndex = computed(() => {
    if (perPage.value === 'all') return filteredMenus.value.length;
    return Math.min(currentPage.value * Number(perPage.value), filteredMenus.value.length);
});

// Delete Menu with SweetAlert2 Confirmation
const confirmDeleteMenu = (menu) => {
    Swal.fire({
        title: 'Hapus Menu?',
        html: `Apakah Anda yakin ingin menghapus menu <strong class="text-gray-900 font-bold">"${menu.name}"</strong>?<br><span class="text-xs text-gray-500 mt-1 block">Tindakan ini tidak dapat dibatalkan.</span>`,
        icon: 'warning',
        iconColor: '#4b5563',
        showCancelButton: true,
        confirmButtonColor: '#111827',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        customClass: {
            popup: 'rounded-2xl border border-gray-300 shadow-xl',
            confirmButton: 'rounded-xl px-5 py-2.5 font-bold text-sm',
            cancelButton: 'rounded-xl px-5 py-2.5 font-bold text-sm'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            form.delete(route('admin.menus.destroy', menu.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Berhasil Dihapus',
                        text: `Menu "${menu.name}" telah berhasil dihapus.`,
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
    <Head title="Manajemen Menu" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <div class="flex items-center space-x-3 mb-1">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 border border-gray-300 flex items-center justify-center text-gray-800 shadow-2xs">
                            <Utensils class="w-5 h-5" />
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">
                            Manajemen Menu
                        </h1>
                    </div>
                    <p class="text-xs text-gray-500 font-medium">
                        Kelola katalog makanan, harga, ketersediaan, serta bahan baku & pantangan dietary.
                    </p>
                </div>

                <!-- Add Menu Button -->
                <Link 
                    :href="route('admin.menus.create')" 
                    class="inline-flex items-center px-4 py-2.5 bg-gray-900 hover:bg-black text-white text-xs sm:text-sm font-bold rounded-xl shadow-xs transition-all cursor-pointer"
                >
                    <Plus class="w-4 h-4 mr-1.5" />
                    Tambah Menu
                </Link>
            </div>

            <!-- Horizontal Toolbar: Items per page & Search -->
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

                    <!-- Right: Search -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <!-- Search Input -->
                        <div class="relative min-w-[240px] sm:w-72">
                            <input 
                                v-model="searchQuery"
                                @input="currentPage = 1"
                                type="text"
                                placeholder="Cari nama menu / bahan..."
                                class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-300 rounded-xl text-xs text-gray-900 placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-gray-900 focus:border-gray-900 transition-all font-medium"
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

            <!-- Menu Table Card -->
            <div class="bg-white rounded-xl shadow-2xs border border-gray-300 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                                    Menu
                                </th>
                                <th scope="col" class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                                    Harga
                                </th>
                                <th scope="col" class="px-6 py-3.5 text-left text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                                    Bahan Baku / Pantangan
                                </th>
                                <th scope="col" class="px-6 py-3.5 text-right text-[11px] font-bold text-gray-600 uppercase tracking-wider">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr 
                                v-for="menu in paginatedMenus" 
                                :key="menu.id" 
                                class="hover:bg-gray-50/80 transition-colors"
                            >
                                <!-- Menu Photo & Name -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3.5">
                                        <div class="flex-shrink-0 w-12 h-12 rounded-lg overflow-hidden border border-gray-200 bg-gray-100 flex items-center justify-center">
                                            <img 
                                                v-if="menu.photo_path"
                                                class="w-full h-full object-cover" 
                                                :src="menu.photo_path" 
                                                :alt="menu.name" 
                                            />
                                            <Utensils v-else class="w-5 h-5 text-gray-400" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-bold text-gray-900 truncate">
                                                {{ menu.name }}
                                            </div>
                                            <div v-if="menu.description" class="text-xs text-gray-500 line-clamp-1 max-w-sm font-normal">
                                                {{ menu.description }}
                                            </div>
                                            <div v-else class="text-xs text-gray-400 italic">
                                                Tanpa deskripsi
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Price -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold text-gray-900">
                                        {{ formatPrice(menu.price) }}
                                    </span>
                                </td>

                                <!-- Allergen / Ingredients List -->
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5 max-w-md">
                                        <span 
                                            v-for="a in (menu.allergens || [])" 
                                            :key="a.id" 
                                            class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200"
                                        >
                                            {{ a.name }}
                                        </span>
                                        <span 
                                            v-if="!menu.allergens || menu.allergens.length === 0" 
                                            class="text-xs text-gray-400 italic"
                                        >
                                            Tidak ada bahan khusus
                                        </span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <!-- Edit -->
                                        <Link 
                                            :href="route('admin.menus.edit', menu.id)" 
                                            class="p-2 text-gray-600 hover:text-black hover:bg-gray-100 rounded-lg border border-gray-200 transition-colors shadow-2xs" 
                                            title="Edit Menu"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </Link>

                                        <!-- Delete -->
                                        <button 
                                            @click="confirmDeleteMenu(menu)" 
                                            class="p-2 text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-lg border border-gray-200 transition-colors shadow-2xs cursor-pointer" 
                                            title="Hapus Menu"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredMenus.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <Utensils class="w-10 h-10 text-gray-300 mb-2" />
                                        <p class="text-sm font-bold text-gray-700">Tidak ada menu ditemukan</p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            {{ searchQuery ? 'Coba ubah kata kunci pencarian.' : 'Mulai dengan menambahkan menu baru.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div 
                    v-if="filteredMenus.length > 0" 
                    class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-600"
                >
                    <div class="font-medium">
                        Menampilkan <span class="font-bold text-gray-900">{{ startItemIndex }}</span> sampai <span class="font-bold text-gray-900">{{ endItemIndex }}</span> dari <span class="font-bold text-gray-900">{{ filteredMenus.length }}</span> menu
                    </div>

                    <div v-if="totalPages > 1" class="flex items-center space-x-1.5">
                        <button 
                            @click="currentPage--"
                            :disabled="currentPage === 1"
                            class="px-2.5 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-bold hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                        >
                            <ChevronLeft class="w-4 h-4" />
                        </button>

                        <span class="px-3 py-1 font-bold text-gray-800">
                            Halaman {{ currentPage }} dari {{ totalPages }}
                        </span>

                        <button 
                            @click="currentPage++"
                            :disabled="currentPage === totalPages"
                            class="px-2.5 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 font-bold hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                        >
                            <ChevronRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
