<script setup>
import { computed } from 'vue';
import { X, ShoppingBag, Gift, Sparkles } from 'lucide-vue-next';

const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true
    },
    items: {
        type: Array,
        required: true,
    },
    birthdayReward: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['close', 'checkout']);

const subtotal = computed(() => {
    return props.items.reduce((sum, item) => sum + (item.menu.price * item.quantity), 0);
});

// Birthday voucher calculation
const isBirthdayVoucherActive = computed(() => {
    return props.birthdayReward?.is_birthday && 
           props.birthdayReward?.is_claimed && 
           !props.birthdayReward?.has_used_this_year;
});

const isMinSpendMet = computed(() => {
    return subtotal.value >= (props.birthdayReward?.min_purchase || 50000);
});

const discountAmount = computed(() => {
    if (isBirthdayVoucherActive.value && isMinSpendMet.value) {
        return Math.round(subtotal.value * 0.20);
    }
    return 0;
});

const finalTotal = computed(() => {
    return Math.max(0, subtotal.value - discountAmount.value);
});

const formattedSubtotal = computed(() => formatPrice(subtotal.value));
const formattedDiscount = computed(() => formatPrice(discountAmount.value));
const formattedFinalTotal = computed(() => formatPrice(finalTotal.value));

// Group active cart items by service_date
const groupedItems = computed(() => {
    const groups = {};
    props.items.forEach(item => {
        if (item.quantity > 0) {
            const date = item.menu.service_date;
            if (!groups[date]) {
                groups[date] = [];
            }
            groups[date].push(item);
        }
    });
    
    // Sort groups by date
    const sortedGroups = {};
    Object.keys(groups).sort().forEach(key => {
        sortedGroups[key] = groups[key];
    });
    
    return sortedGroups;
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(price);
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short' });
};
</script>

<template>
    <div>
        <!-- Backdrop -->
        <transition
            enter-active-class="transition-opacity ease-linear duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-linear duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="isOpen" 
                @click="emit('close')"
                class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm z-40"
            ></div>
        </transition>

        <!-- Drawer -->
        <div 
            :class="[
                'fixed inset-y-0 right-0 w-full md:w-[420px] bg-white shadow-2xl z-50 transform transition-transform duration-300 ease-in-out flex flex-col',
                isOpen ? 'translate-x-0' : 'translate-x-full'
            ]"
        >
            <!-- Header -->
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-white">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-red-50 text-red-600 rounded-xl">
                        <ShoppingBag class="w-5 h-5" />
                    </div>
                    <h2 class="text-xl font-bold text-gray-900">Keranjang Pesanan</h2>
                </div>
                <button 
                    @click="emit('close')"
                    class="p-2 -mr-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6 bg-gray-50/50 space-y-4">
                
                <!-- Birthday Perk Notification inside Cart -->
                <div v-if="isBirthdayVoucherActive" class="rounded-2xl p-4 border transition-all"
                     :class="isMinSpendMet ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-md' : 'bg-amber-50 border-amber-200 text-amber-900'">
                    <div class="flex items-start space-x-3">
                        <div :class="isMinSpendMet ? 'bg-white/20 p-2 rounded-xl text-white' : 'bg-amber-100 p-2 rounded-xl text-amber-600'">
                            <Gift class="w-5 h-5" />
                        </div>
                        <div class="flex-1 text-xs">
                            <p class="font-black text-sm" :class="isMinSpendMet ? 'text-white' : 'text-amber-900'">
                                Kado Ulang Tahun (Diskon 20%)
                            </p>
                            <p v-if="isMinSpendMet" class="mt-0.5 text-amber-100 font-medium">
                                Syarat min. belanja Rp 50.000 terpenuhi! Anda hemat {{ formattedDiscount }}.
                            </p>
                            <p v-else class="mt-0.5 text-amber-700 font-medium">
                                Belanja minimal Rp 50.000 untuk menikmati diskon (kurang {{ formatPrice(50000 - subtotal) }} lagi).
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="items.filter(i => i.quantity > 0).length === 0" class="h-64 flex flex-col items-center justify-center text-gray-500">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <ShoppingBag class="w-8 h-8 text-gray-400" />
                    </div>
                    <p class="font-bold text-gray-900">Keranjang masih kosong</p>
                    <p class="text-sm text-gray-500 mt-1">Silakan pilih menu favorit Anda di katalog.</p>
                </div>
                
                <div v-else class="space-y-5">
                    <div v-for="(dayItems, day) in groupedItems" :key="day">
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3 capitalize flex items-center">
                            {{ formatDate(day) }}
                            <span class="ml-2 flex-grow border-t border-gray-200"></span>
                        </h3>
                        <div class="space-y-3">
                            <div v-for="item in dayItems" :key="item.menu.id" class="flex justify-between items-start bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                                <div class="flex-1 pr-4">
                                    <h4 class="text-sm font-bold text-gray-900 mb-1 leading-tight">{{ item.menu.name }}</h4>
                                    <p class="text-xs font-medium text-gray-500">
                                        {{ formatPrice(item.menu.price) }} <span class="text-gray-300 mx-1">&times;</span> {{ item.quantity }} porsi
                                    </p>
                                </div>
                                <div class="text-sm font-black text-gray-900">
                                    {{ formatPrice(item.menu.price * item.quantity) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Breakdown -->
            <div class="p-6 bg-white border-t border-gray-100 shadow-[0_-4px_10px_-1px_rgba(0,0,0,0.05)] space-y-3">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between items-center text-gray-600">
                        <span>Subtotal</span>
                        <span class="font-bold text-gray-900">{{ formattedSubtotal }}</span>
                    </div>

                    <div v-if="discountAmount > 0" class="flex justify-between items-center text-red-600 font-bold bg-red-50 px-3 py-1.5 rounded-lg">
                        <span class="flex items-center">
                            <Sparkles class="w-4 h-4 mr-1 text-red-500" /> Diskon Ultah 20%
                        </span>
                        <span>-{{ formattedDiscount }}</span>
                    </div>

                    <div class="border-t border-gray-100 pt-2 flex justify-between items-end">
                        <span class="text-gray-700 font-bold">Total Pembayaran</span>
                        <span class="text-2xl font-black text-red-600">{{ formattedFinalTotal }}</span>
                    </div>
                </div>

                <button 
                    @click="emit('checkout')"
                    :disabled="items.filter(i => i.quantity > 0).length === 0"
                    class="w-full py-3.5 px-4 bg-red-600 hover:bg-red-700 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed text-white text-[15px] font-bold rounded-xl shadow-md hover:shadow-lg transition-all active:scale-[0.98] flex items-center justify-center mt-2"
                >
                    Lanjut ke Pembayaran
                </button>
            </div>
        </div>
    </div>
</template>
