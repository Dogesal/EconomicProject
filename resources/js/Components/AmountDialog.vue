<script setup>
import { nextTick, ref, watch } from 'vue';
import BaseButton from './BaseButton.vue';
import BaseInput from './BaseInput.vue';
import FormField from './FormField.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, required: true },
    currency: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirmar' },
    processing: { type: Boolean, default: false },
    error: { type: String, default: '' },
    /** Optional upper bound; shows a hint and a quick-fill button. */
    max: { type: Number, default: null },
    maxHint: { type: String, default: '' },
});

const emit = defineEmits(['submit', 'cancel']);

const amount = ref('');
const input = ref(null);

watch(
    () => props.open,
    async (open) => {
        if (open) {
            amount.value = '';
            await nextTick();
            input.value?.$el?.focus();
        }
    },
);

const submit = () => {
    if (amount.value === '' || Number(amount.value) <= 0) {
        return;
    }

    if (props.max !== null && Number(amount.value) > props.max) {
        return;
    }

    emit('submit', amount.value);
};
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
                <form class="w-full max-w-xs rounded-2xl bg-card p-5 shadow-2xl dark:bg-card" role="dialog" aria-modal="true" @submit.prevent="submit">
                    <h2 class="text-base font-semibold text-ink">{{ title }}</h2>
                    <div class="mt-3">
                        <FormField :label="currency ? `Monto (${currency})` : 'Monto'" :error="error" :hint="maxHint">
                            <div class="flex gap-2">
                                <BaseInput
                                    ref="input"
                                    v-model="amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    :max="max ?? undefined"
                                    inputmode="decimal"
                                    class="flex-1"
                                />
                                <BaseButton v-if="max !== null" variant="secondary" size="sm" @click="amount = max">
                                    Todo
                                </BaseButton>
                            </div>
                        </FormField>
                        <p v-if="max !== null && amount !== '' && Number(amount) > max" class="mt-1 text-xs text-neg">
                            El máximo es {{ max }}.
                        </p>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <BaseButton variant="secondary" @click="emit('cancel')">Cancelar</BaseButton>
                        <BaseButton type="submit" :processing="processing">{{ confirmLabel }}</BaseButton>
                    </div>
                </form>
            </div>
        </Transition>
    </Teleport>
</template>
