<script setup>
const model = defineModel({ type: Boolean, required: true });
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="model"
        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors"
        :class="model ? 'bg-brand-500' : 'bg-line'"
        @click="model = !model"
    >
        <span
            class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"
            :class="model ? 'translate-x-5' : 'translate-x-0.5'"
        />
    </button>
</template>
