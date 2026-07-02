<script setup>
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@/Components/BaseButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import BaseSelect from '@/Components/BaseSelect.vue';
import FormField from '@/Components/FormField.vue';
import SegmentedControl from '@/Components/SegmentedControl.vue';

const props = defineProps({
    accounts: { type: Array, required: true },
    categories: { type: Array, required: true },
});

const emit = defineEmits(['saved']);

const form = useForm({
    account_id: props.accounts[0]?.id ?? '',
    category_id: '',
    type: 'expense',
    amount: '',
    description: '',
    frequency: 'monthly',
    interval: 1,
    next_run_on: new Date().toISOString().slice(0, 10),
    end_on: '',
});

const typeOptions = [
    { value: 'expense', label: 'Gasto', activeClass: 'text-rose-600 dark:text-rose-400' },
    { value: 'income', label: 'Ingreso', activeClass: 'text-emerald-600 dark:text-emerald-400' },
];

const frequencies = [
    { value: 'daily', label: 'Diaria' },
    { value: 'weekly', label: 'Semanal' },
    { value: 'monthly', label: 'Mensual' },
    { value: 'yearly', label: 'Anual' },
];

const submit = () => {
    form.transform((data) => ({ ...data, category_id: data.category_id || null, end_on: data.end_on || null }))
        .post('/recurring', {
            preserveScroll: true,
            onSuccess: () => emit('saved'),
        });
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <SegmentedControl v-model="form.type" :options="typeOptions" />

        <FormField label="Cuenta" :error="form.errors.account_id">
            <BaseSelect v-model="form.account_id">
                <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }} ({{ a.currency }})</option>
            </BaseSelect>
        </FormField>

        <div class="grid grid-cols-2 gap-3">
            <FormField label="Monto" :error="form.errors.amount">
                <BaseInput v-model="form.amount" type="number" step="0.01" min="0" inputmode="decimal" />
            </FormField>
            <FormField label="Descripción" :error="form.errors.description">
                <BaseInput v-model="form.description" type="text" />
            </FormField>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <FormField label="Frecuencia" :error="form.errors.frequency">
                <BaseSelect v-model="form.frequency">
                    <option v-for="f in frequencies" :key="f.value" :value="f.value">{{ f.label }}</option>
                </BaseSelect>
            </FormField>
            <FormField label="Cada N períodos" :error="form.errors.interval">
                <BaseInput v-model="form.interval" type="number" min="1" />
            </FormField>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <FormField label="Próxima ejecución" :error="form.errors.next_run_on">
                <BaseInput v-model="form.next_run_on" type="date" />
            </FormField>
            <FormField label="Termina (opcional)" :error="form.errors.end_on">
                <BaseInput v-model="form.end_on" type="date" />
            </FormField>
        </div>

        <BaseButton type="submit" block :processing="form.processing">Guardar recurrente</BaseButton>
    </form>
</template>
