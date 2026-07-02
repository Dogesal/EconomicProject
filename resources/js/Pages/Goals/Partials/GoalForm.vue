<script setup>
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@/Components/BaseButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import FormField from '@/Components/FormField.vue';

const emit = defineEmits(['saved']);

const form = useForm({ name: '', target_amount: '', currency: 'PEN', target_date: '' });

const submit = () => {
    form.transform((data) => ({ ...data, target_date: data.target_date || null })).post('/goals', {
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

        <div class="grid grid-cols-2 gap-3">
            <FormField label="Meta" :error="form.errors.target_amount">
                <BaseInput v-model="form.target_amount" type="number" step="0.01" min="0" inputmode="decimal" />
            </FormField>
            <FormField label="Moneda" :error="form.errors.currency">
                <BaseInput v-model="form.currency" maxlength="3" class="uppercase" />
            </FormField>
        </div>

        <FormField label="Fecha objetivo (opcional)" :error="form.errors.target_date">
            <BaseInput v-model="form.target_date" type="date" />
        </FormField>

        <BaseButton type="submit" block :processing="form.processing">Crear meta</BaseButton>
    </form>
</template>
