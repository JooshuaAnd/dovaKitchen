<script setup>
defineProps({
    allergen: {
        type: Object,
        required: true
    },
    active: {
        type: Boolean,
        default: false
    }
});

defineEmits(['toggle']);
</script>

<template>
    <button
        type="button"
        @click="$emit('toggle', allergen)"
        :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition-colors border outline-none focus:ring-2 focus:ring-offset-2',
            active 
                ? 'bg-red-50 text-red-700 border-red-200 focus:ring-red-500 hover:bg-red-100' 
                : 'bg-white text-gray-700 border-gray-300 focus:ring-gray-300 hover:bg-gray-50'
        ]"
    >
        {{ allergen.name }}
    </button>
</template>
