import NDK, {NDKEvent} from "@nostr-dev-kit/ndk";
import {
    nip57,
} from "nostr-tools";

export default (livewireComponent) => ({

    invoice: livewireComponent.entangle('invoice', true),

    async zap(message, sender, amountToPay, env) {
        const config = {
            production: {
                relayUrl: 'wss://simple-relay.steuernsindraub21.xyz',
            },
            staging: {
                relayUrl: 'wss://simple-test-relay.steuernsindraub21.xyz',
            },
            local: {
                relayUrl: 'ws://simple-test-relay.steuernsindraub21.xyz',
            },
        };
        const relayUrl = config[env]?.relayUrl || config['local'].relayUrl;

        const ndk = new NDK({
            explicitRelayUrls: [
                relayUrl
            ],
            enableOutboxModel: false,
        });
        // Now connect to specified relays
        await ndk.connect();
        const event = await ndk.fetchEvent({
            kinds: [32121],
            authors: ['daf83d92768b5d0005373f83e30d4203c0b747c170449e02fea611a0da125ee6']
        });
        const amount = amountToPay * 1000;
        console.log('event', event);

        // Fetch the callback URL
        const callbackResponse = await fetch('https://pay.einundzwanzig.space/BTC/UILNURL/pay/lnaddress/verein');
        const callbackData = await callbackResponse.json();
        const zapEndpoint = callbackData.callback;

        const zapEvent = nip57.makeZapRequest({
            profile: sender,
            event: event.id,
            amount: amount,
            relays: [relayUrl],
            comment: message,
        });
        console.log('zapEvent', zapEvent);

        const signedEvent = await window.nostr.signEvent(zapEvent);
        console.log('signedEvent', signedEvent);

        let url = `${zapEndpoint}?amount=${amount}&nostr=${encodeURIComponent(
            JSON.stringify(signedEvent)
        )}`;
        url = `${url}&comment=${encodeURIComponent(message)}`;
        console.log('url', url);

        const res = await fetch(url);
        const { pr: invoice, reason, status } = await res.json();

        if (invoice) {
            console.log('invoice', invoice);
            this.invoice = invoice;
        } else if (status === "ERROR") {
            throw new Error(reason ?? "Unable to fetch invoice");
        } else {
            throw new Error("Other error");
        }

    },

});
