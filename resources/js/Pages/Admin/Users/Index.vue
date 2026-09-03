<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Users, Trash2, Eye, User, Phone, Mail, Calendar, AlertTriangle, X, CheckCircle2, Heart, ShoppingCart } from 'lucide-vue-next';
import dayjs from 'dayjs';
import UserDetailModal from './Partials/UserDetailModal.vue';
import WhatsAppModal from './Partials/WhatsAppModal.vue';

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

const isInactive = (date) => {
    if (!date) return false;
    return dayjs().diff(dayjs(date), 'day') >= 7;
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

// WhatsApp Modal state
const isWaModalOpen = ref(false);
const waSelectedUser = ref(null);

const openWaModal = (user) => {
    waSelectedUser.value = user;
    isWaModalOpen.value = true;
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
    <Head title="Manajemen Pengguna" />

    <AdminLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-gray-900 tracking-tight flex items-center">
                        <Users class="w-8 h-8 mr-3 text-red-600" />
                        Manajemen Akun
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
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Pesanan Terakhir</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">WhatsApp</th>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span v-if="user.orders_max_created_at" 
                                          :class="isInactive(user.orders_max_created_at) ? 'text-yellow-700 font-bold flex items-center bg-yellow-50 px-2.5 py-1 rounded-lg w-fit border border-yellow-200' : 'text-gray-500'">
                                        <AlertTriangle v-if="isInactive(user.orders_max_created_at)" class="w-4 h-4 mr-1.5 text-yellow-600" />
                                        {{ formatDateTime(user.orders_max_created_at) }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button v-if="user.phone" @click="openWaModal(user)" class="inline-flex items-center justify-center p-2 rounded-full bg-green-50 text-green-600 hover:bg-green-100 hover:text-green-700 transition-colors" title="Kirim Pesan WhatsApp">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
                                        </svg>
                                    </button>
                                    <span v-else class="text-xs text-gray-400">-</span>
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
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
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

        <UserDetailModal 
            :show="isModalOpen" 
            :user="selectedUser" 
            @close="closeModal" 
            @delete="confirmDelete" 
        />

        <WhatsAppModal 
            :show="isWaModalOpen" 
            :user="waSelectedUser" 
            @close="isWaModalOpen = false" 
        />
    </AdminLayout>
</template>
