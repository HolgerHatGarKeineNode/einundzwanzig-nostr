export default {
    presets: [
        require("./vendor/power-components/livewire-powergrid/tailwind.config.js"),
    ],
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',

        './app/Livewire/**/*Table.php',
        './vendor/power-components/livewire-powergrid/resources/views/**/*.php',
        './vendor/power-components/livewire-powergrid/src/Themes/Tailwind.php',

        './vendor/wireui/wireui/src/*.php',
        './vendor/wireui/wireui/ts/**/*.ts',
        './vendor/wireui/wireui/src/WireUi/**/*.php',
        './vendor/wireui/wireui/src/Components/**/*.php',
    ],
    darkMode: 'class',
    safelist: [
        'w-96',
        'group',
        'aspect-h-7',
        'aspect-w-10',
        'pointer-events-none',
        'object-cover',
        'group-hover:opacity-75',
    ],
}
