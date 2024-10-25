export default (livewireComponent) => ({

    signThisEvent: livewireComponent.entangle('signThisEvent'),
    showLog: livewireComponent.entangle('showLog', true),

    init() {
        // on change of signThisEvent, call the method
        this.$watch('signThisEvent', async () => {
            const toBeSigned = JSON.parse(this.signThisEvent);
            console.log(toBeSigned);
            const signedEvent = await window.nostr.signEvent(toBeSigned);
            this.$wire.call('signEvent', signedEvent);
        });
    },

});
