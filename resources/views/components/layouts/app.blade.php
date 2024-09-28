<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? 'Page Title' }}</title>
    @livewireStyles
    @vite(['resources/js/app.js','resources/css/app.css'])
    @googlefonts
    <script src="https://kit.fontawesome.com/866fd3d0ab.js" crossorigin="anonymous"></script>
    <script src='https://www.unpkg.com/nostr-login@latest/dist/unpkg.js' data-perms="sign_event:1,sign_event:0"
            data-theme="default" data-dark-mode="true"></script>
    @wireUiScripts
    @stack('scripts')
</head>
<body
    class="font-sans antialiased bg-gray-100 dark:bg-[#222222] text-gray-600 dark:text-gray-400"
    :class="{ 'sidebar-expanded': sidebarExpanded }"
    x-data="{ sidebarOpen: false, sidebarExpanded: localStorage.getItem('sidebar-expanded') == 'true', inboxSidebarOpen: false }"
    x-init="$watch('sidebarExpanded', value => localStorage.setItem('sidebar-expanded', value))"
>
<script>
    if (localStorage.getItem('sidebar-expanded') == 'true') {
        document.querySelector('body').classList.add('sidebar-expanded');
    } else {
        document.querySelector('body').classList.remove('sidebar-expanded');
    }
</script>
<div x-data="nostrLogin"
    class="flex h-[100dvh] overflow-hidden">
    <livewire:layout.sidebar/>
    <div
        class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
        <!-- Site header -->
        <header
            class="sticky top-0 before:absolute before:inset-0 before:backdrop-blur-md before:bg-white/90 dark:before:bg-[#222222]/90 lg:before:bg-[#222222]/90 dark:lg:before:bg-[#222222]/90 before:-z-10 max-lg:shadow-sm z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16 lg:border-b border-gray-200 dark:border-gray-700/60">

                    <!-- Header: Left side -->
                    <div class="flex">
                        <!-- Hamburger button -->
                        <button
                            class="text-gray-500 hover:text-gray-600 dark:hover:text-gray-400 lg:hidden"
                            @click.stop="sidebarOpen = !sidebarOpen"
                            aria-controls="sidebar"
                            :aria-expanded="sidebarOpen"
                        >
                            <span class="sr-only">Open sidebar</span>
                            <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <rect x="4" y="5" width="16" height="2"/>
                                <rect x="4" y="11" width="16" height="2"/>
                                <rect x="4" y="17" width="16" height="2"/>
                            </svg>
                        </button>

                    </div>

                    <!-- Header: Right side -->
                    <div class="flex items-center space-x-3">

                        {{--@include('components.layouts.partials.search-button')--}}

                        {{--@include('components.layouts.partials.notification-buttons')--}}

                        <!-- Info button -->
                        <div class="relative inline-flex" x-data="{ open: false }">
                            <button
                                class="w-8 h-8 flex items-center justify-center hover:bg-[#1B1B1B] lg:hover:bg-[#1B1B1B] dark:hover:bg-[#1B1B1B]/50 dark:lg:hover:bg-[#1B1B1B] rounded-full"
                                :class="{ 'bg-gray-200 dark:bg-[#1B1B1B]': open }"
                                aria-haspopup="true"
                                @click.prevent="open = !open"
                                :aria-expanded="open"
                            >
                                <span class="sr-only">Info</span>
                                <svg class="fill-current text-gray-500/80 dark:text-gray-400/80" width="16" height="16"
                                     viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M9 7.5a1 1 0 1 0-2 0v4a1 1 0 1 0 2 0v-4ZM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z"/>
                                    <path fill-rule="evenodd"
                                          d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16Zm6-8A6 6 0 1 1 2 8a6 6 0 0 1 12 0Z"/>
                                </svg>
                            </button>
                            <div
                                class="origin-top-right z-10 absolute top-full right-0 min-w-44 bg-white dark:bg-[#1B1B1B] border border-gray-200 dark:border-[#1B1B1B]/60 py-1.5 rounded-lg shadow-lg overflow-hidden mt-1"
                                @click.outside="open = false"
                                @keydown.escape.window="open = false"
                                x-show="open"
                                x-transition:enter="transition ease-out duration-200 transform"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-out duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                x-cloak
                            >
                                <div
                                    class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase pt-1.5 pb-2 px-3">
                                    Information
                                </div>
                                <ul>
                                    {{--<li>
                                        <a class="font-medium text-sm text-amber-500 hover:text-amber-600 dark:hover:text-amber-400 flex items-center py-1 px-3"
                                           href="#0" @click="open = false" @focus="open = true"
                                           @focusout="open = false">
                                            <svg class="w-3 h-3 fill-current text-amber-500 shrink-0 mr-2"
                                                 viewBox="0 0 12 12">
                                                <rect y="3" width="12" height="9" rx="1"/>
                                                <path d="M2 0h8v2H2z"/>
                                            </svg>
                                            <span>Documentation</span>
                                        </a>
                                    </li>--}}
                                </ul>
                            </div>
                        </div>

                        {{--@include('components.layouts.partials.dark-mode-toggle')--}}

                        <!-- Divider -->
                        {{--<hr class="w-px h-6 bg-gray-200 dark:bg-gray-700/60 border-none"/>--}}

                        {{--@include('components.layouts.partials.user-button')--}}

                    </div>

                </div>
            </div>
        </header>
        <main class="grow">
            {{ $slot }}
        </main>
    </div>
</div>
@livewireScriptConfig
</body>
</html>
