<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChefHat, Gift } from 'lucide-vue-next';

const form = useForm({
    name: '',
    phone: '',
    date_of_birth: '',
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('register.post'));
};
</script>

<template>
    <Head title="Register" />
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="flex justify-center text-red-600">
                <ChefHat class="w-12 h-12" />
            </div>
            <h2 class="mt-4 text-center text-3xl font-black text-gray-900">Daftar Akun Dova</h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Nikmati catering sehat lezat & keuntungan reward eksklusif
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-xl shadow-gray-100 sm:rounded-2xl sm:px-10 border border-gray-100">
                
                <!-- Birthday Promo Hint Banner -->
                <div class="mb-6 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl p-3.5 flex items-start space-x-3">
                    <Gift class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
                    <p class="text-xs text-amber-800 leading-relaxed font-medium">
                        <strong class="font-bold text-amber-900">CRM Birthday Perk:</strong> Masukkan tanggal lahir Anda untuk klaim <span class="font-bold underline">Voucher Diskon 20%</span> tepat di hari ulang tahun Anda!
                    </p>
                </div>

                <form class="space-y-5" @submit.prevent="submit">
                    <div>
                        <label for="name" class="block text-sm font-bold text-gray-700">Nama Lengkap</label>
                        <div class="mt-1">
                            <input id="name" v-model="form.name" type="text" autocomplete="name" required placeholder="Contoh: Budi Santoso" class="appearance-none block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm">
                        </div>
                        <div v-if="form.errors.name" class="text-xs text-red-600 font-semibold mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-bold text-gray-700">No. Telepon / WhatsApp</label>
                        <div class="mt-1">
                            <input id="phone" v-model="form.phone" type="tel" autocomplete="tel" required placeholder="08xxxxxxxxxx" class="appearance-none block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm">
                        </div>
                        <div v-if="form.errors.phone" class="text-xs text-red-600 font-semibold mt-1">{{ form.errors.phone }}</div>
                    </div>

                    <div>
                        <label for="date_of_birth" class="block text-sm font-bold text-gray-700">Tanggal Lahir</label>
                        <div class="mt-1">
                            <input id="date_of_birth" v-model="form.date_of_birth" type="date" required class="appearance-none block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl shadow-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm">
                        </div>
                        <div v-if="form.errors.date_of_birth" class="text-xs text-red-600 font-semibold mt-1">{{ form.errors.date_of_birth }}</div>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-bold text-gray-700">Alamat Email</label>
                        <div class="mt-1">
                            <input id="email" v-model="form.email" type="email" autocomplete="email" required placeholder="nama@email.com" class="appearance-none block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm">
                        </div>
                        <div v-if="form.errors.email" class="text-xs text-red-600 font-semibold mt-1">{{ form.errors.email }}</div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-bold text-gray-700">Kata Sandi (Password)</label>
                        <div class="mt-1">
                            <input id="password" v-model="form.password" type="password" required placeholder="Minimal 8 karakter" class="appearance-none block w-full px-3.5 py-2.5 border border-gray-300 rounded-xl shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-sm">
                        </div>
                        <div v-if="form.errors.password" class="text-xs text-red-600 font-semibold mt-1">{{ form.errors.password }}</div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" :disabled="form.processing" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-md text-sm font-black text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 transition-all transform hover:-translate-y-0.5">
                            {{ form.processing ? 'Mendaftarkan...' : 'Daftar Sekarang' }}
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 text-center">
                    <Link :href="route('login')" class="text-sm font-bold text-red-600 hover:text-red-700">Sudah punya akun? Masuk di sini</Link>
                </div>
            </div>
        </div>
    </div>
</template>
