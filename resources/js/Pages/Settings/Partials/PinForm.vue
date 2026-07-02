<script setup>
import { useForm } from '@inertiajs/vue3';
import BaseButton from '@/Components/BaseButton.vue';
import BaseInput from '@/Components/BaseInput.vue';
import FormField from '@/Components/FormField.vue';

const props = defineProps({
    /** Ask for the current PIN before allowing a change (lock armed + PIN set). */
    requireCurrent: { type: Boolean, default: false },
});

const emit = defineEmits(['saved']);

const form = useForm({
    current_pin: '',
    pin: '',
    pin_confirmation: '',
});

const submit = () => {
    form.put('/settings/pin', {
        preserveScroll: true,
        onSuccess: () => emit('saved'),
    });
};
</script>

<template>
    <form class="space-y-4" @submit.prevent="submit">
        <FormField v-if="requireCurrent" label="PIN actual" :error="form.errors.current_pin">
            <BaseInput v-model="form.current_pin" type="password" inputmode="numeric" maxlength="6" autocomplete="off" />
        </FormField>

        <FormField label="Nuevo PIN (4 a 6 dígitos)" :error="form.errors.pin">
            <BaseInput v-model="form.pin" type="password" inputmode="numeric" maxlength="6" autocomplete="off" />
        </FormField>

        <FormField label="Repetí el PIN" :error="form.errors.pin_confirmation">
            <BaseInput v-model="form.pin_confirmation" type="password" inputmode="numeric" maxlength="6" autocomplete="off" />
        </FormField>

        <BaseButton type="submit" block :processing="form.processing">Guardar PIN</BaseButton>
    </form>
</template>
