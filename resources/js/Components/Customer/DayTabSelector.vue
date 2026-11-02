<script setup>
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
    <div class="grid grid-cols-5 gap-1.5">
        <button
            v-for="day in days"
            :key="day.id"
            @click="!day.disabled && $emit('update:modelValue', day.id)"
            :disabled="day.disabled"
            :class="[
                'min-w-0 rounded-lg border px-1 py-2 text-xs font-semibold transition-all',
                modelValue === day.id && !day.disabled
                    ? 'bg-blue-600 text-white border-blue-600 shadow-md'
                    : 'bg-white text-gray-600 border-gray-200',
                !day.disabled && modelValue !== day.id ? 'hover:bg-gray-50 hover:border-gray-300' : '',
                day.disabled ? 'cursor-not-allowed bg-gray-100 text-gray-400 opacity-70' : ''
            ]"
        >
            {{ day.label }}
        </button>
    </div>
</template>
