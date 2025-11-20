export default () => ({

    async init() {

    },

    async openNostrLogin() {
        const pubkey = await window.nostr.getPublicKey();
        // fetch profile from /api/nostr/profile/{publicKey}
        fetch('/api/nostr/profile/' + pubkey)
            .then(response => response.json())
            .then(data => {
                console.log('Profile fetched', data);
                // store the profile in AlpineJS store
                Alpine.store('nostr', {user: data});
                this.$dispatch('nostrLoggedIn', {pubkey: data.pubkey});
            });
    },

});
