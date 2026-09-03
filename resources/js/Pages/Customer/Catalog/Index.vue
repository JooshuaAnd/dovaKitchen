<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ShoppingCart, Gift, Sparkles, CheckCircle2, Heart } from 'lucide-vue-next';
import DayTabSelector from '@/Components/Customer/DayTabSelector.vue';
import MenuCard from '@/Components/Customer/MenuCard.vue';
import CartDrawer from '@/Components/Customer/CartDrawer.vue';
import CustomerLayout from '@/Layouts/CustomerLayout.vue';

const props = defineProps({
    menus: {
        type: Array,
        required: true,
    },
    birthdayReward: {
        type: Object,
        default: () => ({})
    }
});

// Calculate day states (monday-friday) based on menus
const days = [
    { id: 'monday', label: 'Senin', disabled: false },
    { id: 'tuesday', label: 'Selasa', disabled: false },
    { id: 'wednesday', label: 'Rabu', disabled: false },
    { id: 'thursday', label: 'Kamis', disabled: false },
    { id: 'friday', label: 'Jumat', disabled: false },
];

days.forEach(day => {
    const dayMenus = props.menus.filter(m => m.service_day === day.id);
    if (dayMenus.length > 0) {
        day.disabled = !dayMenus.some(m => m.is_orderable);
    }
});

const selectedDay = ref('monday');
const isCartOpen = ref(false);

const activeDayMenus = computed(() => {
    return props.menus.filter(m => m.service_day === selectedDay.value);
});

// Cart State
const cart = ref({});

const getQuantity = (menuId) => cart.value[menuId] || 0;

const increment = (menu) => {
    cart.value[menu.daily_menu_id] = (cart.value[menu.daily_menu_id] || 0) + 1;
};

const decrement = (menu) => {
    if (cart.value[menu.daily_menu_id] > 0) {
        cart.value[menu.daily_menu_id]--;
    }
};

const totalCartItems = computed(() => {
    return Object.values(cart.value).reduce((sum, qty) => sum + qty, 0);
});

const cartItems = computed(() => {
    return Object.keys(cart.value)
        .filter(id => cart.value[id] > 0)
        .map(id => {
            const menu = props.menus.find(m => m.daily_menu_id == id);
            return { menu, quantity: cart.value[id] };
        });
});

const checkoutForm = useForm({
    items: []
});

const proceedToCheckout = () => {
    checkoutForm.items = cartItems.value.map(item => ({
        menu_id: item.menu.id,
        service_date: item.menu.service_date,
        portion_quantity: item.quantity
    }));
    
    checkoutForm.post(route('checkout.store'), {
        onSuccess: () => {
            isCartOpen.value = false;
            cart.value = {};
        }
    });
};

// Claim Birthday Reward Form
const claimForm = useForm({});
const claimBirthdayReward = () => {
    claimForm.post(route('birthday-reward.claim'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Katalog Menu" />
    
    <CustomerLayout>
        <div class="min-h-screen bg-gray-50 pb-24">
        <!-- Top Nav -->
        <header class="bg-white shadow-sm sticky top-0 z-30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-900">Menu Mingguan</h1>
                <button 
                    @click="isCartOpen = true"
                    class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-colors"
                >
                    <ShoppingCart class="w-6 h-6" />
                    <span 
                        v-if="totalCartItems > 0"
                        class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-red-600 rounded-full"
                    >
                        {{ totalCartItems }}
                    </span>
                </button>
            </div>
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 border-t border-gray-100 bg-white">
                <DayTabSelector :days="days" v-model="selectedDay" />
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
            
            <!-- CRM Birthday Celebration & Reward Card -->
            <div v-if="birthdayReward?.is_birthday" 
                 class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-red-600 via-rose-600 to-amber-600 text-white p-6 sm:p-8 shadow-xl shadow-red-200 border border-red-500">
                <!-- Background ambient circles -->
                <div class="absolute -top-12 -right-12 w-48 h-48 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="absolute -bottom-12 -left-12 w-48 h-48 bg-amber-400/20 rounded-full blur-2xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div class="space-y-3 max-w-2xl">
                        <div class="inline-flex items-center space-x-2 bg-white/20 backdrop-blur-md px-3.5 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider text-amber-200 border border-white/20">
                            <Sparkles class="w-4 h-4 text-amber-300" />
                            <span>Spesial Hari Ulang Tahun Anda! 🎂</span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight leading-tight">
                            Selamat Ulang Tahun, {{ birthdayReward.user_name }}! 🎉
                        </h2>
                        <p class="text-sm sm:text-base text-red-50 font-medium leading-relaxed">
                            Semoga panjang umur, selalu diberikan kesehatan, kebahagiaan, dan kemudahan dalam setiap langkah. Dova Kitchen ingin merayakan hari istimewa ini bersama Anda! ❤️
                        </p>
                        
                        <div class="pt-1 flex items-center space-x-2 text-xs font-bold text-amber-200">
                            <Gift class="w-4 h-4" />
                            <span>Kado Spesial: Diskon 20% (Min. Belanja Rp 50.000)</span>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="flex-shrink-0 w-full md:w-auto">
                        <div v-if="birthdayReward.has_used_this_year" 
                             class="inline-flex items-center px-5 py-3 rounded-2xl bg-white/20 backdrop-blur-md text-white text-sm font-bold border border-white/30">
                            <CheckCircle2 class="w-5 h-5 mr-2 text-green-300" />
                            Kado Tahun Ini Telah Digunakan
                        </div>
                        
                        <div v-else-if="birthdayReward.is_claimed" 
                             class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
                            <div class="inline-flex items-center px-5 py-3 rounded-2xl bg-white text-red-600 text-sm font-black shadow-lg">
                                <CheckCircle2 class="w-5 h-5 mr-2 text-green-600" />
                                Voucher 20% Aktif di Keranjang!
                            </div>
                            <button @click="isCartOpen = true" class="text-xs font-bold text-white underline hover:text-amber-200">
                                Buka Keranjang &rarr;
                            </button>
                        </div>

                        <button v-else 
                                @click="claimBirthdayReward" 
                                :disabled="claimForm.processing"
                                class="w-full md:w-auto inline-flex items-center justify-center px-8 py-4 rounded-2xl bg-yellow-400 hover:bg-yellow-300 active:scale-95 text-gray-900 text-base font-black shadow-2xl shadow-yellow-500/50 transition-all transform hover:-translate-y-0.5">
                            <Gift class="w-5 h-5 mr-2 text-gray-900" />
                            {{ claimForm.processing ? 'Mengklaim Kado...' : '🎁 Klaim Kado Ultah Sekarang' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Menu Grid -->
            <div v-if="activeDayMenus.length === 0" class="flex flex-col items-center justify-center py-20 bg-white rounded-2xl border border-dashed border-gray-300">
                <p class="text-lg font-medium text-gray-900 mb-2">Belum ada menu yang tersedia</p>
                <p class="text-gray-500 text-center max-w-md">Semua menu untuk hari ini telah disaring berdasarkan preferensi diet Anda, atau belum diterbitkan.</p>
            </div>
            
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <MenuCard 
                    v-for="menu in activeDayMenus" 
                    :key="menu.daily_menu_id" 
                    :menu="menu"
                    :quantity="getQuantity(menu.daily_menu_id)"
                    @increment="increment"
                    @decrement="decrement"
                />
            </div>
        </main>

        <CartDrawer 
            :is-open="isCartOpen" 
            :items="cartItems" 
            :birthday-reward="birthdayReward"
            @close="isCartOpen = false"
            @checkout="proceedToCheckout"
        />
        </div>
    </CustomerLayout>
</template>
