<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { ticketStore } from '../store';
import { administratorStore } from '../../administrators/store';
import FormError from '../../../components/FormError.vue';

const props = defineProps<{
    ticketId: number;
    assignedTo: number | null;
}>();
const emit = defineEmits<{
    updated: [];
}>();
const administrators = administratorStore.getters.all;
const selected = ref<number | null>(props.assignedTo);
const busy = ref(false);

async function loadAdministrators() {
    try {
        await administratorStore.actions.getAll();
    } catch {
        // Displayed by the shared error service.
    }
}

onMounted(loadAdministrators);

async function saveAssignment() {
    if (selected.value === null) {
        return;
    }
    busy.value = true;
    try {
        await ticketStore.actions.update(props.ticketId, { assigned_to: selected.value });
        emit('updated');
    } catch {
        // Displayed by the shared error service.
    } finally {
        busy.value = false;
    }
}
</script>
<template>
    <form @submit.prevent="saveAssignment">
        <label for="administrator">Assign to administrator</label>
        <select id="administrator" v-model="selected" required>
            <option :value="null" disabled>Select administrator</option>
            <option v-for="admin in administrators" :key="admin.id" :value="admin.id">{{ admin.first_name }} {{ admin.last_name }}</option>
        </select>
        <FormError name="assigned_to" />
        <button :disabled="busy || selected === null">Save assignment</button>
    </form>
</template>
