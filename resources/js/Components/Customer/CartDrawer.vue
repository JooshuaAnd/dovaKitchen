<script setup>
import { computed } from 'vue';
import { X, ShoppingBag } from 'lucide-vue-next';

const props = defineProps({
    isOpen: { type: Boolean, required: true },
    items: { type: Array, required: true },
});

const emit = defineEmits(['close', 'checkout']);
const subtotal = computed(() => props.items.reduce((sum, item) => sum + (item.menu.price * item.quantity), 0));
const groupedItems = computed(() => props.items.reduce((groups, item) => {
    const date = item.menu.service_date;
    (groups[date] ??= []).push(item);
    return groups;
}, {}));

const formatPrice = (price) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(price);

const formatDate = (date) => new Date(date).toLocaleDateString('id-ID', {
    weekday: 'long', year: 'numeric', month: 'short', day: 'numeric',
});
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs" @click="emit('close')"></div>

        <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="flex w-screen max-w-md flex-col bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 p-4 sm:p-6">
                    <div class="flex items-center space-x-3">
                        <ShoppingBag class="h-6 w-6 text-red-600" />
                        <h2 class="text-lg font-black text-gray-900">Keranjang Katering</h2>
                    </div>
                    <button @click="emit('close')" class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <div class="flex-1 space-y-5 overflow-y-auto bg-gray-50/50 p-4 sm:p-6">
                    <div v-if="items.length === 0" class="flex h-64 flex-col items-center justify-center text-gray-500">
                        <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                            <ShoppingBag class="h-8 w-8 text-gray-400" />
                        </div>
                        <p class="font-bold text-gray-900">Keranjang masih kosong</p>
                        <p class="mt-1 text-sm text-gray-500">Silakan pilih menu favorit Anda di katalog.</p>
                    </div>

                    <div v-else v-for="(dayItems, day) in groupedItems" :key="day">
                        <h3 class="mb-3 flex items-center text-xs font-bold uppercase tracking-widest text-gray-500">
                            {{ formatDate(day) }}<span class="ml-2 grow border-t border-gray-200"></span>
                        </h3>
                        <div class="space-y-3">
                            <div v-for="item in dayItems" :key="item.menu.id" class="flex items-start justify-between rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                                <div class="flex-1 pr-4">
                                    <h4 class="mb-1 text-sm font-bold leading-tight text-gray-900">{{ item.menu.name }}</h4>
                                    <p class="text-xs font-medium text-gray-500">{{ formatPrice(item.menu.price) }} <span class="mx-1 text-gray-300">&times;</span> {{ item.quantity }} porsi</p>
                                </div>
                                <div class="text-sm font-black text-gray-900">{{ formatPrice(item.menu.price * item.quantity) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-3 border-t border-gray-100 bg-white p-4 sm:p-6 shadow-[0_-4px_10px_-1px_rgba(0,0,0,0.05)]">
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between text-gray-600">
                            <span>Subtotal</span><span class="font-bold text-gray-900">{{ formatPrice(subtotal) }}</span>
                        </div>
                        <div class="flex items-end justify-between border-t border-gray-100 pt-2">
                            <span class="font-bold text-gray-700">Total Sementara</span>
                            <span class="text-2xl font-black text-red-600">{{ formatPrice(subtotal) }}</span>
                        </div>
                    </div>
                    <button @click="emit('checkout')" :disabled="items.length === 0" class="mt-2 flex w-full items-center justify-center rounded-xl bg-red-600 px-4 py-3.5 text-[15px] font-bold text-white shadow-md transition-all hover:bg-red-700 hover:shadow-lg active:scale-[0.98] disabled:cursor-not-allowed disabled:bg-gray-200 disabled:text-gray-400">
                        Lanjut ke Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
