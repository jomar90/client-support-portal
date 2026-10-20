import { reactive } from 'vue';

export function storeModuleFactory<
    TState extends object,
    TActions,
>(
    initialState: TState,
    createActions: (state: TState) => TActions,
) {
    const state = reactive(initialState) as TState;
    const actions = createActions(state);

    return {
        state,
        actions,
    };
}
