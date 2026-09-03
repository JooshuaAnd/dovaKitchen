<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    user: Object,
});

const profileForm = useForm({
    name: props.user.name,
    phone: props.user.phone || '',
    date_of_birth: props.user.date_of_birth ? props.user.date_of_birth.substring(0, 10) : '',
    email: props.user.email,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updateProfile = () => {
    profileForm.put(route('profile.update'), {
        preserveScroll: true,
    });
};

const updatePassword = () => {
    passwordForm.put(route('profile.password'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};
</script>

<template>
    <Head title="My Profile" />

    <CustomerLayout>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
            <h1 class="text-3xl font-black text-gray-900 mb-8">My Profile</h1>

            <div class="space-y-6">
                
                <!-- Dietary Shortcut -->
                <div class="bg-blue-50 rounded-2xl p-6 border border-blue-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-blue-900">Dietary Preferences</h2>
                        <p class="text-sm text-blue-700 mt-1">Manage your allergens to filter the catalog.</p>
                    </div>
                    <Link :href="route('dietary-setup')" class="px-5 py-2.5 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors shadow-sm">
                        Edit Allergens
                    </Link>
                </div>

                <!-- Profile Info -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Profile Information</h2>
                    
                    <form @submit.prevent="updateProfile" class="space-y-6 max-w-xl">
                        <div>
                            <label class="block text-sm font-bold text-gray-700">Nama Lengkap</label>
                            <input v-model="profileForm.name" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                            <div v-if="profileForm.errors.name" class="text-red-500 text-xs font-semibold mt-1">{{ profileForm.errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700">No. Telepon / WhatsApp</label>
                            <input v-model="profileForm.phone" type="tel" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                            <div v-if="profileForm.errors.phone" class="text-red-500 text-xs font-semibold mt-1">{{ profileForm.errors.phone }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700">Tanggal Lahir (CRM Birthday)</label>
                            <input v-model="profileForm.date_of_birth" type="date" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                            <p class="text-xs text-gray-500 mt-1">Digunakan untuk klaim kado voucher ulang tahun 20%.</p>
                            <div v-if="profileForm.errors.date_of_birth" class="text-red-500 text-xs font-semibold mt-1">{{ profileForm.errors.date_of_birth }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700">Email</label>
                            <input v-model="profileForm.email" type="email" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                            <div v-if="profileForm.errors.email" class="text-red-500 text-xs font-semibold mt-1">{{ profileForm.errors.email }}</div>
                        </div>

                        <div class="flex items-center space-x-4">
                            <button type="submit" :disabled="profileForm.processing" class="bg-gray-900 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-gray-800 disabled:opacity-50">
                                Save Changes
                            </button>
                            <span v-if="profileForm.recentlySuccessful" class="text-sm text-green-600 font-medium">Saved.</span>
                        </div>
                    </form>
                </div>

                <!-- Password -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Update Password</h2>
                    
                    <form @submit.prevent="updatePassword" class="space-y-6 max-w-xl">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Current Password</label>
                            <input v-model="passwordForm.current_password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                            <div v-if="passwordForm.errors.current_password" class="text-red-500 text-sm mt-1">{{ passwordForm.errors.current_password }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">New Password</label>
                            <input v-model="passwordForm.password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                            <div v-if="passwordForm.errors.password" class="text-red-500 text-sm mt-1">{{ passwordForm.errors.password }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input v-model="passwordForm.password_confirmation" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                        </div>

                        <div class="flex items-center space-x-4">
                            <button type="submit" :disabled="passwordForm.processing" class="bg-gray-900 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-gray-800 disabled:opacity-50">
                                Update Password
                            </button>
                            <span v-if="passwordForm.recentlySuccessful" class="text-sm text-green-600 font-medium">Saved.</span>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </CustomerLayout>
</template>
