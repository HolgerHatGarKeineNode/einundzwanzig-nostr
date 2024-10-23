import {Alpine, Livewire} from '../../vendor/livewire/livewire/dist/livewire.esm';

import nostrDefault from "./nostrDefault.js";
import nostrApp from "./nostrApp.js";
import nostrLogin from "./nostrLogin.js";
import nostrZap from "./nostrZap.js";
import electionAdminCharts from "./electionAdminCharts.js";

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

Alpine.data('nostrDefault', nostrDefault);
Alpine.data('nostrApp', nostrApp);
Alpine.data('nostrLogin', nostrLogin);
Alpine.data('nostrZap', nostrZap);
Alpine.data('electionAdminCharts', electionAdminCharts);

Livewire.start();
