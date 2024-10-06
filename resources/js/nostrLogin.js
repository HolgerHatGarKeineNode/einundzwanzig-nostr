import {init as initNostrLogin} from 'nostr-login';

export default () => ({

    openNostrLogin() {
        window.nostr.getPublicKey();
    },

    async init() {
        await initNostrLogin({
            methods: ['connect', 'extension'],
            onAuth: async (npub, options) => {
                console.log('User logged in', npub, options);
                console.log('type', options.method);
                if (options.method === 'readOnly') {
                    console.log('User logged in as read-only');
                    return;
                }
                if (options.method === undefined) {
                    Alpine.store('nostr', {user: null});
                    this.$dispatch('nostrLoggedOut', {});
                    return;
                }
                const pubkey = await window.nostr.getPublicKey();
                // fetch profile from /api/nostr/profile/{publicKey}
                fetch('/api/nostr/profile/' + pubkey)
                    .then(response => response.json())
                    .then(data => {
                        console.log('Profile fetched', data);
                        // store the profile in AlpineJS store
                        Alpine.store('nostr', {user: data});
                        this.$dispatch('nostrLoggedIn', {pubkey: data.pubkey});
                        document.addEventListener('nlAuth', (e) => {
                            // type is login, signup or logout
                            if (e.detail.type === 'login' || e.detail.type === 'signup') {

                            } else {
                                console.log('User logged out')
                                Alpine.store('nostr', {user: null});
                                this.$dispatch('nostrLoggedOut', {});
                            }
                        })
                    });
            }
        });
    },

});
