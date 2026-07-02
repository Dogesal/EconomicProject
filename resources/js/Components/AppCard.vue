<script setup>
defineProps({
    padded: { type: Boolean, default: true },
});
</script>

<template>
    <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900" :class="padded ? 'p-4' : ''">
        <slot />
    </div>
</template>
