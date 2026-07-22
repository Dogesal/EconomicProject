<script setup>
const model = defineModel({ type: [String, Number], default: '' });
</script>

<template>
    <select
        v-model="model"
        class="w-full appearance-none rounded-2xl bg-muted px-4 py-3 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-brand-500/40"
    >
        <slot />
    </select>
</template>
