<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import BaseSelect from '@/Components/BaseSelect.vue';
import FormField from '@/Components/FormField.vue';

const props = defineProps({
    accountTypes: { type: Array, required: true },
    /** Existing account (edit mode) or null (create mode). */
    account: { type: Object, default: null },
});

const emit = defineEmits(['saved']);

const isEdit = computed(() => props.account !== null);

const colorOptions = ['#4f46e5', '#0891b2', '#059669', '#d97706', '#dc2626', '#9333ea', '#475569'];

const form = useForm({
    name: props.account?.name ?? '',
    type: props.account?.type ?? props.accountTypes[0]?.value ?? 'cash',
    currency: props.account?.currency ?? 'PEN',
    initial_balance: props.account?.initialBalance.decimal ?? 0,
    color: props.account?.color ?? colorOptions[0],
});

const submit = () => {
    if (isEdit.value) {
        form.put(`/accounts/${props.account.id}`, {
            preserveScroll: true,
            onSuccess: () => emit('saved'),
        });
    } else {
        form.post('/accounts', {
            preserveScroll: true,
            onSuccess: () => emit('saved'),
        });
    }
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <FormField label="Nombre" :error="form.errors.name">
            <BaseInput v-model="form.name" type="text" />
        </FormField>

        <div class="grid grid-cols-2 gap-3">
            <FormField label="Tipo" :error="form.errors.type">
                <BaseSelect v-model="form.type">
                    <option v-for="t in accountTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                </BaseSelect>
            </FormField>
            <FormField v-if="!isEdit" label="Moneda" :error="form.errors.currency">
                <BaseInput v-model="form.currency" maxlength="3" class="uppercase" />
            </FormField>
        </div>

        <FormField v-if="!isEdit" label="Saldo inicial" :error="form.errors.initial_balance">
            <BaseInput v-model="form.initial_balance" type="number" step="0.01" inputmode="decimal" />
        </FormField>

        <FormField label="Color" :error="form.errors.color">
            <div class="flex gap-2">
                <button
                    v-for="color in colorOptions"
                    :key="color"
                    type="button"
                    class="h-8 w-8 rounded-full transition-transform"
                    :class="form.color === color ? 'scale-110 ring-2 ring-slate-400 ring-offset-2 dark:ring-slate-500 dark:ring-offset-slate-900' : ''"
                    :style="{ backgroundColor: color }"
                    :aria-label="`Color ${color}`"
                    @click="form.color = color"
                />
            </div>
        </FormField>

        <BaseButton type="submit" block :processing="form.processing">
            {{ isEdit ? 'Guardar cambios' : 'Crear cuenta' }}
        </BaseButton>
    </form>
</template>
