import Overview from './pages/Overview.vue';
import Edit from './pages/Edit.vue';

export const userRoutes = [
    { path: '/users', name: 'users.overview', component: Overview, meta: { admin: true } },
    { path: '/users/:id/edit', name: 'users.edit', component: Edit, meta: { admin: true } },
];
