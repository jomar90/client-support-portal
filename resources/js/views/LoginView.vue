<script setup lang="ts">
import axios from 'axios';
import { ref } from 'vue';
import { useRouter } from 'vue-router';

import { authStore } from '../stores/authStore';

interface ValidationErrorResponse {
    message?: string;
    errors?: Record<string, string[]>;
}

const router = useRouter();

const email = ref('');
const password = ref('');
const errorMessage = ref('');
const submitting = ref(false);

async function submit(): Promise<void> {
    errorMessage.value = '';
    submitting.value = true;

    try {
        const user = await authStore.actions.login({
            email: email.value,
            password: password.value,
        });

        if (!user) {
            errorMessage.value = 'Unable to load the authenticated user.';

            return;
        }

        await router.push({
            name: 'tickets.index',
        });
    } catch (error) {
        if (axios.isAxiosError<ValidationErrorResponse>(error)) {
            errorMessage.value =
                error.response?.data.errors?.email?.[0]
                ?? error.response?.data.message
                ?? 'Unable to log in.';

            return;
        }

        errorMessage.value = 'Unable to log in.';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <main>
        <h1>Login</h1>

        <form @submit.prevent="submit">
            <div>
                <label for="email">Email</label>

                <input
                    id="email"
                    v-model="email"
                    type="email"
                    autocomplete="email"
                    required
                >
            </div>

            <div>
                <label for="password">Password</label>

                <input
                    id="password"
                    v-model="password"
                    type="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <p v-if="errorMessage">
                {{ errorMessage }}
            </p>

            <p>
                <RouterLink :to="{ name: 'password.forgot' }">
                    Forgot your password?
                </RouterLink>
            </p>

            <button
                type="submit"
                :disabled="submitting"
            >
                {{ submitting ? 'Logging in...' : 'Login' }}
            </button>
        </form>
    </main>
</template>
