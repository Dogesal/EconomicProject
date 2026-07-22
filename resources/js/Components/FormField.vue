<script setup>
defineProps({
    label: { type: String, required: true },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
});
</script>

<template>
    <div>
        <label class="text-xs font-medium text-ink-soft">{{ label }}</label>
        <div class="mt-1">
            <slot />
        </div>
        <p v-if="error" class="mt-1 text-xs text-neg">{{ error }}</p>
        <p v-else-if="hint" class="mt-1 text-xs text-ink-faint">{{ hint }}</p>
    </div>
</template>
