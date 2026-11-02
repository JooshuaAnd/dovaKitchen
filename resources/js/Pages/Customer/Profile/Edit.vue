<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    user: Object,
});

const profileForm = useForm({
    name: props.user.name,
    date_of_birth: props.user.date_of_birth ? props.user.date_of_birth.substring(0, 10) : '',
});

const updateProfile = () => {
    profileForm.put(route('profile.update'), {
        preserveScroll: true,
    });
};

</script>

<template>
    <Head title="Profil Saya" />

    <CustomerLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <h1 class="text-3xl font-black text-gray-900 mb-8">Profil Saya</h1>

            <div class="space-y-6">
                
                <!-- Dietary Shortcut -->
                <div class="bg-blue-50 rounded-2xl p-6 border border-blue-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-blue-900">Preferensi Diet</h2>
                        <p class="text-sm text-blue-700 mt-1">Kelola alergen Anda untuk menyaring katalog.</p>
                    </div>
                    <Link :href="route('dietary-setup')" class="px-5 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-sm">
                        Edit Alergen
                    </Link>
                </div>

                <!-- Profile Info -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Informasi Profil</h2>
                    
                    <form @submit.prevent="updateProfile" class="space-y-6 max-w-xl">
                        <div>
                            <label class="block text-sm font-bold text-gray-700">Nama Lengkap</label>
                            <input v-model="profileForm.name" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                            <div v-if="profileForm.errors.name" class="text-red-500 text-xs font-semibold mt-1">{{ profileForm.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700">No. Telepon / WhatsApp</label>
                            <input :value="props.user.phone || '-'" type="tel" disabled class="mt-1 block w-full rounded-xl border-gray-200 bg-gray-50 text-gray-600 shadow-sm sm:text-sm">
                            <p class="text-xs text-gray-500 mt-1">Nomor WhatsApp digunakan sebagai identitas akses customer.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700">Tanggal Lahir (CRM Birthday)</label>
                            <input v-model="profileForm.date_of_birth" type="date" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                            <p class="text-xs text-gray-500 mt-1">Digunakan untuk klaim kado voucher ulang tahun 20%.</p>
                            <div v-if="profileForm.errors.date_of_birth" class="text-red-500 text-xs font-semibold mt-1">{{ profileForm.errors.date_of_birth }}</div>
                        </div>

                        <div class="flex items-center space-x-4">
                            <button type="submit" :disabled="profileForm.processing" class="bg-gray-900 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-gray-800 disabled:opacity-50">
                                Simpan Perubahan
                            </button>
                            <span v-if="profileForm.recentlySuccessful" class="text-sm text-green-600 font-medium">Tersimpan.</span>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </CustomerLayout>
</template>
