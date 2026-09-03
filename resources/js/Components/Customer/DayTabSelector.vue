<script setup>
import { Lock } from 'lucide-vue-next';

defineProps({
    days: {
        type: Array,
        required: true,
        // Example: [{ id: 'monday', label: 'Monday', disabled: false }]
    },
    modelValue: {
        type: String,
        required: true
    }
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="flex space-x-2 overflow-x-auto pb-2 scrollbar-hide">
        <button
            v-for="day in days"
            :key="day.id"
            @click="!day.disabled && $emit('update:modelValue', day.id)"
            :disabled="day.disabled"
            :class="[
                'flex items-center px-5 py-2.5 rounded-lg text-sm font-semibold transition-all whitespace-nowrap border',
                modelValue === day.id && !day.disabled
                    ? 'bg-blue-600 text-white border-blue-600 shadow-md'
                    : 'bg-white text-gray-600 border-gray-200',
                !day.disabled && modelValue !== day.id ? 'hover:bg-gray-50 hover:border-gray-300' : '',
                day.disabled ? 'opacity-60 cursor-not-allowed bg-gray-100 text-gray-400' : ''
            ]"
        >
            <Lock v-if="day.disabled" class="w-4 h-4 mr-2 text-gray-400" />
            {{ day.label }}
        </button>
    </div>
</template>
