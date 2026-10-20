
<script setup lang="ts">
import axios from 'axios';
import { ref } from 'vue';

import { authStore } from '../stores/authStore';

interface ValidationErrorResponse {
    message?: string;
    errors?: Record<string, string[]>;
}

const email = ref('');
const errorMessage = ref('');
const successMessage = ref('');
const submitting = ref(false);

async function submit(): Promise<void> {
    errorMessage.value = '';
    successMessage.value = '';
    submitting.value = true;

    try {
        successMessage.value =
            await authStore.actions.forgotPassword(email.value);
    } catch (error) {
        if (axios.isAxiosError<ValidationErrorResponse>(error)) {
            errorMessage.value =
                error.response?.data.errors?.email?.[0]
                ?? error.response?.data.message
                ?? 'Unable to request a password reset.';
        } else {
            errorMessage.value = 'Unable to request a password reset.';
        }
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <main>
        <h1>Forgot password</h1>

        <p>Enter your email address to request a password reset link.</p>

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

            <p v-if="errorMessage" role="alert">
                {{ errorMessage }}
            </p>

            <p v-if="successMessage" role="status">
                {{ successMessage }}
            </p>

            <button type="submit" :disabled="submitting">
                {{ submitting ? 'Sending...' : 'Send reset link' }}
            </button>
        </form>

        <p>
            <RouterLink :to="{ name: 'login' }">
                Back to login
            </RouterLink>
        </p>
    </main>
</template>
