<script setup>
import { computed } from 'vue';

const props = defineProps({
    percentage: { type: Number, required: true },
    /** Tailwind class for the bar; ignored when `color` is set. */
    barClass: { type: String, default: 'bg-indigo-500' },
    /** Raw CSS color (e.g. category hex color). */
    color: { type: String, default: '' },
});

const width = computed(() => `${Math.min(100, Math.max(0, props.percentage))}%`);
</script>

<template>
    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
        <div
            class="h-full rounded-full transition-all"
            :class="color ? '' : barClass"
            :style="{ width, ...(color ? { backgroundColor: color } : {}) }"
        />
    </div>
</template>
