<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Users, Trash2, Eye, User, Phone, Mail, Calendar, AlertTriangle, X, CheckCircle2, Heart, ShoppingCart } from 'lucide-vue-next';
import dayjs from 'dayjs';

const props = defineProps({
    users: {
        type: Array,
        required: true,
    }
});

const formatDate = (date) => {
    return date ? dayjs(date).format('DD MMM YYYY') : '-';
};

const formatDateTime = (date) => {
    return date ? dayjs(date).format('DD MMM YYYY HH:mm') : '-';
};

// Modal state
const isModalOpen = ref(false);
const selectedUser = ref(null);

const openModal = (user) => {
    selectedUser.value = user;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    setTimeout(() => {
        selectedUser.value = null;
    }, 300);
};

// Delete user
const form = useForm({});

const confirmDelete = (user) => {
    if (confirm(`Yakin ingin menghapus kustomer ${user.name}? Semua data pesanan yang terkait juga akan terhapus.`)) {
        form.delete(route('admin.users.destroy', user.id), {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedUser.value?.id === user.id) {
                    closeModal();
                }
            }
        });
    }
};
</script>

<template>
    <Head title="User Management" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight flex items-center">
                        <Users class="w-8 h-8 mr-3 text-red-600" />
                        User Management
                    </h1>
                    <p class="mt-2 text-sm text-gray-500 font-medium">Kelola data pelanggan (Kustomer) yang terdaftar di Dova Kitchen.</p>
                </div>
            </div>

            <!-- Flash Message -->
            <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start">
                <div class="flex-shrink-0">
                    <CheckCircle2 class="w-5 h-5 text-green-500" />
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ $page.props.flash.success }}</p>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kustomer</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kontak</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Total Pesanan</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tgl Terdaftar</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-gradient-to-br from-red-100 to-red-50 rounded-full flex items-center justify-center border border-red-100">
                                            <span class="text-red-700 font-bold text-lg">{{ user.name.charAt(0).toUpperCase() }}</span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-gray-900">{{ user.name }}</div>
                                            <div class="text-xs text-gray-500">
                                                <span v-if="user.allergens.length > 0" class="text-red-500 font-medium flex items-center mt-0.5">
                                                    <AlertTriangle class="w-3 h-3 mr-1" /> {{ user.allergens.length }} pantangan
                                                </span>
                                                <span v-else class="text-green-500 font-medium flex items-center mt-0.5">
                                                    Aman (Bebas pantangan)
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 flex items-center">
                                        <Mail class="w-3 h-3 mr-1.5 text-gray-400" /> {{ user.email }}
                                    </div>
                                    <div class="text-xs text-gray-500 flex items-center mt-1">
                                        <Phone class="w-3 h-3 mr-1.5 text-gray-400" /> {{ user.phone || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700">
                                        {{ user.orders_count }} Pesanan
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDateTime(user.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="openModal(user)" class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 p-2 rounded-lg transition-colors mr-2" title="View Details">
                                        <Eye class="w-4 h-4" />
                                    </button>
                                    <button @click="confirmDelete(user)" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 p-2 rounded-lg transition-colors" title="Delete User">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <Users class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                                    <p class="text-base font-medium text-gray-900">Belum ada pengguna</p>
                                    <p class="text-sm mt-1">Sistem belum memiliki kustomer yang terdaftar.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Detail Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" @click="closeModal" aria-hidden="true"></div>

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
                                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500 p-1 bg-gray-50 rounded-full hover:bg-gray-100 transition-colors">
                                        <X class="w-5 h-5" />
                                    </button>
                                </div>
                                
                                <div v-if="selectedUser" class="space-y-6">
                                    <!-- Profile Header -->
                                    <div class="flex items-center p-4 bg-gray-50 rounded-xl border border-gray-100">
                                        <div class="h-16 w-16 bg-gradient-to-br from-red-500 to-rose-600 rounded-full flex items-center justify-center shadow-lg shadow-red-200 text-white font-black text-2xl">
                                            {{ selectedUser.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="ml-5">
                                            <h4 class="text-xl font-bold text-gray-900">{{ selectedUser.name }}</h4>
                                            <p class="text-sm text-gray-500 font-medium">Terdaftar sejak {{ formatDate(selectedUser.created_at) }}</p>
                                        </div>
                                    </div>

                                    <!-- Personal Info Grid -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-1">
                                                <Mail class="w-3 h-3 mr-1.5" /> Email
                                            </p>
                                            <p class="text-sm font-medium text-gray-900">{{ selectedUser.email }}</p>
                                        </div>
                                        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-1">
                                                <Phone class="w-3 h-3 mr-1.5" /> No. Handphone
                                            </p>
                                            <p class="text-sm font-medium text-gray-900">{{ selectedUser.phone || 'Belum diisi' }}</p>
                                        </div>
                                        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-1">
                                                <Calendar class="w-3 h-3 mr-1.5" /> Tanggal Lahir
                                            </p>
                                            <p class="text-sm font-medium text-gray-900">{{ formatDate(selectedUser.date_of_birth) }}</p>
                                        </div>
                                        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center mb-1">
                                                <ShoppingCart class="w-3 h-3 mr-1.5" /> Total Pesanan
                                            </p>
                                            <p class="text-sm font-medium text-gray-900">{{ selectedUser.orders_count }} Transaksi</p>
                                        </div>
                                    </div>

                                    <!-- Dietary Profile -->
                                    <div class="pt-2">
                                        <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-3 flex items-center">
                                            <Heart class="w-4 h-4 mr-2 text-red-500" /> Profil Pantangan (Alergi)
                                        </h4>
                                        <div class="bg-white p-5 rounded-xl border border-red-100 shadow-sm relative overflow-hidden">
                                            <div class="absolute top-0 right-0 w-32 h-32 bg-red-50 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none"></div>
                                            
                                            <div v-if="selectedUser.allergens.length > 0" class="relative z-10 flex flex-wrap gap-2">
                                                <span v-for="a in selectedUser.allergens" :key="a.id" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-bold bg-red-100 text-red-700 border border-red-200">
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
                        <button type="button" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-gray-900 text-base font-bold text-white hover:bg-gray-800 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition-colors" @click="closeModal">
                            Tutup
                        </button>
                        <button type="button" class="mt-3 w-full inline-flex justify-center rounded-xl border border-red-200 shadow-sm px-4 py-2 bg-white text-base font-bold text-red-700 hover:bg-red-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors" @click="confirmDelete(selectedUser)">
                            Hapus Akun
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
