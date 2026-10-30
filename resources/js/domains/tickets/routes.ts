import Overview from './pages/Overview.vue';
import Create from './pages/Create.vue';
import Edit from './pages/Edit.vue';
import Show from './pages/Show.vue';

export const ticketRoutes = [
    {
        path: '/tickets',
        name: 'tickets.overview',
        component: Overview,
    },
    {
        path: '/tickets/create',
        name: 'tickets.create',
        component: Create,
    },
    {
        path: '/tickets/:id/edit',
        name: 'tickets.edit',
        component: Edit,
    },
    {
        path: '/tickets/:id',
        name: 'tickets.show',
        component: Show,
    },
];
