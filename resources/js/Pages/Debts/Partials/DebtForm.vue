<script setup>
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@/Components/BaseButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import FormField from '@/Components/FormField.vue';
import SegmentedControl from '@/Components/SegmentedControl.vue';

const emit = defineEmits(['saved']);

const form = useForm({
    name: '',
    direction: 'i_owe',
    amount: '',
    currency: 'PEN',
    due_date: '',
});

const directionOptions = [
    { value: 'i_owe', label: 'Debo', activeClass: 'text-rose-600 dark:text-rose-400' },
    { value: 'owed_to_me', label: 'Me deben', activeClass: 'text-emerald-600 dark:text-emerald-400' },
];

const submit = () => {
    form.transform((data) => ({ ...data, due_date: data.due_date || null })).post('/debts', {
        preserveScroll: true,
        onSuccess: () => emit('saved'),
    });
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <SegmentedControl v-model="form.direction" :options="directionOptions" />

        <FormField
            :label="form.direction === 'i_owe' ? 'A quién le debo / concepto' : 'Quién me debe / concepto'"
            :error="form.errors.name"
        >
            <BaseInput v-model="form.name" type="text" />
        </FormField>

        <div class="grid grid-cols-2 gap-3">
            <FormField label="Monto" :error="form.errors.amount">
                <BaseInput v-model="form.amount" type="number" step="0.01" min="0" inputmode="decimal" />
            </FormField>
            <FormField label="Moneda" :error="form.errors.currency">
                <BaseInput v-model="form.currency" maxlength="3" class="uppercase" />
            </FormField>
        </div>

        <FormField label="Fecha de vencimiento (opcional)" :error="form.errors.due_date">
            <BaseInput v-model="form.due_date" type="date" />
        </FormField>

        <BaseButton type="submit" block :processing="form.processing">Registrar deuda</BaseButton>
    </form>
</template>
