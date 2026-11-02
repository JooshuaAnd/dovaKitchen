<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChefHat } from 'lucide-vue-next';

const form = useForm({
    phone: '',
});

const submit = () => {
    form.post(route('customer-access.identify'));
};
</script>

<template>
    <Head title="Mulai Memesan" />

    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <Link :href="route('home')" class="inline-flex text-red-600 p-2 rounded-2xl">
                <ChefHat class="w-12 h-12" />
            </Link>
            <h1 class="mt-5 text-3xl font-black text-gray-900">Mulai Pesan di Dova Kitchen</h1>
            <p class="mt-2 text-sm text-gray-600">Masukkan nomor WhatsApp yang menerima link pemesanan dari admin.</p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <form class="bg-white py-8 px-4 shadow-xl shadow-gray-100 sm:rounded-2xl sm:px-10 border border-gray-100 space-y-5" @submit.prevent="submit">
                <div>
                    <label for="phone" class="block text-sm font-bold text-gray-700">Nomor WhatsApp</label>
                    <input id="phone" v-model="form.phone" type="tel" autocomplete="tel" required placeholder="08xxxxxxxxxx" class="mt-1 block w-full px-3.5 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm">
                    <p class="mt-1.5 text-xs text-gray-500">Contoh: 081234567890</p>
                    <p v-if="form.errors.phone" class="mt-1 text-xs font-semibold text-red-600">{{ form.errors.phone }}</p>
                </div>

                <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-3 px-4 rounded-xl text-sm font-black text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 transition-colors">
                    {{ form.processing ? 'Memeriksa...' : 'Lanjutkan' }}
                </button>
            </form>
        </div>
    </div>
</template>
