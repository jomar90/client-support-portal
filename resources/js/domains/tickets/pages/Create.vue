<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import Form from '../components/Form.vue';
import { ticketStore } from '../store';
import type { TicketFormData } from '../types';

const router = useRouter();
const ticket: TicketFormData = {
    title: '',
    description: '',
    category_ids: [],
};
const busy = ref(false);

async function createTicket(form: TicketFormData) {
    busy.value = true;
    try {
        const id = await ticketStore.actions.create(form);
        await router.push({ name: 'tickets.show', params: { id } });
    } catch {
        // Displayed by the shared error service.
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <h1>Create ticket</h1>
    <Form :ticket="ticket" :busy="busy" @submit="createTicket" />
</template>
