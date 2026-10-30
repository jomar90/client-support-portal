<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { authStore } from '../store';
import type { ResetPasswordData } from '../types';
import FormError from '../../../components/FormError.vue';

const route = useRoute();
const router = useRouter();
const form = ref<ResetPasswordData>({
    email: typeof route.query.email === 'string' ? route.query.email : '',
    token: String(route.params.token),
    password: '',
    password_confirmation: '',
});
const busy = ref(false);

async function resetPassword() {
    busy.value = true;
    try {
        await authStore.actions.resetPassword(form.value);
        await router.push({ name: 'auth.login', query: { reset: 'success' } });
    } catch {
        // Displayed by the shared error service.
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <h1>Reset password</h1>
    <form @submit.prevent="resetPassword">
        <label for="email">Email address</label>
        <input id="email" v-model="form.email" type="email" autocomplete="email" required>
        <FormError name="email" />
        <FormError name="token" />
        <label for="password">New password (at least 8 characters)</label>
        <input id="password" v-model="form.password" type="password" autocomplete="new-password" minlength="8" required>
        <FormError name="password" />
        <label for="confirmation">Confirm new password</label>
        <input id="confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
        <FormError name="password_confirmation" />
        <button :disabled="busy">Reset password</button>
    </form>
    <RouterLink :to="{ name: 'auth.forgot' }">Request another reset link</RouterLink>
</template>
