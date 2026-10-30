<script setup lang="ts">
import { ref } from 'vue';
import { authStore } from '../store';
import FormError from '../../../components/FormError.vue';

const email = ref('');
const message = ref('');
const busy = ref(false);

async function sendResetLink() {
    busy.value = true;
    message.value = '';
    try {
        message.value = await authStore.actions.forgotPassword(email.value);
    } catch {
        // Displayed by the shared error service.
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <h1>Forgot password</h1>
    <p>Enter your email address to request a password reset link.</p>
    <p v-if="message" role="status">{{ message }}</p>
    <form @submit.prevent="sendResetLink">
        <label for="email">Email address</label>
        <input id="email" v-model="email" type="email" autocomplete="email" required>
        <FormError name="email" />
        <button :disabled="busy">Send reset link</button>
    </form>
    <RouterLink :to="{ name: 'auth.login' }">Back to login</RouterLink>
</template>
