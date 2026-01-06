export default () => ({

    async init() {

    },

    async openNostrLogin() {
        console.log('Starting Nostr login...');
        console.log('window.nostr available:', !!window.nostr);

        const pubkey = await window.nostr.getPublicKey();
        console.log('Fetched pubkey:', pubkey);

        // fetch profile from /api/nostr/profile/{publicKey}
        const url = '/api/nostr/profile/' + pubkey;
        console.log('Fetching profile from:', url);

        fetch(url)
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                console.log('Response headers:', response.headers);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Profile fetched', data);
                    // store the profile in AlpineJS store
                    Alpine.store('nostr', {user: data});
                    this.$dispatch('nostrLoggedIn', {pubkey: pubkey});
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw e;
                }
            })
            .catch(error => {
                console.error('Error during Nostr login:', error);
            });
    },

});
