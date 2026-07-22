<script setup>
defineProps({
    /**
     * Array of { value, label, activeClass? } where activeClass styles the
     * selected pill (defaults to the brand fill).
     */
    options: { type: Array, required: true },
});

const model = defineModel({ type: String, required: true });
</script>

<template>
    <div class="flex gap-2">
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            class="flex-1 rounded-full px-3 py-2 text-sm font-semibold transition-colors"
            :class="model === option.value
                    ? option.activeClass || 'bg-brand-500 text-white'
                    : 'bg-card text-ink-soft ring-1 ring-line'"
            @click="model = option.value"
        >
            {{ option.label }}
        </button>
    </div>
</template>
