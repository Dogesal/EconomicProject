<script setup>
defineProps({
    label: { type: String, required: true },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
});
</script>

<template>
    <div>
        <label class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ label }}</label>
        <div class="mt-1">
            <slot />
        </div>
        <p v-if="error" class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ error }}</p>
        <p v-else-if="hint" class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ hint }}</p>
    </div>
</template>
