<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ChefHat, Phone, MapPin, ChevronRight, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
    menus: {
        type: Array,
        required: true,
    },
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(price);
};

// Organize menus by day
const days = [
    { id: 'monday', label: 'Senin' },
    { id: 'tuesday', label: 'Selasa' },
    { id: 'wednesday', label: 'Rabu' },
    { id: 'thursday', label: 'Kamis' },
    { id: 'friday', label: 'Jumat' },
];

const selectedDay = ref('monday');

const activeDayMenus = computed(() => {
    return props.menus.filter(m => m.service_day === selectedDay.value);
});

const scrollTo = (id) => {
    const el = document.getElementById(id);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
    }
};
</script>

<template>
    <Head title="Catering Sehat & Lezat" />
    
    <div class="min-h-screen bg-[#FDFBF7] font-sans scroll-smooth">
        <!-- Sticky Navigation -->
        <header class="fixed top-0 left-0 right-0 bg-white/95 backdrop-blur-md shadow-sm z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex justify-between items-center">
                <!-- Logo -->
                <div class="flex items-center space-x-3 cursor-pointer" @click="scrollTo('home')">
                    <div class="w-12 h-12 bg-red-600 rounded-full flex items-center justify-center shadow-lg shadow-red-200">
                        <ChefHat class="w-7 h-7 text-white" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-gray-900 leading-none">Dova Kitchen</h1>
                        <span class="text-xs font-semibold text-red-600 tracking-widest uppercase">Catering Service</span>
                    </div>
                </div>

                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center space-x-8">
                    <button @click="scrollTo('home')" class="text-gray-600 hover:text-red-600 font-semibold transition-colors">Beranda</button>
                    <button @click="scrollTo('profile')" class="text-gray-600 hover:text-red-600 font-semibold transition-colors">Profil</button>
                    <button @click="scrollTo('menu')" class="text-gray-600 hover:text-red-600 font-semibold transition-colors">Menu Mingguan</button>
                </nav>

                <!-- Actions -->
                <div class="flex items-center space-x-4">
                    <Link :href="route('login')" class="hidden sm:block text-gray-600 hover:text-gray-900 font-semibold">Masuk</Link>
                    <Link :href="route('register')" class="bg-gradient-to-r from-red-600 to-red-500 text-white px-6 py-2.5 rounded-full font-bold hover:shadow-lg hover:shadow-red-200 transition-all transform hover:-translate-y-0.5">Daftar Sekarang</Link>
                </div>
            </div>
        </header>

        <!-- 1. Home / Hero Poster Content Card -->
        <section id="home" class="relative pt-20">
            <!-- Background Image -->
            <div class="absolute inset-0 z-0 h-[600px] md:h-[700px] mt-20">
                <img src="https://images.unsplash.com/photo-1555939594-58d7cb561ad1?q=80&w=2000&auto=format&fit=crop" alt="Catering Background" class="w-full h-full object-cover" />
                <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/50 to-transparent"></div>
            </div>

            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-32 pb-40 md:pt-48 md:pb-56">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center space-x-2 bg-white/20 backdrop-blur-md rounded-full px-4 py-2 mb-6 border border-white/30 text-white">
                        <MapPin class="w-4 h-4 text-red-400" />
                        <span class="text-sm font-medium">Melayani Area Yogyakarta & Sekitarnya</span>
                    </div>
                    <h2 class="text-5xl md:text-7xl font-black text-white mb-6 leading-tight drop-shadow-lg">
                        Selamat Datang Di <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-yellow-200">Dova Kitchen</span>
                    </h2>
                    <p class="text-xl text-gray-200 mb-10 text-shadow leading-relaxed">
                        Pengalaman Kuliner yang Tidak Terlupakan. Jelajahi hidangan lezat kami yang disiapkan dengan cinta untuk setiap porsi Anda, 100% aman untuk pantangan alergi Anda.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button @click="scrollTo('menu')" class="inline-flex justify-center items-center bg-white text-gray-900 px-8 py-4 rounded-full font-black text-lg hover:bg-gray-50 transition-colors shadow-xl">
                            Temukan Menu Kami <ChevronRight class="w-5 h-5 ml-2" />
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Profile Card -->
        <section id="profile" class="py-24 bg-white relative z-20 -mt-10 rounded-t-[3rem] shadow-[0_-10px_40px_rgba(0,0,0,0.1)]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <span class="text-red-600 font-bold tracking-wider uppercase text-sm">Tentang Kami</span>
                    <h2 class="text-4xl font-black text-gray-900 mt-2 mb-4">Profil Dova Kitchen</h2>
                    <div class="w-24 h-1 bg-red-600 mx-auto rounded-full"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-16 items-center">
                    <div class="relative">
                        <div class="absolute inset-0 bg-red-100 rounded-3xl transform rotate-3 scale-105 -z-10"></div>
                        <img src="https://images.unsplash.com/photo-1577106263724-2c8e03bfe9ce?q=80&w=800&auto=format&fit=crop" alt="Chef Cooking" class="rounded-3xl shadow-2xl object-cover h-[500px] w-full" />
                        
                        <div class="absolute -bottom-6 -right-6 bg-white p-6 rounded-2xl shadow-xl border border-gray-100 hidden md:block">
                            <div class="flex items-center space-x-4">
                                <div class="bg-yellow-100 p-3 rounded-full text-yellow-600">
                                    <ChefHat class="w-8 h-8" />
                                </div>
                                <div>
                                    <p class="text-3xl font-black text-gray-900">5+</p>
                                    <p class="text-sm font-semibold text-gray-500">Tahun Pengalaman</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-8">
                        <h3 class="text-3xl font-bold text-gray-900 leading-tight">Solusi Catering Sehat Tanpa Khawatir Alergi</h3>
                        <p class="text-lg text-gray-600 leading-relaxed">
                            Berdiri sejak 2021, Dova Kitchen hadir untuk merevolusi industri katering dengan memberikan perhatian khusus pada diet dan alergi pelanggan. Kami percaya bahwa makanan sehat harus tetap lezat dan aman untuk dikonsumsi semua orang.
                        </p>
                        
                        <ul class="space-y-5">
                            <li class="flex items-start">
                                <div class="flex-shrink-0 mt-1">
                                    <CheckCircle2 class="w-6 h-6 text-green-500" />
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-xl font-bold text-gray-900">Filter Alergen Otomatis</h4>
                                    <p class="mt-1 text-gray-600">Sistem pintar kami menyembunyikan menu yang mengandung bahan pantangan Anda.</p>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0 mt-1">
                                    <CheckCircle2 class="w-6 h-6 text-green-500" />
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-xl font-bold text-gray-900">Bahan Segar Premium</h4>
                                    <p class="mt-1 text-gray-600">Hanya menggunakan sayuran organik dan daging pilihan terbaik dari peternakan lokal.</p>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <div class="flex-shrink-0 mt-1">
                                    <CheckCircle2 class="w-6 h-6 text-green-500" />
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-xl font-bold text-gray-900">Higienis & Profesional</h4>
                                    <p class="mt-1 text-gray-600">Dapur berstandar internasional yang menjamin kebersihan di setiap proses memasak.</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- 3. Menu Card Carousel / Grid -->
        <section id="menu" class="py-24 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-red-600 font-bold tracking-wider uppercase text-sm">Hidangan Spesial</span>
                    <h2 class="text-4xl font-black text-gray-900 mt-2 mb-4">Pilihan Menu Mingguan</h2>
                    <p class="text-gray-600 max-w-2xl mx-auto">Daftar menu di bawah ini dapat Anda pesan melalui Dashboard setelah melakukan registrasi. Atur pantangan alergi Anda agar menu tersaring otomatis.</p>
                </div>

                <!-- Day Tabs -->
                <div class="flex justify-center mb-12 overflow-x-auto pb-4 hide-scrollbar">
                    <div class="inline-flex bg-white rounded-full p-1.5 shadow-sm border border-gray-200">
                        <button 
                            v-for="day in days" 
                            :key="day.id"
                            @click="selectedDay = day.id"
                            :class="[
                                selectedDay === day.id 
                                    ? 'bg-red-600 text-white shadow-md' 
                                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50',
                                'px-6 py-2.5 rounded-full font-bold text-sm transition-all whitespace-nowrap'
                            ]"
                        >
                            {{ day.label }}
                        </button>
                    </div>
                </div>

                <!-- Menu Cards -->
                <div v-if="activeDayMenus.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="menu in activeDayMenus" :key="menu.id" class="bg-white rounded-3xl overflow-hidden shadow-lg border border-gray-100 group hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                        <div class="relative h-64 overflow-hidden">
                            <img :src="menu.photo_path || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?q=80&w=800&auto=format&fit=crop'" :alt="menu.name" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
                            <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-4 py-1.5 rounded-full shadow-sm">
                                <span class="font-black text-red-600">{{ formatPrice(menu.price) }}</span>
                            </div>
                        </div>
                        <div class="p-8">
                            <h3 class="text-2xl font-black text-gray-900 mb-3">{{ menu.name }}</h3>
                            <p class="text-gray-600 mb-6 line-clamp-3 leading-relaxed">{{ menu.description }}</p>
                            
                            <div class="border-t border-gray-100 pt-6">
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Mengandung Alergen:</p>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="a in menu.allergens" :key="a.id" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-red-50 text-red-700 border border-red-100">
                                        {{ a.name }}
                                    </span>
                                    <span v-if="menu.allergens.length === 0" class="text-xs font-medium text-green-600 bg-green-50 px-2.5 py-1 rounded-md border border-green-100">Aman / Bebas Alergen Utama</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-else class="text-center py-20 bg-white rounded-3xl border border-dashed border-gray-300">
                    <ChefHat class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                    <h3 class="text-xl font-bold text-gray-900">Belum ada menu</h3>
                    <p class="text-gray-500 mt-2">Menu untuk hari ini belum tersedia.</p>
                </div>

                <div class="mt-16 text-center">
                    <Link :href="route('register')" class="inline-flex justify-center items-center bg-gray-900 text-white px-10 py-4 rounded-full font-black text-lg hover:bg-gray-800 transition-colors shadow-xl">
                        Pesan Menu Sekarang <ChevronRight class="w-5 h-5 ml-2" />
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white py-12 border-t border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center md:text-left grid grid-cols-1 md:grid-cols-3 gap-8 items-center">
                <div>
                    <div class="flex items-center justify-center md:justify-start space-x-2 mb-4">
                        <ChefHat class="w-6 h-6 text-red-500" />
                        <span class="text-xl font-black tracking-tight">Dova Kitchen</span>
                    </div>
                    <p class="text-gray-400 text-sm">Katering sehat pilihan utama di Yogyakarta. Enak, terjangkau, dan aman untuk alergi.</p>
                </div>
                <div class="text-center">
                    <p class="text-sm font-bold text-gray-300 uppercase tracking-wider mb-2">Tautan Cepat</p>
                    <div class="flex flex-col space-y-2">
                        <button @click="scrollTo('home')" class="text-gray-400 hover:text-white transition-colors text-sm">Beranda</button>
                        <button @click="scrollTo('profile')" class="text-gray-400 hover:text-white transition-colors text-sm">Profil</button>
                        <button @click="scrollTo('menu')" class="text-gray-400 hover:text-white transition-colors text-sm">Menu Mingguan</button>
                    </div>
                </div>
                <div class="text-center md:text-right">
                    <p class="text-gray-500 text-sm">&copy; {{ new Date().getFullYear() }} Dova Kitchen. Hak Cipta Dilindungi.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.text-shadow {
    text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}
</style>
