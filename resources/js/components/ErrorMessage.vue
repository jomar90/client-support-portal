<script setup lang="ts">
import { getMessage } from '../services/error';
</script>
<template>
    <p v-if="getMessage" class="error" role="alert">{{ getMessage }}</p>
</template>
