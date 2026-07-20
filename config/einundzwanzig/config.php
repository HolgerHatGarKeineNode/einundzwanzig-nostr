<?php

return [
    'current_board' => [
        'npub1pt0kw36ue3w2g4haxq3wgm6a2fhtptmzsjlc2j2vphtcgle72qesgpjyc6',
        'npub1gvqkjccl9urg93svaw60jqkk3ux8r3ycl5t3rlvc9uzjeu0agfuss8x8qy',
        'npub10t8npnmqhpwx9w8k232kess7gqtdlr6kqjemdzf8jnughwqd0gwsez0924',
        'npub1r8343wqpra05l3jnc4jud4xz7vlnyeslf7gfsty7ahpf92rhfmpsmqwym8',
        'npub17fqtu2mgf7zueq2kdusgzwr2lqwhgfl2scjsez77ddag2qx8vxaq3vnr8y',
        'npub1v4lgwjv7qfn3t7qjscpsgz9vqvspf6hecdp2ckgp0dz89uqn5slsgrhw3p',
        'npub14r770s5wrqpm8jmzur5arnm9aum9x0wasaxwczael54xhjggl7ws5lygc6',
    ],

    /*
     * Relays, von denen Profile (kind 0) geholt werden.
     *
     * Konfigurierbar, weil der Abruf SYNCHRON im Request laeuft: Findet sich
     * kein Profil, wartet der Aufrufer auf die Zeitueberschreitung jedes
     * einzelnen Relays — gemessen 21 Sekunden fuer die vier Standardadressen.
     *
     * In Tests gehoert das auf eine leere Liste (NOSTR_PROFILE_RELAYS=""):
     * Ein Test darf keine echten Verbindungen nach draussen aufbauen, und ein
     * Wegwerf-Schluessel hat dort ohnehin nie ein Profil. Leer heisst: gar
     * nicht erst verbinden.
     */
    'profile_relays' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('NOSTR_PROFILE_RELAYS', implode(',', [
            'wss://purplepag.es',
            'wss://nostr.wine',
            'wss://relay.damus.io',
            'wss://relay.primal.net',
        ])))
    ))),
];
