<script setup>
import { computed, ref, onMounted } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ChefHat, ChevronDown, ChevronUp } from 'lucide-vue-next';
import AllergenBadge from '@/Components/Customer/AllergenBadge.vue';

const props = defineProps({
    allergens: {
        type: Array,
        required: true,
    },
    userAllergens: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    allergen_ids: props.userAllergens.map(a => a.id) || [],
});

// Group allergens by category
const groupedAllergens = computed(() => {
    return props.allergens.reduce((acc, allergen) => {
        if (!acc[allergen.category]) {
            acc[allergen.category] = [];
        }
        acc[allergen.category].push(allergen);
        return acc;
    }, {});
});

const expandedCategory = ref(null);
const searchQueries = ref({});

const toggleCategory = (category) => {
    if (expandedCategory.value === category) {
        expandedCategory.value = null;
    } else {
        expandedCategory.value = category;
        // Focus or initialize search query if needed
        if (!searchQueries.value[category]) {
            searchQueries.value[category] = '';
        }
    }
};

const filteredItems = (category, items) => {
    const query = searchQueries.value[category] || '';
    if (!query) return items;
    return items.filter(i => i.name.toLowerCase().includes(query.toLowerCase()));
};

const getSelectedText = (category, items) => {
    const selected = items.filter(item => form.allergen_ids.includes(item.id));
    if (selected.length === 0) return `Pilih dari ${category}...`;
    if (selected.length === 1) return selected[0].name;
    return `${selected.length} item dipilih`;
};

const toggleAllergen = (allergen) => {
    const index = form.allergen_ids.indexOf(allergen.id);
    if (index === -1) {
        form.allergen_ids.push(allergen.id);
    } else {
        form.allergen_ids.splice(index, 1);
    }
};

const submit = () => {
    form.post(route('dietary-profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Dietary Profile" />
    
    <div class="min-h-screen bg-white flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <ChefHat class="mx-auto h-12 w-12 text-red-600" />
            <h2 class="mt-6 text-3xl font-black text-gray-900">Any allergies or dietary restrictions?</h2>
            <p class="mt-2 text-sm text-gray-600">
                Select the ingredients you cannot eat. We will strictly hide menus containing these allergens.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-xl">
            <div class="bg-gray-50 py-8 px-4 shadow-sm border border-gray-100 sm:rounded-2xl sm:px-10">
                <form @submit.prevent="submit">
                    <div class="space-y-6 mb-8 text-left relative">
                        <!-- Full screen overlay to close dropdowns -->
                        <div v-if="expandedCategory" @click="expandedCategory = null" class="fixed inset-0 z-0"></div>
                        
                        <div 
                            v-for="(items, category) in groupedAllergens" 
                            :key="category" 
                            class="relative"
                            :class="expandedCategory === category ? 'z-50' : 'z-10'"
                        >
                            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ category }}</label>
                            
                            <div class="relative">
                                <button 
                                    type="button"
                                    @click="toggleCategory(category)"
                                    class="w-full bg-white border border-gray-300 rounded-lg shadow-sm pl-4 pr-10 py-3 text-left cursor-default focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors"
                                    :class="{'border-blue-500 ring-2 ring-blue-500': expandedCategory === category}"
                                >
                                    <span class="block truncate text-gray-700 font-medium">
                                        {{ getSelectedText(category, items) }}
                                    </span>
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <ChevronDown class="h-5 w-5 text-gray-400 transition-transform duration-200" :class="{'transform rotate-180': expandedCategory === category}" aria-hidden="true" />
                                    </span>
                                </button>
                                
                                <transition
                                    enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95"
                                >
                                    <div v-show="expandedCategory === category" class="absolute z-20 mt-1 w-full bg-white shadow-xl max-h-60 rounded-lg py-2 text-base ring-1 ring-black ring-opacity-5 flex flex-col focus:outline-none sm:text-sm">
                                        <div class="px-3 pb-2 border-b border-gray-100">
                                            <input 
                                                type="text" 
                                                v-model="searchQueries[category]" 
                                                placeholder="Ketik untuk mencari..." 
                                                class="w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 py-1.5"
                                                @click.stop
                                            >
                                        </div>
                                        <div class="overflow-y-auto flex-1">
                                            <div 
                                                v-for="allergen in filteredItems(category, items)" 
                                                :key="allergen.id" 
                                                class="text-gray-900 cursor-pointer select-none relative py-2.5 pl-4 pr-4 hover:bg-gray-50 flex items-center transition-colors" 
                                                @click="toggleAllergen(allergen)"
                                            >
                                                <input 
                                                    type="checkbox" 
                                                    :checked="form.allergen_ids.includes(allergen.id)" 
                                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded mr-3 pointer-events-none focus:ring-0"
                                                >
                                                <span class="font-normal block truncate" :class="{'font-medium text-blue-700': form.allergen_ids.includes(allergen.id)}">
                                                    {{ allergen.name }}
                                                </span>
                                            </div>
                                            <div v-if="filteredItems(category, items).length === 0" class="px-4 py-3 text-sm text-gray-500 text-center">
                                                Tidak ditemukan
                                            </div>
                                        </div>
                                    </div>
                                </transition>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between mt-8">
                        <button 
                            type="button" 
                            @click="form.allergen_ids = []" 
                            class="text-sm font-semibold text-gray-500 hover:text-gray-900"
                        >
                            Clear all
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="inline-flex justify-center rounded-xl border border-transparent bg-blue-600 py-3 px-8 text-sm font-bold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                        >
                            {{ form.processing ? 'Saving...' : 'Save & Continue' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
