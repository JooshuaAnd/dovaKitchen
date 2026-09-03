<script setup>
import { defineProps, defineEmits } from 'vue';
import { Mail, Phone, Calendar, ShoppingCart, Heart, CheckCircle2, X } from 'lucide-vue-next';
import dayjs from 'dayjs';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    user: {
        type: Object,
        default: null,
    }
});

const emit = defineEmits(['close', 'delete']);

const formatDate = (date) => {
    return date ? dayjs(date).format('DD MMM YYYY') : '-';
};

const formatDateTime = (date) => {
    return date ? dayjs(date).format('DD MMM YYYY HH:mm') : '-';
};

const close = () => {
    emit('close');
};

const confirmDelete = () => {
    emit('delete', props.user);
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/20 transition-opacity backdrop-blur-md" @click="close" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Panel -->
            <div class="relative z-10 inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-2xl font-black text-gray-900 flex items-center" id="modal-title">
                                    Detail Kustomer
                                </h3>
                                <button @click="close" class="text-gray-400 hover:text-gray-500 p-1 bg-gray-50 rounded-full hover:bg-gray-100 transition-colors">
                                    <X class="w-5 h-5" />
                                </button>
                            </div>
                            
                            <div v-if="user" class="space-y-6">
                                <!-- Profile Header -->
                                <div class="flex items-center p-4 bg-gray-50 rounded-xl border border-gray-100">
                                    <div class="h-16 w-16 bg-gradient-to-br from-red-500 to-rose-600 rounded-full flex items-center justify-center shadow-lg shadow-red-200 text-white font-black text-2xl">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="ml-5">
                                        <h4 class="text-xl font-bold text-gray-900">{{ user.name }}</h4>
                                        <p class="text-sm text-gray-500 font-medium">Terdaftar sejak {{ formatDate(user.created_at) }}</p>
                                    </div>
                                </div>

                                <!-- Personal Info Grid -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-1">
                                            <Mail class="w-3 h-3 mr-1.5" /> Email
                                        </p>
                                        <p class="text-sm font-medium text-gray-900">{{ user.email }}</p>
                                    </div>
                                    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-1">
                                            <Phone class="w-3 h-3 mr-1.5" /> No. Handphone
                                        </p>
                                        <p class="text-sm font-medium text-gray-900">{{ user.phone || 'Belum diisi' }}</p>
                                    </div>
                                    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-1">
                                            <Calendar class="w-3 h-3 mr-1.5" /> Tanggal Lahir
                                        </p>
                                        <p class="text-sm font-medium text-gray-900">{{ formatDate(user.date_of_birth) }}</p>
                                    </div>
                                    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-1">
                                            <ShoppingCart class="w-3 h-3 mr-1.5" /> Total Pesanan
                                        </p>
                                        <p class="text-sm font-medium text-gray-900">{{ user.orders_count }} Transaksi</p>
                                    </div>
                                    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-1">
                                            <Calendar class="w-3 h-3 mr-1.5" /> Pesanan Terakhir
                                        </p>
                                        <p class="text-sm font-medium text-gray-900">{{ user.orders_max_created_at ? formatDateTime(user.orders_max_created_at) : 'Belum pernah pesan' }}</p>
                                    </div>
                                </div>

                                <!-- Dietary Profile -->
                                <div class="pt-2">
                                    <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3 flex items-center">
                                        <Heart class="w-4 h-4 mr-2 text-red-500" /> Profil Pantangan (Alergi)
                                    </h4>
                                    <div class="bg-white p-5 rounded-xl border border-red-100 shadow-sm relative overflow-hidden">
                                        <div class="absolute top-0 right-0 w-32 h-32 bg-red-50 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none"></div>
                                        
                                        <div v-if="user.allergens && user.allergens.length > 0" class="relative z-10 flex flex-wrap gap-2">
                                            <span v-for="a in user.allergens" :key="a.id" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-bold bg-red-100 text-red-700 border border-red-200">
                                                {{ a.name }}
                                            </span>
                                        </div>
                                        <div v-else class="relative z-10 flex items-center text-sm font-medium text-green-600 bg-green-50 px-4 py-3 rounded-lg border border-green-100">
                                            <CheckCircle2 class="w-5 h-5 mr-2" />
                                            Kustomer ini tidak memiliki pantangan alergi apapun.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-4 sm:px-6 sm:flex sm:flex-row-reverse border-t border-gray-100">
                    <button type="button" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-gray-900 text-base font-bold text-white hover:bg-gray-800 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition-colors" @click="close">
                        Tutup
                    </button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-red-200 shadow-sm px-4 py-2 bg-white text-base font-bold text-red-700 hover:bg-red-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors" @click="confirmDelete">
                        Hapus Akun
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
