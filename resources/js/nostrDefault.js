export default (livewireComponent) => ({

    isAllowed: livewireComponent.entangle('isAllowed', true),

});
