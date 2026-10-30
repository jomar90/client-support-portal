import { createRouter, createWebHistory } from 'vue-router';
import { authRoutes } from '../domains/auth/routes';
import { ticketRoutes } from '../domains/tickets/routes';
import { categoryRoutes } from '../domains/categories/routes';
import { userRoutes } from '../domains/users/routes';
import { authStore } from '../domains/auth/store';
import { clearErrors } from '../services/error';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        ...authRoutes,
        ...ticketRoutes,
        ...categoryRoutes,
        ...userRoutes,
        { path: '/:pathMatch(.*)*', redirect: '/tickets' },
    ],
});

router.beforeEach(async to => {
    clearErrors();

    try {
        await authStore.actions.getUser();
    } catch {
        // Keep the current page and display the HTTP service's error message.
        return false;
    }

    const user = authStore.getters.user.value;

    if (!to.meta.guest && !user) {
        return { name: 'auth.login' };
    }

    if (to.meta.guest && user) {
        return { name: 'tickets.overview' };
    }

    if (to.meta.admin && !user?.admin) {
        return { name: 'tickets.overview' };
    }
});

export default router;
