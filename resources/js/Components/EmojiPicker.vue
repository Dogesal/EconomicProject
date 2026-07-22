<script setup>
import { ref } from 'vue';
import BaseInput from './BaseInput.vue';

const model = defineModel({ type: String, default: '' });

const showCustom = ref(false);

const emojis = [
    // Comida y compras
    '🍽️', '🍕', '☕', '🍺', '🛒', '🎁',
    // Transporte
    '🚌', '🚗', '⛽', '✈️', '🏍️', '🚕',
    // Hogar y servicios
    '🏠', '💡', '💧', '📱', '🌐', '🧹',
    // Salud y cuidado
    '🏥', '💊', '🏋️', '💄', '👶', '🐶',
    // Ocio y educación
    '🎮', '🎬', '🎵', '📚', '🎓', '⚽',
    // Dinero y trabajo
    '💼', '💰', '💳', '🏦', '📈', '🧾',
];

const select = (emoji) => {
    model.value = model.value === emoji ? '' : emoji;
    showCustom.value = false;
};
</script>

<template>
    <div>
        <div class="grid grid-cols-6 gap-1.5">
            <button
                v-for="emoji in emojis"
                :key="emoji"
                type="button"
                class="flex h-9 items-center justify-center rounded-xl text-lg transition-colors"
                :class="model === emoji
                        ? 'bg-brand-100 ring-2 ring-brand-500 /20 dark:ring-brand-400'
                        : 'bg-muted hover:bg-muted dark:hover:bg-line'"
                @click="select(emoji)"
            >
                {{ emoji }}
            </button>
        </div>

        <button
            type="button"
            class="mt-2 text-xs font-medium text-brand-500"
            @click="showCustom = !showCustom"
        >
            {{ showCustom ? 'Ocultar' : '¿Otro? Usá el teclado de tu celu' }}
        </button>

        <div v-if="showCustom" class="mt-2 flex items-center gap-2">
            <BaseInput
                v-model="model"
                type="text"
                maxlength="4"
                placeholder="🦄"
                class="w-20 text-center text-lg"
            />
            <span class="text-xs text-ink-faint">Tocá y elegí cualquier emoji del teclado.</span>
        </div>
    </div>
</template>
