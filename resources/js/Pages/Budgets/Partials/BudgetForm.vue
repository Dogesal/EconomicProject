<script setup>
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@/Components/BaseButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import BaseSelect from '@/Components/BaseSelect.vue';
import FormField from '@/Components/FormField.vue';

const props = defineProps({
    expenseCategories: { type: Array, required: true },
    currency: { type: String, required: true },
    period: { type: Object, required: true },
});

const emit = defineEmits(['saved']);

const form = useForm({
    category_id: props.expenseCategories[0]?.id ?? '',
    amount: '',
    period_year: props.period.year,
    period_month: props.period.month,
});

const submit = () => {
    form.post('/budgets', {
        preserveScroll: true,
        onSuccess: () => emit('saved'),
    });
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <FormField label="Categoría" :error="form.errors.category_id">
            <BaseSelect v-model="form.category_id">
                <option v-for="c in expenseCategories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </BaseSelect>
        </FormField>

        <FormField :label="`Monto mensual (${currency})`" :error="form.errors.amount">
            <BaseInput v-model="form.amount" type="number" step="0.01" min="0" inputmode="decimal" />
        </FormField>

        <BaseButton type="submit" block :processing="form.processing">Guardar presupuesto</BaseButton>
    </form>
</template>
