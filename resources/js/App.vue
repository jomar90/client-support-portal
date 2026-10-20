<script setup lang="ts">
import { authStore } from './stores/authStore';
</script>

<template>
    <nav
        v-if="authStore.state.user?.admin"
        aria-label="Administrator navigation"
    >
        <RouterLink :to="{ name: 'categories.index' }">
            Categories
        </RouterLink>
    </nav>

    <RouterView />
</template>

