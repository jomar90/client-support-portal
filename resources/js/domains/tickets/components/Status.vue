<script setup lang="ts">
import { ref } from 'vue';
import { ticketStore } from '../store';
import type { TicketStatus } from '../types';
import FormError from '../../../components/FormError.vue';

const props = defineProps<{
    ticketId: number;
    status: TicketStatus;
}>();
const emit = defineEmits<{
    updated: [];
}>();
const selected = ref<TicketStatus>(props.status);
const busy = ref(false);

async function saveStatus() {
    busy.value = true;
    try {
        await ticketStore.actions.update(props.ticketId, { status: selected.value });
        emit('updated');
    } catch {
        // Displayed by the shared error service.
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <form @submit.prevent="saveStatus">
        <label for="status">Status</label>
        <select id="status" v-model="selected">
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="resolved">Resolved</option>
        </select>
        <FormError name="status" />
        <button :disabled="busy">Save status</button>
    </form>
</template>
