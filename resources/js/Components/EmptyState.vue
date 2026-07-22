<script setup>
defineProps({
    message: { type: String, required: true },
});
</script>

<template>
    <div class="rounded-2xl bg-muted p-8 text-center text-sm text-ink-faint">
        {{ message }}
        <div v-if="$slots.default" class="mt-3">
            <slot />
        </div>
    </div>
</template>
