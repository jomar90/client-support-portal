import Overview from './pages/Overview.vue';
import Create from './pages/Create.vue';

export const categoryRoutes = [
    {
        path: '/categories',
        name: 'categories.overview',
        component: Overview,
        meta: { admin: true },
    },
    {
        path: '/categories/create',
        name: 'categories.create',
        component: Create,
        meta: { admin: true },
    },
];
