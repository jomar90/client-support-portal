<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Form from '../components/Form.vue';
import { ticketStore } from '../store';
import type { TicketDetail, TicketFormData } from '../types';

const route = useRoute();
const router = useRouter();
const id = Number(route.params.id);
const ticket = ref<TicketDetail | null>(null);
const loading = ref(true);
const busy = ref(false);

async function loadTicket() {
    try {
        ticket.value = await ticketStore.actions.getById(id);
    } catch {
        // Displayed by the shared error service.
    } finally {
        loading.value = false;
    }
}

onMounted(loadTicket);

async function updateTicket(form: TicketFormData) {
    busy.value = true;
    try {
        await ticketStore.actions.update(id, form);
        await router.push({ name: 'tickets.show', params: { id } });
    } catch {
        // Displayed by the shared error service.
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <h1>Edit ticket</h1>
    <p v-if="loading">Loading ticket…</p>
    <Form v-if="ticket" :ticket="ticket" :busy="busy" @submit="updateTicket" />
</template>
