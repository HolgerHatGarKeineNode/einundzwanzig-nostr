import {Alpine, Livewire} from '../../vendor/livewire/livewire/dist/livewire.esm';

import nostrDefault from "./nostrDefault.js";
import nostrApp from "./nostrApp.js";
import nostrLogin from "./nostrLogin.js";
import nostrZap from "./nostrZap.js";
import electionAdminCharts from "./electionAdminCharts.js";
import projectChatRoom from "./projectChatRoom.js";

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
// Registrierung hier, nicht in einem seitenspezifischen Entry: Alpine startet
// unten mit Livewire.start(); wer eine Komponente bereitstellt, muss vorher
// registriert sein. Das Chat-SDK laedt projectChatRoom selbst per dynamischem
// Import erst beim Klick — hier faellt nichts Schweres an.
Alpine.data('projectChatRoom', projectChatRoom);

Livewire.start();
