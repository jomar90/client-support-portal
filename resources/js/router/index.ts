import {
    createRouter,
    createWebHistory,
} from 'vue-router';

import { authStore } from '../stores/authStore';
import LoginView from '../views/LoginView.vue';
import TicketOverviewView from '../views/TicketOverviewView.vue';
import ForgotPasswordView from '../views/ForgotPasswordView.vue';
import TicketCreateView from '../views/TicketCreateView.vue';
import TicketEditView from '../views/TicketEditView.vue';
import TicketDetailView from '../views/TicketDetailView.vue';
import CategoryOverviewView from '../views/CategoryOverviewView.vue';

import CategoryCreateView from '../views/CategoryCreateView.vue';


const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            redirect: '/tickets',
        },
        {
            path: '/login',
            name: 'login',
            component: LoginView,
        },
        {
            path: '/tickets',
            name: 'tickets.index',
            component: TicketOverviewView,
            meta: {
                requiresAuth: true,
            },
        },
        {
            path: '/forgot-password',
            name: 'password.forgot',
            component: ForgotPasswordView,
        },
        {
            path: '/tickets/create',
            name: 'tickets.create',
            component: TicketCreateView,
            meta: { requiresAuth: true },
        },
        {
            path: '/tickets/:id/edit',
            name: 'tickets.edit',
            component: TicketEditView,
            meta: { requiresAuth: true },
        },
        {
            path: '/tickets/:id',
            name: 'tickets.show',
            component: TicketDetailView,
            meta: { requiresAuth: true },
        },
        {
            path: '/categories',
            name: 'categories.index',
            component: CategoryOverviewView,
            meta: {
                requiresAuth: true,
                requiresAdmin: true,
            },
        },
        {
            path: '/categories/create',
            name: 'categories.create',
            component: CategoryCreateView,
            meta: {
                requiresAuth: true,
                requiresAdmin: true,
            },
        },
    ],
});

router.beforeEach(async (to) => {
    if (!authStore.state.initialized) {
        await authStore.actions.fetchUser();
    }

    if (
        to.meta.requiresAuth
        && !authStore.getters.isAuthenticated.value
    ) {
        return {
            name: 'login',
        };
    }

    if (
        to.name === 'login'
        && authStore.getters.isAuthenticated.value
    ) {
        return {
            name: 'tickets.index',
        };
    }
    if (
        to.meta.requiresAdmin
        && !authStore.state.user?.admin
    ) {
        return {
            name: 'tickets.index',
        };
    }
});



export default router;
