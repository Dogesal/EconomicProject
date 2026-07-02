<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import BaseSelect from '@/Components/BaseSelect.vue';
import FormField from '@/Components/FormField.vue';

const props = defineProps({
    accounts: { type: Array, required: true },
});

const emit = defineEmits(['saved']);

const form = useForm({
    from_account_id: props.accounts[0]?.id ?? '',
    to_account_id: '',
    amount: '',
    description: '',
    occurred_on: new Date().toISOString().slice(0, 10),
});

const fromAccount = computed(() => props.accounts.find((a) => a.id === form.from_account_id));

// Transfers are same-currency only (see TransferBetweenAccounts).
const destinationAccounts = computed(() =>
    props.accounts.filter((a) => a.id !== form.from_account_id && a.currency === fromAccount.value?.currency),
);

watch(destinationAccounts, (options) => {
    if (!options.some((a) => a.id === form.to_account_id)) {
        form.to_account_id = options[0]?.id ?? '';
    }
}, { immediate: true });

const submit = () => {
    form.transform((data) => ({ ...data, description: data.description || null })).post('/transfers', {
        preserveScroll: true,
        onSuccess: () => emit('saved'),
    });
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <FormField label="Desde" :error="form.errors.from_account_id">
            <BaseSelect v-model="form.from_account_id">
                <option v-for="a in accounts" :key="a.id" :value="a.id">
                    {{ a.name }} ({{ a.currentBalance.formatted }})
                </option>
            </BaseSelect>
        </FormField>

        <FormField
            label="Hacia"
            :error="form.errors.to_account_id"
            :hint="!destinationAccounts.length ? `No hay otra cuenta en ${fromAccount?.currency ?? 'esta moneda'}.` : ''"
        >
            <BaseSelect v-model="form.to_account_id">
                <option v-for="a in destinationAccounts" :key="a.id" :value="a.id">
                    {{ a.name }} ({{ a.currentBalance.formatted }})
                </option>
            </BaseSelect>
        </FormField>

        <FormField :label="`Monto${fromAccount ? ` (${fromAccount.currency})` : ''}`" :error="form.errors.amount">
            <BaseInput v-model="form.amount" type="number" step="0.01" min="0" inputmode="decimal" />
        </FormField>

        <div class="grid grid-cols-2 gap-3">
            <FormField label="Fecha" :error="form.errors.occurred_on">
                <BaseInput v-model="form.occurred_on" type="date" />
            </FormField>
            <FormField label="Descripción" :error="form.errors.description">
                <BaseInput v-model="form.description" type="text" />
            </FormField>
        </div>

        <BaseButton type="submit" block :processing="form.processing" :disabled="!destinationAccounts.length">
            Transferir
        </BaseButton>
    </form>
</template>
