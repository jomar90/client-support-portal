<script setup lang="ts">
import { ref } from 'vue';
import { authStore } from '../store';
import type { RegistrationData } from '../types';
import FormError from '../../../components/FormError.vue';

const form = ref<RegistrationData>({
    first_name: '', last_name: '', email: '', phone_number: '',
    password: '', password_confirmation: '',
});
const busy = ref(false);
const message = ref('');

async function register() {
    busy.value = true;
    try {
        message.value = await authStore.actions.register(form.value);
        form.value.password = '';
        form.value.password_confirmation = '';
    } catch {
        // Displayed by the shared error service.
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <h1>Create an account</h1>
    <p v-if="message" role="status">{{ message }}</p>
    <form v-else @submit.prevent="register">
        <label for="first-name">First name</label>
        <input id="first-name" v-model="form.first_name" autocomplete="given-name" maxlength="255" required>
        <FormError name="first_name" />
        <label for="last-name">Last name</label>
        <input id="last-name" v-model="form.last_name" autocomplete="family-name" maxlength="255" required>
        <FormError name="last_name" />
        <label for="email">Email address</label>
        <input id="email" v-model="form.email" type="email" autocomplete="email" maxlength="255" required>
        <FormError name="email" />
        <label for="phone-number">Phone number (optional)</label>
        <input id="phone-number" v-model="form.phone_number" type="tel" autocomplete="tel" maxlength="255">
        <FormError name="phone_number" />
        <label for="password">Password (8–72 characters)</label>
        <input id="password" v-model="form.password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
        <FormError name="password" />
        <label for="password-confirmation">Confirm password</label>
        <input id="password-confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
        <button :disabled="busy">{{ busy ? 'Creating account…' : 'Register' }}</button>
    </form>
    <RouterLink :to="{ name: 'auth.login' }">Back to login</RouterLink>
</template>
