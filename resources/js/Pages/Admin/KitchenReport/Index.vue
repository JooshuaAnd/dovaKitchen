<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ChefHat, Printer } from 'lucide-vue-next';
import dayjs from 'dayjs';

defineProps({
    report: Array
});

const formatDate = (date) => {
    return dayjs(date).format('dddd, DD MMM YYYY');
};

const printReport = () => {
    window.print();
};
</script>

<template>
    <Head title="Kitchen Report" />

    <AdminLayout>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 print:py-0 print:max-w-none print:px-0">
            <div class="mb-8 flex items-center justify-between print:hidden">
                <h1 class="text-3xl font-black text-gray-900">Kitchen Report</h1>
                <button @click="printReport" class="inline-flex items-center px-4 py-2 bg-gray-900 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-800 focus:bg-gray-800 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <Printer class="w-4 h-4 mr-2" /> Print Report
                </button>
            </div>

            <!-- Print Header -->
            <div class="hidden print:flex items-center space-x-3 mb-6 border-b border-black pb-4">
                <ChefHat class="w-8 h-8" />
                <div>
                    <h1 class="text-2xl font-black">Dova Kitchen - Daily Cook Report</h1>
                    <p class="text-sm">Printed on {{ dayjs().format('DD MMM YYYY, HH:mm') }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden print:shadow-none print:border-black print:rounded-none">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 print:divide-black">
                        <thead class="bg-gray-50 print:bg-white">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider print:text-black print:border-b print:border-black">Service Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider print:text-black print:border-b print:border-black">Menu Name</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider print:text-black print:border-b print:border-black">Total Portions to Cook</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 print:divide-gray-400">
                            <tr v-for="(item, index) in report" :key="index" class="hover:bg-gray-50 print:hover:bg-transparent">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 print:text-black">
                                    {{ formatDate(item.service_date) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 print:text-black font-bold">
                                    {{ item.menu_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-xl font-black text-blue-600 print:text-black">
                                    {{ item.total_portions }}
                                </td>
                            </tr>
                            <tr v-if="report.length === 0">
                                <td colspan="3" class="px-6 py-8 text-center text-gray-500 print:text-black">
                                    No paid orders found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="hidden print:block mt-8 text-sm italic">
                * Note: This report only includes portions from orders that have been successfully verified (Paid).
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
