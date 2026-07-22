<script setup>
import { computed } from 'vue';

const props = defineProps({
    percentage: { type: Number, required: true },
    /** Tailwind class for the bar; ignored when `color` is set. */
    barClass: { type: String, default: 'bg-brand-500' },
    /** Raw CSS color (e.g. category hex color). */
    color: { type: String, default: '' },
    /** `md` = barra del mockup (8px); `sm` para listas compactas. */
    size: { type: String, default: 'md' },
});

const width = computed(() => `${Math.min(100, Math.max(0, props.percentage))}%`);
</script>

<template>
    <div class="w-full overflow-hidden rounded-full bg-line" :class="size === 'sm' ? 'h-1.5' : 'h-2'">
        <div
            class="h-full rounded-full transition-all duration-500"
            :class="color ? '' : barClass"
            :style="{ width, ...(color ? { backgroundColor: color } : {}) }"
        />
    </div>
</template>
