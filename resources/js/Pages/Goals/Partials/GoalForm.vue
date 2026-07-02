<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import BaseButton from '@/Components/BaseButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import BaseSelect from '@/Components/BaseSelect.vue';
import FormField from '@/Components/FormField.vue';

const props = defineProps({
    accounts: { type: Array, default: () => [] },
});

const emit = defineEmits(['saved']);

const form = useForm({ name: '', target_amount: '', currency: 'PEN', target_date: '', account_id: '' });

const linkedAccount = computed(() => props.accounts.find((a) => a.id === form.account_id) ?? null);

const submit = () => {
    form.transform((data) => ({
        ...data,
        target_date: data.target_date || null,
        account_id: data.account_id || null,
        // Una meta con cuenta hereda la moneda de la cuenta.
        currency: linkedAccount.value?.currency ?? data.currency,
    })).post('/goals', {
        preserveScroll: true,
        onSuccess: () => emit('saved'),
    });
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <FormField label="Nombre" :error="form.errors.name">
            <BaseInput v-model="form.name" type="text" />
        </FormField>

        <FormField
            label="Cuenta asociada"
            :error="form.errors.account_id"
            hint="Con cuenta, los aportes salen de ella como movimientos reales."
        >
            <BaseSelect v-model="form.account_id">
                <option value="">Solo seguimiento — sin cuenta</option>
                <option v-for="a in accounts" :key="a.id" :value="a.id">
                    {{ a.name }} ({{ a.currentBalance.formatted }})
                </option>
            </BaseSelect>
        </FormField>

        <div class="grid grid-cols-2 gap-3">
            <FormField label="Meta" :error="form.errors.target_amount">
                <BaseInput v-model="form.target_amount" type="number" step="0.01" min="0" inputmode="decimal" />
            </FormField>
            <FormField v-if="!linkedAccount" label="Moneda" :error="form.errors.currency">
                <BaseInput v-model="form.currency" maxlength="3" class="uppercase" />
            </FormField>
            <FormField v-else label="Moneda">
                <BaseInput :model-value="linkedAccount.currency" disabled />
            </FormField>
        </div>

        <FormField label="Fecha objetivo (opcional)" :error="form.errors.target_date">
            <BaseInput v-model="form.target_date" type="date" />
        </FormField>

        <BaseButton type="submit" block :processing="form.processing">Crear meta</BaseButton>
    </form>
</template>
