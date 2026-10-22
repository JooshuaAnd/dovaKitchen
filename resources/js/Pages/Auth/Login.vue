<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChefHat } from 'lucide-vue-next';

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('login.post'));
};
</script>

<template>
    <Head title="Masuk" />
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center">
                <Link :href="route('home')" title="Kembali ke Beranda" class="inline-flex text-red-600 hover:text-red-700 hover:scale-105 transition-all p-2 rounded-2xl hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500">
                    <ChefHat class="w-12 h-12" />
                </Link>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">Masuk ke akun Anda</h2>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 border border-gray-100">
                <form class="space-y-6" @submit.prevent="submit">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Alamat Email</label>
                        <div class="mt-1">
                            <input id="email" v-model="form.email" type="email" autocomplete="email" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm">
                        </div>
                        <div v-if="form.errors.email" class="text-sm text-red-600 mt-1">{{ form.errors.email }}</div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Kata Sandi</label>
                        <div class="mt-1">
                            <input id="password" v-model="form.password" type="password" autocomplete="current-password" required class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50">
                            {{ form.processing ? 'Masuk...' : 'Masuk' }}
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 text-center">
                    <Link :href="route('register')" class="text-sm font-medium text-red-600 hover:text-red-500">Belum punya akun? Daftar di sini</Link>
                </div>
            </div>
        </div>
    </div>
</template>
