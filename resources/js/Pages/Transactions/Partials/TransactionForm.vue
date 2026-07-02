<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import BaseSelect from '@/Components/BaseSelect.vue';
import FormField from '@/Components/FormField.vue';
import SegmentedControl from '@/Components/SegmentedControl.vue';

const props = defineProps({
    accounts: { type: Array, required: true },
    categories: { type: Array, required: true },
    /** Existing transaction (edit mode) or null (create mode). */
    transaction: { type: Object, default: null },
});

const emit = defineEmits(['saved']);

const isEdit = computed(() => props.transaction !== null);

const form = useForm({
    account_id: props.transaction?.accountId ?? props.accounts[0]?.id ?? '',
    type: props.transaction?.type ?? 'expense',
    amount: props.transaction ? String(props.transaction.amount.decimal) : '',
    category_id: props.transaction?.categoryId ?? '',
    description: props.transaction?.description ?? '',
    occurred_on: props.transaction?.occurredOn ?? new Date().toISOString().slice(0, 10),
});

const typeOptions = [
    { value: 'expense', label: 'Gasto', activeClass: 'text-rose-600 dark:text-rose-400' },
    { value: 'income', label: 'Ingreso', activeClass: 'text-emerald-600 dark:text-emerald-400' },
];

const filteredCategories = computed(() => props.categories.filter((c) => c.type === form.type));

const selectedAccount = computed(() => props.accounts.find((a) => a.id === form.account_id) ?? null);

const balanceHint = computed(() =>
    form.type === 'expense' && selectedAccount.value
        ? `Disponible: ${selectedAccount.value.currentBalance.formatted}`
        : '',
);

const submit = () => {
    const request = form.transform((data) => ({ ...data, category_id: data.category_id || null }));

    if (isEdit.value) {
        request.put(`/transactions/${props.transaction.id}`, {
            preserveScroll: true,
            onSuccess: () => emit('saved'),
        });
    } else {
        request.post('/transactions', {
            preserveScroll: true,
            onSuccess: () => emit('saved'),
        });
    }
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <SegmentedControl v-if="!isEdit" v-model="form.type" :options="typeOptions" />

        <FormField label="Cuenta" :error="form.errors.account_id">
            <BaseSelect v-model="form.account_id" :disabled="isEdit">
                <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.name }} ({{ a.currency }})</option>
            </BaseSelect>
        </FormField>

        <FormField label="Monto" :error="form.errors.amount" :hint="balanceHint">
            <BaseInput v-model="form.amount" type="number" step="0.01" min="0" inputmode="decimal" />
        </FormField>

        <FormField label="Categoría" :error="form.errors.category_id">
            <BaseSelect v-model="form.category_id">
                <option value="">Sin categoría</option>
                <option v-for="c in filteredCategories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </BaseSelect>
        </FormField>

        <div class="grid grid-cols-2 gap-3">
            <FormField label="Fecha" :error="form.errors.occurred_on">
                <BaseInput v-model="form.occurred_on" type="date" />
            </FormField>
            <FormField label="Descripción" :error="form.errors.description">
                <BaseInput v-model="form.description" type="text" />
            </FormField>
        </div>

        <BaseButton type="submit" block :processing="form.processing">
            {{ isEdit ? 'Guardar cambios' : 'Registrar movimiento' }}
        </BaseButton>
    </form>
</template>
