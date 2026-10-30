<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { ticketStore } from '../store';
import { authStore } from '../../auth/store';
import type { TicketDetail } from '../types';
import Assignment from '../components/Assignment.vue';
import Status from '../components/Status.vue';
import Responses from '../components/Responses.vue';
import Notes from '../components/Notes.vue';

const route = useRoute();
const id = Number(route.params.id);
const ticket = ref<TicketDetail | null>(null);
const loading = ref(true);
const isAdmin = authStore.getters.isAdmin;

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
</script>
<template>
    <p v-if="loading">Loading ticket…</p>
    <div v-if="ticket">
        <h1>Ticket #{{ ticket.id }}: {{ ticket.title }}</h1>
        <p class="multiline">{{ ticket.description }}</p>
        <dl>
            <dt>Categories</dt>
            <dd>{{ ticket.categories.join(', ') || 'None' }}</dd>
            <dt>Status</dt>
            <dd>{{ ticket.status.replace('_', ' ') }}</dd>
            <dt>Created by</dt>
            <dd>{{ ticket.created_by }}</dd>
            <dt>Created at</dt>
            <dd>{{ new Date(ticket.created_at).toLocaleString() }}</dd>
            <dt>Last updated at</dt>
            <dd>{{ new Date(ticket.updated_at).toLocaleString() }}</dd>
            <dt>Assigned to</dt>
            <dd>{{ ticket.assigned_to || 'Unassigned' }}</dd>
        </dl>
        <RouterLink :to="{ name: 'tickets.edit', params: { id } }">Edit ticket</RouterLink>
        <section v-if="isAdmin">
            <h2>Manage ticket</h2>
            <Assignment :ticket-id="id" :assigned-to="ticket.assigned_to_id" @updated="loadTicket" />
            <Status :ticket-id="id" :status="ticket.status" @updated="loadTicket" />
        </section>
        <Responses :ticket-id="id" :responses="ticket.responses" :is-admin="isAdmin" @updated="loadTicket" />
        <Notes v-if="isAdmin && ticket.notes" :ticket-id="id" :notes="ticket.notes" @updated="loadTicket" />
    </div>
    <RouterLink :to="{ name: 'tickets.overview' }">Back to tickets</RouterLink>
</template>
