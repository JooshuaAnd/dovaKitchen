<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Calendar as CalendarIcon, ChevronLeft, ChevronRight, Plus, X } from 'lucide-vue-next';
import dayjs from 'dayjs';

const props = defineProps({
    weekDates: Array,
    availableMenus: Array,
    currentWeekStart: String
});

const getNextWeekUrl = () => {
    return route('admin.weekly-management.index', { date: dayjs(props.currentWeekStart).add(7, 'day').format('YYYY-MM-DD') });
};

const getPrevWeekUrl = () => {
    return route('admin.weekly-management.index', { date: dayjs(props.currentWeekStart).subtract(7, 'day').format('YYYY-MM-DD') });
};

const isModalOpen = ref(false);
const activeDate = ref(null);
const activeDayName = ref('');
const selectedMenuIds = ref([]);

const openModal = (date, dayName, currentMenus) => {
    activeDate.value = date;
    activeDayName.value = dayName;
    selectedMenuIds.value = currentMenus.map(m => m.id);
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    activeDate.value = null;
    activeDayName.value = '';
    selectedMenuIds.value = [];
};

const toggleMenu = (menuId) => {
    const index = selectedMenuIds.value.indexOf(menuId);
    if (index > -1) {
        selectedMenuIds.value.splice(index, 1);
    } else {
        selectedMenuIds.value.push(menuId);
    }
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
            closeModal();
        }
    });
};
</script>

<template>
    <Head title="Weekly Management" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <h1 class="text-3xl font-black text-gray-900">Weekly Management</h1>
                
                <div class="flex items-center space-x-4 bg-white px-4 py-2 rounded-lg border border-gray-200 shadow-sm">
                    <Link :href="getPrevWeekUrl()" class="p-1 text-gray-500 hover:text-blue-600 transition-colors">
                        <ChevronLeft class="w-5 h-5" />
                    </Link>
                    <div class="text-sm font-bold text-gray-700 flex items-center">
                        <CalendarIcon class="w-4 h-4 mr-2 text-gray-400" />
                        Week of {{ dayjs(currentWeekStart).format('DD MMM YYYY') }}
                    </div>
                    <Link :href="getNextWeekUrl()" class="p-1 text-gray-500 hover:text-blue-600 transition-colors">
                        <ChevronRight class="w-5 h-5" />
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <div v-for="day in weekDates" :key="day.date" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-gray-900">{{ day.day_name }}</h3>
                            <p class="text-xs text-gray-500">{{ dayjs(day.date).format('DD MMM') }}</p>
                        </div>
                        <button @click="openModal(day.date, day.day_name, day.menus)" class="p-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-md transition-colors" title="Manage Menus">
                            <Plus class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="p-4 flex-1">
                        <div v-if="day.menus.length === 0" class="h-full flex flex-col items-center justify-center text-center py-6">
                            <p class="text-sm text-gray-400 italic">No menus assigned</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="menu in day.menus" :key="menu.id" class="flex items-center space-x-3 p-2 bg-gray-50 rounded-lg border border-gray-100">
                                <img :src="menu.photo_path || 'https://ui-avatars.com/api/?name='+menu.name" class="w-10 h-10 rounded-md object-cover" />
                                <div>
                                    <p class="text-sm font-bold text-gray-900 leading-tight">{{ menu.name }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(menu.price) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manage Menus Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeModal"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col m-4">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Manage Menus</h2>
                        <p class="text-sm text-gray-500">{{ activeDayName }}, {{ dayjs(activeDate).format('DD MMMM YYYY') }}</p>
                    </div>
                    <button @click="closeModal" class="p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-900 rounded-full transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                
                <div class="p-6 overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div 
                            v-for="menu in availableMenus" 
                            :key="menu.id"
                            @click="toggleMenu(menu.id)"
                            class="flex items-start space-x-4 p-3 rounded-xl border-2 cursor-pointer transition-all"
                            :class="selectedMenuIds.includes(menu.id) ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
                        >
                            <img :src="menu.photo_path || 'https://ui-avatars.com/api/?name='+menu.name" class="w-16 h-16 rounded-lg object-cover flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 truncate">{{ menu.name }}</p>
                                <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ menu.description }}</p>
                            </div>
                            <div class="flex-shrink-0 mt-1">
                                <div class="w-5 h-5 rounded border flex items-center justify-center transition-colors"
                                     :class="selectedMenuIds.includes(menu.id) ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300'">
                                    <svg v-if="selectedMenuIds.includes(menu.id)" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div v-if="availableMenus.length === 0" class="col-span-full py-8 text-center text-gray-500">
                            No active menus found in catalog.
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end space-x-3 bg-gray-50 rounded-b-2xl">
                    <button @click="closeModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button @click="saveSchedule" :disabled="form.processing" type="button" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all disabled:opacity-50 flex items-center">
                        <span v-if="form.processing" class="w-4 h-4 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        Save Schedule
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
