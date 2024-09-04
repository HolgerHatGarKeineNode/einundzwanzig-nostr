import './bootstrap';

// Light switcher
document.querySelectorAll('.light-switch').forEach(lightSwitch => lightSwitch.checked = true);
document.documentElement.classList.add('dark');
document.querySelector('html').style.colorScheme = 'dark';
localStorage.setItem('dark-mode', true);
document.dispatchEvent(new CustomEvent('darkMode', { detail: { mode: 'on' } }));
