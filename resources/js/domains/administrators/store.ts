import { storeModuleFactory } from '../../services/store';
import type { Administrator } from './types';

// Administrators are users with admin=true. This store is only an assignment lookup.
export const administratorStore = storeModuleFactory<Administrator>('administrators');
