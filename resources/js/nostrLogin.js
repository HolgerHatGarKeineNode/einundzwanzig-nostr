export default () => ({

    openNostrLogin() {
        window.nostr.getPublicKey();
    },

    init() {
        // listen for nostr auth events
        document.addEventListener('nlAuth', (e) => {
            // type is login, signup or logout
            if (e.detail.type === 'login' || e.detail.type === 'signup') {
                console.log('User logged in');
                // fetch profile from /api/nostr/profile/{publicKey}
                fetch('/api/nostr/profile/' + e.detail.pubkey)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Profile fetched', data);
                        // store the profile in AlpineJS store
                        Alpine.store('nostr', { user: data });
                        this.$dispatch('nostrLoggedIn', {pubkey: data.pubkey});
                    });
            } else {
                console.log('User logged out')
                Alpine.store('nostr', { user: null });
            }
        })
    },

});
