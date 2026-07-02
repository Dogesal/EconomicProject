<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import BaseSelect from '@/Components/BaseSelect.vue';
import FormField from '@/Components/FormField.vue';

const props = defineProps({
    debt: { type: Object, required: true },
    accounts: { type: Array, required: true },
});

const emit = defineEmits(['saved']);

// Payments are same-currency only (see RecordDebtPayment).
const eligibleAccounts = computed(() => props.accounts.filter((a) => a.currency === props.debt.remaining.currency));

const isCollection = computed(() => props.debt.direction === 'owed_to_me');

const form = useForm({
    account_id: eligibleAccounts.value[0]?.id ?? '',
    amount: '',
    occurred_on: new Date().toISOString().slice(0, 10),
});

const submit = () => {
    form.post(`/debts/${props.debt.id}/pay`, {
        preserveScroll: true,
        onSuccess: () => emit('saved'),
    });
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <FormField
            :label="isCollection ? 'Cuenta donde entra la plata' : 'Cuenta de donde sale la plata'"
            :error="form.errors.account_id"
            :hint="!eligibleAccounts.length ? `No hay ninguna cuenta en ${debt.remaining.currency}.` : ''"
        >
            <BaseSelect v-model="form.account_id">
                <option v-for="a in eligibleAccounts" :key="a.id" :value="a.id">
                    {{ a.name }} ({{ a.currentBalance.formatted }})
                </option>
            </BaseSelect>
        </FormField>

        <FormField
            :label="`Monto (${debt.remaining.currency})`"
            :error="form.errors.amount"
            :hint="`Restante: ${debt.remaining.formatted}`"
        >
            <BaseInput v-model="form.amount" type="number" step="0.01" min="0" inputmode="decimal" />
        </FormField>

        <FormField label="Fecha" :error="form.errors.occurred_on">
            <BaseInput v-model="form.occurred_on" type="date" />
        </FormField>

        <BaseButton type="submit" block :processing="form.processing" :disabled="!eligibleAccounts.length">
            {{ isCollection ? 'Registrar cobro' : 'Registrar pago' }}
        </BaseButton>
    </form>
</template>
