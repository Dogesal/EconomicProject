<script setup>
defineProps({
    type: { type: String, default: 'text' },
});

const model = defineModel({ type: [String, Number], default: '' });
</script>

<template>
    <input
        v-model="model"
        :type="type"
        class="w-full rounded-2xl bg-muted px-4 py-3 text-sm text-ink placeholder:text-ink-faint focus:outline-none focus:ring-2 focus:ring-brand-500/40"
    />
</template>
