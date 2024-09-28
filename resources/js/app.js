import {Alpine, Livewire} from '../../vendor/livewire/livewire/dist/livewire.esm';

import nostrApp from "./nostrApp.js";
import nostrLogin from "./nostrLogin.js";

import './bootstrap';

// Light switcher
document.querySelectorAll('.light-switch').forEach(lightSwitch => lightSwitch.checked = true);
document.documentElement.classList.add('dark');
document.querySelector('html').style.colorScheme = 'dark';
localStorage.setItem('dark-mode', true);
document.dispatchEvent(new CustomEvent('darkMode', { detail: { mode: 'on' } }));

Alpine.store('nostr', {
    user: null,
});

Alpine.data('nostrApp', nostrApp);
Alpine.data('nostrLogin', nostrLogin);

Livewire.start();
