<script setup>
import { Minus, Plus, TriangleAlert } from 'lucide-vue-next';

const props = defineProps({
    menu: {
        type: Object,
        required: true
    },
    quantity: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['increment', 'decrement']);

const formattedPrice = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
}).format(props.menu.price);
</script>

<template>
    <div :class="['relative bg-white rounded-xl shadow-sm overflow-hidden flex flex-col h-full group', menu.is_recommended ? 'border-gray-100' : 'border-amber-300']">
        <!-- Photo -->
        <div class="aspect-w-16 aspect-h-9 w-full h-44 sm:h-48 bg-gray-100 relative overflow-hidden">
            <img 
                v-if="menu.photo_path" 
                :src="menu.photo_path" 
                :alt="menu.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                Tidak ada foto
            </div>
            
            <!-- Price Badge -->
            <div class="absolute bottom-2 left-2 bg-white/95 backdrop-blur-sm px-2.5 py-1 rounded-md text-sm font-bold text-gray-900 shadow-sm border border-white/20">
                {{ formattedPrice }}
            </div>
            <span v-if="!menu.is_orderable" class="absolute right-2 top-2 rounded-full bg-gray-900/90 px-2.5 py-1 text-[11px] font-bold text-white shadow-sm">
                Pemesanan ditutup
            </span>
        </div>

        <div class="p-4 flex flex-col flex-grow">
            <h3 class="text-lg font-bold text-gray-900 mb-1 leading-tight">{{ menu.name }}</h3>
            
            <p class="text-sm text-gray-500 mb-3 line-clamp-2">{{ menu.description }}</p>

            <div v-if="!menu.is_recommended" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-amber-900">
                <div class="flex items-center gap-1.5 text-xs font-bold">
                    <TriangleAlert class="h-4 w-4 shrink-0" />
                    Tidak direkomendasikan untuk Anda
                </div>
                <p class="mt-1 text-xs leading-relaxed">
                    Mengandung: {{ menu.dietary_warnings.join(', ') }}
                </p>
            </div>

            <!-- Allergens -->
            <div v-if="menu.allergens && menu.allergens.length > 0" class="flex flex-wrap gap-1 mb-4 mt-auto">
                <span 
                    v-for="allergen in menu.allergens" 
                    :key="allergen.id"
                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold tracking-wide uppercase bg-red-50 text-red-600 border border-red-100"
                >
                    {{ allergen.name }}
                </span>
            </div>
            <div v-else class="mt-auto mb-4"></div>

            <!-- Counter -->
            <div class="flex items-center justify-between mt-auto border-t border-gray-100 pt-4">
                <span class="text-sm font-semibold text-gray-700">Porsi</span>
                <div v-if="menu.is_orderable" class="flex items-center space-x-3 bg-gray-50/50 rounded-lg p-1 border border-gray-200">
                    <button 
                        @click="emit('decrement', menu)" 
                        :disabled="quantity <= 0 || !menu.is_orderable"
                        class="p-1 rounded-md bg-white border border-gray-200 text-gray-500 hover:text-gray-900 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition-colors"
                    >
                        <Minus class="w-4 h-4" />
                    </button>
                    <span class="w-6 text-center font-bold text-gray-900 text-sm">{{ quantity }}</span>
                    <button 
                        @click="emit('increment', menu)"
                        :disabled="!menu.is_orderable"
                        class="p-1 rounded-md bg-blue-600 border border-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition-colors"
                    >
                        <Plus class="w-4 h-4" />
                    </button>
                </div>
                <span v-else class="rounded-md bg-gray-100 px-2.5 py-1.5 text-xs font-semibold text-gray-500">Tidak tersedia</span>
            </div>
        </div>
    </div>
</template>
