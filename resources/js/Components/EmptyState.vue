<script setup>
defineProps({
    message: { type: String, required: true },
});
</script>

<template>
    <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-400 dark:border-slate-700 dark:text-slate-500">
        {{ message }}
        <div v-if="$slots.default" class="mt-3">
            <slot />
        </div>
    </div>
</template>
