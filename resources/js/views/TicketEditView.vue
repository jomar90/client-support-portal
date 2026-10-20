
<script setup lang="ts">
import axios from 'axios';
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ticketStore } from '../stores/ticketStore';

const route = useRoute();
const router = useRouter();

const ticketId = Number(route.params.id);

const title = ref('');
const description = ref('');
const loading = ref(true);
const submitting = ref(false);
const errorMessage = ref('');
const loadError = ref('');

onMounted(async () => {
    try {
        if (!Number.isSafeInteger(ticketId) || ticketId < 1) {
            throw new Error('Invalid ticket ID');
        }

        const ticket = await ticketStore.actions.getTicket(ticketId);

        title.value = ticket.title;
        description.value = ticket.description;
    } catch {
        loadError.value = 'Ticket not found or access denied.';
    } finally {
        loading.value = false;
    }
});

async function submit(): Promise<void> {
    errorMessage.value = '';
    submitting.value = true;

    try {
        await ticketStore.actions.updateTicket(ticketId, {
            title: title.value,
            description: description.value,
        });

        await router.push({ name: 'tickets.index' });
    } catch (error) {
        if (axios.isAxiosError(error)) {
            const data = error.response?.data as {
                message?: string;
                errors?: Record<string, string[]>;
            } | undefined;

            errorMessage.value =
                data?.errors?.title?.[0]
                ?? data?.errors?.description?.[0]
                ?? data?.message
                ?? 'Unable to update ticket.';
        } else {
            errorMessage.value = 'Unable to update ticket.';
        }
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <main>
        <h1>Edit Ticket</h1>

        <p v-if="loading">Loading ticket...</p>

        <p v-else-if="loadError" role="alert">
            {{ loadError }}
        </p>

        <form v-else @submit.prevent="submit">
            <div>
                <label for="title">Title</label>
                <input
                    id="title"
                    v-model="title"
                    type="text"
                    maxlength="255"
                    required
                >
            </div>

            <div>
                <label for="description">Description</label>
                <textarea
                    id="description"
                    v-model="description"
                    rows="6"
                    required
                ></textarea>
            </div>

            <p v-if="errorMessage" role="alert">
                {{ errorMessage }}
            </p>

            <button type="submit" :disabled="submitting">
                {{ submitting ? 'Saving...' : 'Save Changes' }}
            </button>
        </form>

        <p>
            <RouterLink :to="{ name: 'tickets.index' }">
                Back to Tickets
            </RouterLink>
        </p>
    </main>
</template>
