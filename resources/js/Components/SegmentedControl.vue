<script setup>
defineProps({
    /**
     * Array of { value, label, activeClass? } where activeClass styles the
     * selected pill (defaults to an indigo accent).
     */
    options: { type: Array, required: true },
});

const model = defineModel({ type: String, required: true });
</script>

<template>
    <div class="grid gap-1 rounded-lg bg-slate-100 p-1 dark:bg-slate-800" :style="{ gridTemplateColumns: `repeat(${options.length}, 1fr)` }">
        <button
            v-for="option in options"
            :key="option.value"
            type="button"
            class="rounded-md py-1.5 text-sm font-medium transition-colors"
            :class="
                model === option.value
                    ? [option.activeClass || 'text-indigo-600 dark:text-indigo-400', 'bg-white shadow dark:bg-slate-900']
                    : 'text-slate-500 dark:text-slate-400'
            "
            @click="model = option.value"
        >
            {{ option.label }}
        </button>
    </div>
</template>
