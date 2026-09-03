<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Calendar as CalendarIcon, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import dayjs from 'dayjs';
import 'dayjs/locale/id';

dayjs.locale('id');

const props = defineProps({
    weekDates: Array,
    currentWeekStart: String
});

const getNextWeekUrl = () => {
    return route('admin.kitchen-report.index', { date: dayjs(props.currentWeekStart).add(7, 'day').format('YYYY-MM-DD') });
};

const getPrevWeekUrl = () => {
    return route('admin.kitchen-report.index', { date: dayjs(props.currentWeekStart).subtract(7, 'day').format('YYYY-MM-DD') });
};
</script>

<template>
    <Head title="Laporan Pemesanan" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 print:py-0 print:max-w-none print:px-0">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4 print:hidden">
                <h1 class="text-3xl font-black text-gray-900">Laporan Pemesanan</h1>
                
                <div class="flex items-center space-x-4 bg-white px-4 py-2 rounded-lg border border-gray-200 shadow-sm">
                    <Link :href="getPrevWeekUrl()" class="p-1 text-gray-500 hover:text-blue-600 transition-colors">
                        <ChevronLeft class="w-5 h-5" />
                    </Link>
                    <div class="text-sm font-bold text-gray-700 flex items-center">
                        <CalendarIcon class="w-4 h-4 mr-2 text-gray-400" />
                        Minggu: {{ dayjs(currentWeekStart).format('DD MMM YYYY') }}
                    </div>
                    <Link :href="getNextWeekUrl()" class="p-1 text-gray-500 hover:text-blue-600 transition-colors">
                        <ChevronRight class="w-5 h-5" />
                    </Link>
                </div>
            </div>

            <!-- Print Header -->
            <div class="hidden print:block mb-8 text-center border-b border-gray-900 pb-6">
                <h2 class="text-2xl font-black text-gray-900 mb-1">Dova Kitchen - Laporan Pemesanan</h2>
                <p class="text-gray-600 font-medium">Periode: {{ dayjs(currentWeekStart).format('DD MMMM YYYY') }} - {{ dayjs(currentWeekStart).add(4, 'day').format('DD MMMM YYYY') }}</p>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 print:grid-cols-5 print:gap-4">
                <div v-for="day in weekDates" :key="day.date" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col print:border-gray-300 print:shadow-none">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 text-center print:bg-white print:border-b-2 print:border-gray-900">
                        <h3 class="font-black text-gray-900 capitalize">{{ dayjs(day.date).format('dddd') }}</h3>
                        <p class="text-xs font-bold text-gray-500">{{ dayjs(day.date).format('DD MMM') }}</p>
                    </div>
                    
                    <div class="p-4 flex-1">
                        <div v-if="day.menus.length === 0" class="h-full flex flex-col items-center justify-center text-center py-6 print:py-2">
                            <p class="text-sm text-gray-400 italic">Libur / Tidak ada menu</p>
                        </div>
                        <div v-else class="space-y-4">
                            <div v-for="menu in day.menus" :key="menu.id" class="flex flex-col space-y-3 p-3 bg-gray-50 rounded-lg border border-gray-100 print:bg-white print:border-gray-200 print:p-2">
                                <div class="flex items-start space-x-3">
                                    <img v-if="menu.photo_path" :src="menu.photo_path" class="w-12 h-12 rounded-md object-cover print:hidden" />
                                    <div class="flex-1">
                                        <p class="text-sm font-bold text-gray-900 leading-tight">{{ menu.name }}</p>
                                    </div>
                                </div>
                                <div class="bg-blue-50 text-blue-800 border border-blue-100 rounded-md py-2 px-3 text-center print:bg-gray-100 print:text-gray-900 print:border-gray-300">
                                    <p class="text-xs font-semibold uppercase tracking-wider mb-0.5">Total Dipesan</p>
                                    <p class="text-xl font-black">{{ menu.total_portions }} <span class="text-sm font-bold">porsi</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Print Footer -->
            <div class="hidden print:block mt-12 text-sm text-gray-500 text-center">
                Dicetak pada: {{ dayjs().format('DD MMMM YYYY HH:mm') }}
            </div>
        </div>
    </AdminLayout>
</template>

<style>
@media print {
    body {
        background-color: white !important;
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
}
</style>
