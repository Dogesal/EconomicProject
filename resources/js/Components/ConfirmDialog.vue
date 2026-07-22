<script setup>
import BaseButton from './BaseButton.vue';

defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '¿Estás seguro?' },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Eliminar' },
    cancelLabel: { type: String, default: 'Cancelar' },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-150"
            leave-active-class="transition-opacity duration-150"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-6" @click.self="emit('cancel')">
                <div class="w-full max-w-xs rounded-3xl bg-card p-5 shadow-2xl" role="alertdialog" aria-modal="true">
                    <h2 class="text-base font-bold text-ink">{{ title }}</h2>
                    <p v-if="message" class="mt-1 text-sm text-ink-soft">{{ message }}</p>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <BaseButton variant="secondary" @click="emit('cancel')">{{ cancelLabel }}</BaseButton>
                        <BaseButton variant="danger" :processing="processing" @click="emit('confirm')">{{ confirmLabel }}</BaseButton>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
