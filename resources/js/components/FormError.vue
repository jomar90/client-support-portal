<script setup lang="ts">
import { getErrorByProperty } from '../services/error';

const props = defineProps<{
    name: string;
}>();
const errors = getErrorByProperty(props.name);
</script>
<template>
    <p v-for="error in errors" :key="error" class="error">{{ error }}</p>
</template>
