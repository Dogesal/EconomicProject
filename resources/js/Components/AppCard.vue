<script setup>
defineProps({
    padded: { type: Boolean, default: true },
    /** `card` = superficie elevada; `muted` = bloque gris plano del mockup. */
    tone: { type: String, default: 'card' },
});
</script>

<template>
    <div
        class="rounded-2xl"
        :class="[tone === 'muted' ? 'bg-muted' : 'bg-card shadow-sm shadow-black/5', padded ? 'p-4' : '']"
    >
        <slot />
    </div>
</template>
