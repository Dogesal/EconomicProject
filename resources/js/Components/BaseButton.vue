<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' }, // primary | secondary | danger | ghost
    size: { type: String, default: 'md' }, // sm | md
    block: { type: Boolean, default: false },
    processing: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

const variantClasses = {
    primary: 'bg-brand-500 text-white shadow-sm shadow-brand-500/25 hover:bg-brand-600 active:bg-brand-700',
    secondary: 'bg-muted text-ink hover:bg-line active:bg-line',
    danger: 'bg-neg text-white shadow-sm hover:brightness-110 active:brightness-95',
    ghost: 'text-ink-soft hover:bg-muted hover:text-ink',
};

const sizeClasses = {
    sm: 'px-3.5 py-2 text-xs',
    md: 'px-5 py-2.5 text-sm',
};

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-full font-semibold transition-all active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50',
    variantClasses[props.variant],
    sizeClasses[props.size],
    props.block ? 'w-full' : '',
]);
</script>

<template>
    <button :type="type" :class="classes" :disabled="disabled || processing">
        <svg v-if="processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z" />
        </svg>
        <slot />
    </button>
</template>
