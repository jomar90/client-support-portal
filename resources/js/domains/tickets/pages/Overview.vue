<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { ticketStore } from '../store';

const tickets = ticketStore.getters.all;
const loading = ref(true);

async function loadTickets() {
    ticketStore.setters.setAll([]);
    try {
        await ticketStore.actions.getAll();
    } catch {
        // Displayed by the shared error service.
    } finally {
        loading.value = false;
    }
}

onMounted(loadTickets);
</script>
<template>
    <h1>Tickets</h1>
    <RouterLink :to="{ name: 'tickets.create' }">Create ticket</RouterLink>
    <p>Tickets are shown oldest first.</p>
    <p v-if="loading">Loading tickets…</p>
    <div v-else class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Categories</th>
                    <th>Status</th>
                    <th>Created by</th>
                    <th>Created at</th>
                    <th>Last updated at</th>
                    <th>Assigned to</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="ticket in tickets" :key="ticket.id">
                    <td>{{ ticket.id }}</td>
                    <td>
                        <RouterLink :to="{ name: 'tickets.show', params: { id: ticket.id } }">{{ ticket.title }}</RouterLink>
                    </td>
                    <td>{{ ticket.categories.join(', ') || 'None' }}</td>
                    <td>{{ ticket.status.replace('_', ' ') }}</td>
                    <td>{{ ticket.created_by }}</td>
                    <td>{{ new Date(ticket.created_at).toLocaleString() }}</td>
                    <td>{{ new Date(ticket.updated_at).toLocaleString() }}</td>
                    <td>{{ ticket.assigned_to || 'Unassigned' }}</td>
                </tr>
                <tr v-if="tickets.length === 0">
                    <td colspan="8">No tickets to display.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
