<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { authStore } from '../store';
import FormError from '../../../components/FormError.vue';

const router = useRouter();
const route = useRoute();
const email = ref('');
const password = ref('');
const busy = ref(false);

async function login() {
    busy.value = true;
    try {
        await authStore.actions.login(email.value, password.value);
        await router.push({ name: 'tickets.overview' });
    } catch {
        // The shared error service displays the failure.
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <h1>Log in</h1>
    <p v-if="route.query.reset === 'success'" role="status">Your password has been reset. You can now log in.</p>
    <form @submit.prevent="login">
        <label for="email">Email address</label>
        <input id="email" v-model="email" type="email" autocomplete="username" required>
        <FormError name="email" />
        <label for="password">Password</label>
        <input id="password" v-model="password" type="password" autocomplete="current-password" required>
        <FormError name="password" />
        <button :disabled="busy">{{ busy ? 'Logging in…' : 'Log in' }}</button>
    </form>
    <RouterLink :to="{ name: 'auth.forgot' }">Forgot your password?</RouterLink>
    <p><RouterLink :to="{ name: 'auth.register' }">Create an account</RouterLink></p>
</template>
