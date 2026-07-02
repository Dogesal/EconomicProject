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
                class="flex h-9 items-center justify-center rounded-lg text-lg transition-colors"
                :class="
                    model === emoji
                        ? 'bg-indigo-100 ring-2 ring-indigo-500 dark:bg-indigo-500/20 dark:ring-indigo-400'
                        : 'bg-slate-50 hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700'
                "
                @click="select(emoji)"
            >
                {{ emoji }}
            </button>
        </div>

        <button
            type="button"
            class="mt-2 text-xs font-medium text-indigo-600 dark:text-indigo-400"
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
            <span class="text-xs text-slate-400 dark:text-slate-500">Tocá y elegí cualquier emoji del teclado.</span>
        </div>
    </div>
</template>
