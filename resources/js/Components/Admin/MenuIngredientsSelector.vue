<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { 
    ChevronDown, 
    Search, 
    Plus, 
    X, 
    Check, 
    ShieldAlert, 
    Sparkles, 
    RotateCcw,
    CheckCircle2,
    Info
} from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => []
    },
    allergens: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:modelValue']);

// Local reactive allergens list (allows appending newly created ones)
const allAllergens = ref([...props.allergens]);

// Category presentation configurations
const categoryMeta = {
    'Seafood': {
        label: 'Seafood (Makanan Laut)',
        icon: '🦐',
        accentBg: 'bg-cyan-50',
        accentBorder: 'border-cyan-200',
        badgeBg: 'bg-cyan-100 text-cyan-800 border-cyan-200',
        chipBg: 'bg-cyan-50 text-cyan-800 border-cyan-300 hover:bg-cyan-100'
    },
    'Meat': {
        label: 'Daging & Unggas (Meat)',
        icon: '🥩',
        accentBg: 'bg-rose-50',
        accentBorder: 'border-rose-200',
        badgeBg: 'bg-rose-100 text-rose-800 border-rose-200',
        chipBg: 'bg-rose-50 text-rose-800 border-rose-300 hover:bg-rose-100'
    },
    'Dairy & Telur': {
        label: 'Dairy & Telur (Susu, Olahan, Telur)',
        icon: '🥛',
        accentBg: 'bg-amber-50',
        accentBorder: 'border-amber-200',
        badgeBg: 'bg-amber-100 text-amber-800 border-amber-200',
        chipBg: 'bg-amber-50 text-amber-800 border-amber-300 hover:bg-amber-100'
    },
    'Kacang & Biji': {
        label: 'Kacang & Biji-bijian',
        icon: '🥜',
        accentBg: 'bg-orange-50',
        accentBorder: 'border-orange-200',
        badgeBg: 'bg-orange-100 text-orange-800 border-orange-200',
        chipBg: 'bg-orange-50 text-orange-800 border-orange-300 hover:bg-orange-100'
    },
    'Gluten & Gandum': {
        label: 'Gluten & Gandum (Tepung, Mi, Roti)',
        icon: '🌾',
        accentBg: 'bg-yellow-50',
        accentBorder: 'border-yellow-200',
        badgeBg: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        chipBg: 'bg-yellow-50 text-yellow-800 border-yellow-300 hover:bg-yellow-100'
    },
    'Seasoning': {
        label: 'Bumbu & Penyedap (Seasoning)',
        icon: '🧂',
        accentBg: 'bg-emerald-50',
        accentBorder: 'border-emerald-200',
        badgeBg: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        chipBg: 'bg-emerald-50 text-emerald-800 border-emerald-300 hover:bg-emerald-100'
    },
    'Lainnya': {
        label: 'Preferensi Rasa & Lainnya',
        icon: '🌶️',
        accentBg: 'bg-purple-50',
        accentBorder: 'border-purple-200',
        badgeBg: 'bg-purple-100 text-purple-800 border-purple-200',
        chipBg: 'bg-purple-50 text-purple-800 border-purple-300 hover:bg-purple-100'
    }
};

const getCategoryMeta = (category) => {
    return categoryMeta[category] || {
        label: category,
        icon: '🥗',
        accentBg: 'bg-gray-50',
        accentBorder: 'border-gray-200',
        badgeBg: 'bg-gray-100 text-gray-800 border-gray-200',
        chipBg: 'bg-gray-50 text-gray-800 border-gray-300 hover:bg-gray-100'
    };
};

// Desired category display ordering
const orderedCategories = [
    'Seafood',
    'Meat',
    'Dairy & Telur',
    'Kacang & Biji',
    'Gluten & Gandum',
    'Seasoning',
    'Lainnya'
];

// Group allergens by category
const groupedAllergens = computed(() => {
    const groups = {};
    
    // Initialize ordered categories
    orderedCategories.forEach(cat => {
        groups[cat] = [];
    });

    allAllergens.value.forEach(allergen => {
        const cat = allergen.category || 'Lainnya';
        if (!groups[cat]) {
            groups[cat] = [];
        }
        groups[cat].push(allergen);
    });

    // Remove empty groups if any
    Object.keys(groups).forEach(key => {
        if (groups[key].length === 0 && !orderedCategories.includes(key)) {
            delete groups[key];
        }
    });

    return groups;
});

// Dropdown and search states
const openCategory = ref(null);
const searchQueries = ref({});

const toggleCategory = (category) => {
    if (openCategory.value === category) {
        openCategory.value = null;
    } else {
        openCategory.value = category;
        if (!searchQueries.value[category]) {
            searchQueries.value[category] = '';
        }
    }
};

// Filtered items per category dropdown
const getFilteredItems = (category, items) => {
    const q = (searchQueries.value[category] || '').trim().toLowerCase();
    if (!q) return items;
    return items.filter(item => item.name.toLowerCase().includes(q));
};

// Helper to check selection
const isSelected = (id) => {
    return props.modelValue.includes(id);
};

// Toggle a single allergen selection
const toggleAllergen = (id) => {
    const updated = [...props.modelValue];
    const idx = updated.indexOf(id);
    if (idx === -1) {
        updated.push(id);
    } else {
        updated.splice(idx, 1);
    }
    emit('update:modelValue', updated);
};

// Quick select/deselect per category
const selectAllInCategory = (category, items) => {
    const itemIds = items.map(i => i.id);
    const set = new Set([...props.modelValue, ...itemIds]);
    emit('update:modelValue', Array.from(set));
};

const deselectAllInCategory = (category, items) => {
    const itemIds = new Set(items.map(i => i.id));
    const updated = props.modelValue.filter(id => !itemIds.has(id));
    emit('update:modelValue', updated);
};

const getSelectedInCategory = (items) => {
    return items.filter(item => isSelected(item.id));
};

// Summary of all selected allergens
const selectedAllergensList = computed(() => {
    return allAllergens.value.filter(a => isSelected(a.id));
});

const clearAll = () => {
    if (confirm('Apakah Anda yakin ingin menghapus semua bahan baku yang dipilih?')) {
        emit('update:modelValue', []);
    }
};

// Quick Add Ingredient Modal
const showAddModal = ref(false);
const newIngredientName = ref('');
const newIngredientCategory = ref('Seafood');
const isSubmittingNew = ref(false);
const addErrorMsg = ref('');

const openAddModal = (defaultCategory = 'Seafood') => {
    newIngredientName.value = '';
    newIngredientCategory.value = defaultCategory;
    addErrorMsg.value = '';
    showAddModal.value = true;
};

const submitNewIngredient = async () => {
    if (!newIngredientName.value.trim()) {
        addErrorMsg.value = 'Nama bahan baku wajib diisi.';
        return;
    }

    isSubmittingNew.value = true;
    addErrorMsg.value = '';

    try {
        const response = await window.axios.post(window.route('admin.allergens.store'), {
            name: newIngredientName.value.trim(),
            category: newIngredientCategory.value
        });

        if (response.data && response.data.allergen) {
            const created = response.data.allergen;
            // Add to local list
            allAllergens.value.push(created);
            // Auto-select this newly created ingredient
            const updated = [...props.modelValue, created.id];
            emit('update:modelValue', updated);
            
            showAddModal.value = false;
            newIngredientName.value = '';
        }
    } catch (err) {
        if (err.response && err.response.data && err.response.data.message) {
            addErrorMsg.value = err.response.data.message;
        } else {
            addErrorMsg.value = 'Gagal menambahkan bahan baku. Silakan coba lagi.';
        }
    } finally {
        isSubmittingNew.value = false;
    }
};

// Close dropdown on click outside
const closeOnOutsideClick = (e) => {
    if (openCategory.value && !e.target.closest('.category-combobox-container')) {
        openCategory.value = null;
    }
};

onMounted(() => {
    document.addEventListener('click', closeOnOutsideClick);
});

onUnmounted(() => {
    document.removeEventListener('click', closeOnOutsideClick);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header Info -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-200 gap-2">
            <div>
                <label class="block text-base font-bold text-gray-900">
                    Bahan Baku & Profil Dietary
                </label>
                <p class="text-xs text-gray-500 mt-0.5">
                    Pilih bahan baku spesifik berdasarkan kategori untuk pencocokan otomatis dengan pantangan/alergi kustomer.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    @click="openAddModal()" 
                    class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg border border-blue-200 transition-colors shadow-xs"
                >
                    <Plus class="w-3.5 h-3.5 mr-1" />
                    Tambah Bahan Baru
                </button>
            </div>
        </div>

        <!-- Combo Boxes Grid (Categorized) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div 
                v-for="(items, category) in groupedAllergens" 
                :key="category" 
                class="category-combobox-container relative bg-white rounded-xl border border-gray-200 hover:border-gray-300 transition-all shadow-xs"
                :class="{'ring-2 ring-blue-500/20 border-blue-400': openCategory === category}"
            >
                <!-- Category Box Trigger -->
                <div class="p-3.5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="inline-flex items-center text-sm font-bold text-gray-800">
                            <span class="text-base mr-1.5">{{ getCategoryMeta(category).icon }}</span>
                            {{ getCategoryMeta(category).label }}
                        </span>
                        
                        <span 
                            v-if="getSelectedInCategory(items).length > 0" 
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold"
                            :class="getCategoryMeta(category).badgeBg"
                        >
                            {{ getSelectedInCategory(items).length }} dipilih
                        </span>
                        <span v-else class="text-xs text-gray-400 font-medium">
                            0 dipilih
                        </span>
                    </div>

                    <!-- Combo Box Button -->
                    <button 
                        type="button"
                        @click.stop="toggleCategory(category)"
                        class="w-full flex items-center justify-between px-3.5 py-2.5 text-left text-sm bg-gray-50 hover:bg-gray-100/80 border border-gray-200 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <span class="truncate text-gray-700 font-medium text-xs sm:text-sm">
                            <template v-if="getSelectedInCategory(items).length === 0">
                                Pilih bahan {{ category }}...
                            </template>
                            <template v-else-if="getSelectedInCategory(items).length <= 2">
                                {{ getSelectedInCategory(items).map(i => i.name).join(', ') }}
                            </template>
                            <template v-else>
                                {{ getSelectedInCategory(items).slice(0, 2).map(i => i.name).join(', ') }} +{{ getSelectedInCategory(items).length - 2 }} lainnya
                            </template>
                        </span>
                        <ChevronDown 
                            class="w-4 h-4 text-gray-400 ml-2 shrink-0 transition-transform duration-200"
                            :class="{'rotate-180 text-blue-600': openCategory === category}"
                        />
                    </button>

                    <!-- Selected Chips in this Category -->
                    <div v-if="getSelectedInCategory(items).length > 0" class="flex flex-wrap gap-1.5 mt-2.5">
                        <span 
                            v-for="item in getSelectedInCategory(items)" 
                            :key="item.id"
                            class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium border transition-colors group"
                            :class="getCategoryMeta(category).chipBg"
                        >
                            <span>{{ item.name }}</span>
                            <button 
                                type="button" 
                                @click.stop="toggleAllergen(item.id)" 
                                class="ml-1.5 opacity-60 hover:opacity-100 hover:text-red-700 focus:outline-none"
                                title="Hapus bahan"
                            >
                                <X class="w-3 h-3" />
                            </button>
                        </span>
                    </div>
                </div>

                <!-- Dropdown Card -->
                <transition
                    enter-active-class="transition ease-out duration-150"
                    enter-from-class="transform opacity-0 scale-95"
                    enter-to-class="transform opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="transform opacity-100 scale-100"
                    leave-to-class="transform opacity-0 scale-95"
                >
                    <div 
                        v-if="openCategory === category" 
                        class="absolute left-0 right-0 top-full mt-1.5 z-40 bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden"
                    >
                        <!-- Search & Quick Action Header -->
                        <div class="p-2.5 bg-gray-50 border-b border-gray-100 space-y-2">
                            <div class="relative">
                                <Search class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-gray-400" />
                                <input 
                                    v-model="searchQueries[category]" 
                                    type="text" 
                                    :placeholder="'Cari ' + category + '...'" 
                                    class="w-full pl-8 pr-3 py-1.5 text-xs bg-white border border-gray-200 rounded-md focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
                                    @click.stop
                                >
                            </div>
                            <div class="flex items-center justify-between text-xs px-1">
                                <span class="text-gray-500 font-medium">
                                    {{ getFilteredItems(category, items).length }} bahan tersedia
                                </span>
                                <div class="space-x-2">
                                    <button 
                                        type="button" 
                                        @click.stop="selectAllInCategory(category, items)"
                                        class="text-blue-600 hover:text-blue-800 font-semibold"
                                    >
                                        Pilih Semua
                                    </button>
                                    <span class="text-gray-300">|</span>
                                    <button 
                                        type="button" 
                                        @click.stop="deselectAllInCategory(category, items)"
                                        class="text-gray-500 hover:text-gray-700"
                                    >
                                        Batal Semua
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Checkbox List -->
                        <div class="max-h-56 overflow-y-auto p-1.5 divide-y divide-gray-50">
                            <label 
                                v-for="item in getFilteredItems(category, items)" 
                                :key="item.id"
                                class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-50 cursor-pointer select-none transition-colors"
                                :class="{'bg-blue-50/70 text-blue-900 font-medium': isSelected(item.id)}"
                                @click.stop
                            >
                                <input 
                                    type="checkbox" 
                                    :checked="isSelected(item.id)" 
                                    @change="toggleAllergen(item.id)"
                                    class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 cursor-pointer"
                                >
                                <span class="ml-2.5 text-xs sm:text-sm text-gray-700 flex-1" :class="{'text-blue-900 font-semibold': isSelected(item.id)}">
                                    {{ item.name }}
                                </span>
                                <Check v-if="isSelected(item.id)" class="w-4 h-4 text-blue-600 ml-2 shrink-0" />
                            </label>

                            <div v-if="getFilteredItems(category, items).length === 0" class="py-6 text-center text-xs text-gray-500">
                                Tidak ada bahan yang cocok dengan pencarian.
                            </div>
                        </div>

                        <!-- Bottom add shortcut -->
                        <div class="p-2 bg-gray-50 border-t border-gray-100 flex items-center justify-between text-xs">
                            <button 
                                type="button" 
                                @click.stop="openAddModal(category)"
                                class="text-blue-600 hover:text-blue-800 font-semibold flex items-center"
                            >
                                <Plus class="w-3 h-3 mr-1" /> Tambah bahan di {{ category }}
                            </button>
                            <button 
                                type="button" 
                                @click.stop="openCategory = null"
                                class="px-2.5 py-1 bg-white hover:bg-gray-100 border border-gray-200 rounded font-medium text-gray-600"
                            >
                                Selesai
                            </button>
                        </div>
                    </div>
                </transition>
            </div>
        </div>


        <!-- Add Ingredient Modal -->
        <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center">
                        <Plus class="w-5 h-5 mr-1.5 text-blue-600" />
                        Tambah Bahan Baku Baru
                    </h3>
                    <button 
                        type="button" 
                        @click="showAddModal = false" 
                        class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div v-if="addErrorMsg" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700 font-medium">
                    {{ addErrorMsg }}
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            Nama Bahan Baku <span class="text-red-500">*</span>
                        </label>
                        <input 
                            v-model="newIngredientName" 
                            type="text" 
                            placeholder="Contoh: Lobster, Jamur Enoki, Keju Mozzarella..."
                            class="w-full text-sm border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                            @keyup.enter="submitNewIngredient"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            Kategori <span class="text-red-500">*</span>
                        </label>
                        <select 
                            v-model="newIngredientCategory" 
                            class="w-full text-sm border-gray-300 rounded-lg focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option v-for="cat in orderedCategories" :key="cat" :value="cat">
                                {{ getCategoryMeta(cat).icon }} {{ getCategoryMeta(cat).label }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end space-x-3 pt-3 border-t border-gray-100">
                    <button 
                        type="button" 
                        @click="showAddModal = false" 
                        class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        Batal
                    </button>
                    <button 
                        type="button" 
                        @click="submitNewIngredient" 
                        :disabled="isSubmittingNew || !newIngredientName.trim()"
                        class="inline-flex items-center px-4 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm disabled:opacity-50 transition-colors"
                    >
                        <span v-if="isSubmittingNew">Menyimpan...</span>
                        <span v-else>Simpan & Gunakan</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
