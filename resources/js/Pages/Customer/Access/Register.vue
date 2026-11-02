<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChefHat } from 'lucide-vue-next';

const props = defineProps({
    phone: {
        type: String,
        required: true,
    },
});

const form = useForm({
    name: '',
});

const submit = () => {
    form.post(route('customer-access.register'));
};
</script>

<template>
    <Head title="Data Customer" />

    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <Link :href="route('home')" class="inline-flex text-red-600 p-2 rounded-2xl">
                <ChefHat class="w-12 h-12" />
            </Link>
            <h1 class="mt-5 text-3xl font-black text-gray-900">Kenalan Dulu</h1>
            <p class="mt-2 text-sm text-gray-600">Cukup isi nama sebelum memilih menu.</p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <form class="bg-white py-8 px-4 shadow-xl shadow-gray-100 sm:rounded-2xl sm:px-10 border border-gray-100 space-y-5" @submit.prevent="submit">
                <div>
                    <label class="block text-sm font-bold text-gray-700">Nomor WhatsApp</label>
                    <input :value="props.phone" type="text" disabled class="mt-1 block w-full px-3.5 py-3 border border-gray-200 rounded-xl bg-gray-50 text-gray-600 sm:text-sm">
                </div>

                <div>
                    <label for="name" class="block text-sm font-bold text-gray-700">Nama Lengkap</label>
                    <input id="name" v-model="form.name" type="text" autocomplete="name" required placeholder="Contoh: Budi Santoso" class="mt-1 block w-full px-3.5 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm">
                    <p v-if="form.errors.name" class="mt-1 text-xs font-semibold text-red-600">{{ form.errors.name }}</p>
                </div>

                <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-3 px-4 rounded-xl text-sm font-black text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 transition-colors">
                    {{ form.processing ? 'Menyimpan...' : 'Lanjut ke Preferensi Diet' }}
                </button>
            </form>
        </div>
    </div>
</template>
