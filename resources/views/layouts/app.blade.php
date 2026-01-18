@php
    $currentRoute = request()->route()->getName();
    $isCurrentRouteClass = 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100';
    $isNotCurrentRouteClass = 'text-gray-600 dark:text-gray-400';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @hasSection('meta')
        @yield('meta')
    @else
        {!! seo($SEOData ?? null) !!}
    @endif
    <title>{{ $title ?? 'Page Title' }}</title>
    <script src="https://kit.fontawesome.com/866fd3d0ab.js" crossorigin="anonymous"></script>
    @googlefonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased"
    x-data="nostrLogin"
>
    <flux:header class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:brand href="/" name="Einundzwanzig" class="max-lg:hidden dark:hidden">
            <img src="{{ asset('einundzwanzig-alpha.jpg') }}" alt="Logo" class="h-6 w-6">
        </flux:brand>
        <flux:brand href="/" name="Einundzwanzig" class="max-lg:hidden! hidden dark:flex">
            <img src="{{ asset('einundzwanzig-alpha.jpg') }}" alt="Logo" class="h-6 w-6">
        </flux:brand>

        <flux:navbar class="-mb-px max-lg:hidden">
            @if(\App\Support\NostrAuth::check())
                <flux:navbar.item icon="rss" :href="route('association.news')" :current="request()->routeIs('association.news')">News</flux:navbar.item>
                <flux:navbar.item icon="identification" :href="route('association.profile')" :current="request()->routeIs('association.profile')">Profil</flux:navbar.item>
                <flux:navbar.item icon="heart" :href="route('association.projectSupport')" :current="request()->routeIs('association.projectSupport')">Projekt-Unterstützungen</flux:navbar.item>

                <flux:dropdown position="bottom">
                    <flux:navbar.item icon:trailing="chevron-down">Admin</flux:navbar.item>

                    <flux:navmenu>
                        <flux:navmenu.item :href="route('association.members.admin')" :current="request()->routeIs('association.members.admin')">Mitglieder</flux:navmenu.item>
                    </flux:navmenu>
                </flux:dropdown>
            @endif
        </flux:navbar>

        <flux:spacer />

        <flux:navbar class="me-4">
            <flux:dropdown position="bottom" align="end" class="max-lg:hidden">
                <flux:navbar.item icon:trailing="information-circle">Info</flux:navbar.item>

                <flux:menu>
                    <flux:menu.item href="https://gitworkshop.dev/r/naddr1qvzqqqrhnypzqzklvar4enzu53t06vpzu3h465nwkzhk9p9ls4y5crwhs3lnu5pnqy88wumn8ghj7mn0wvhxcmmv9uqpxetfde6kuer6wasku7nfvukkummnw3eqdgsn8w/issues" target="_blank">Issues/Feedback</flux:menu.item>
                    <flux:menu.item :href="route('changelog')">Changelog</flux:menu.item>
                    <flux:menu.item href="https://github.com/HolgerHatGarkeineNode/einundzwanzig-nostr" target="_blank">Github</flux:menu.item>
                    <flux:menu.item href="https://einundzwanzig.space/kontakt/" target="_blank">Impressum</flux:menu.item>
                </flux:menu>
            </flux:dropdown>

            @if(\App\Support\NostrAuth::check())
                <form method="post" action="{{ route('logout') }}" @submit="$dispatch('nostrLoggedOut')">
                    @csrf
                    <flux:navbar.item type="submit" icon="arrow-right-start-on-rectangle">Logout</flux:navbar.item>
                </form>
            @else
                <flux:navbar.item icon="user" wire:key="loginBtn" @click="openNostrLogin">Mit Nostr verbinden</flux:navbar.item>
            @endif
        </flux:navbar>
    </flux:header>

    <flux:sidebar sticky collapsible="mobile" class="lg:hidden bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.header>
            <flux:sidebar.brand
                href="/"
                name="Einundzwanzig"
            >
                <img src="{{ asset('einundzwanzig-alpha.jpg') }}" alt="Logo" class="h-6 w-6">
            </flux:sidebar.brand>

            <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not(in-data-flux-sidebar-collapsed-desktop):-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            @if(\App\Support\NostrAuth::check())
                <flux:sidebar.item icon="rss" :href="route('association.news')" :current="request()->routeIs('association.news')">News</flux:sidebar.item>
                <flux:sidebar.item icon="identification" :href="route('association.profile')" :current="request()->routeIs('association.profile')">Meine Mitgliedschaft</flux:sidebar.item>
                <flux:sidebar.item icon="heart" :href="route('association.projectSupport')" :current="request()->routeIs('association.projectSupport')">Projekt-Unterstützungen</flux:sidebar.item>

                <flux:sidebar.group heading="Admin" class="grid">
                    <flux:sidebar.item icon="users" :href="route('association.members.admin')" :current="request()->routeIs('association.members.admin')">Mitglieder</flux:sidebar.item>
                </flux:sidebar.group>

                <flux:sidebar.group heading="Info" class="grid">
                    <flux:sidebar.item href="https://gitworkshop.dev/r/naddr1qvzqqqrhnypzqzklvar4enzu53t06vpzu3h465nwkzhk9p9ls4y5crwhs3lnu5pnqy88wumn8ghj7mn0wvhxcmmv9uqpxetfde6kuer6wasku7nfvukkummnw3eqdgsn8w/issues" target="_blank">Issues/Feedback</flux:sidebar.item>
                    <flux:sidebar.item :href="route('changelog')">Changelog</flux:sidebar.item>
                    <flux:sidebar.item href="https://github.com/HolgerHatGarkeineNode/einundzwanzig-nostr" target="_blank">Github</flux:sidebar.item>
                    <flux:sidebar.item href="https://einundzwanzig.space/kontakt/" target="_blank">Impressum</flux:sidebar.item>
                </flux:sidebar.group>
            @endif

            @if(\App\Support\NostrAuth::check())
                <flux:sidebar.spacer />
                <form method="post" action="{{ route('logout') }}" @submit="$dispatch('nostrLoggedOut')">
                    @csrf
                    <flux:sidebar.item icon="arrow-right-start-on-rectangle" type="submit">Logout</flux:sidebar.item>
                </form>
            @else
                <flux:sidebar.item icon="user" @click="openNostrLogin">Mit Nostr verbinden</flux:sidebar.item>
            @endif
        </flux:sidebar.nav>
    </flux:sidebar>

    <flux:main>
        {{ $slot }}
    </flux:main>

    @persist('toast')
        <flux:toast />
    @endpersist

    @fluxScripts
    @livewireScriptConfig
    <script>
        window.wnjParams = {
            position: 'bottom',
            accent: 'orange',
            startHidden: false,
            compactMode: false,
            disableOverflowFix: false,
        }
    </script>
    <script src="{{ asset('dist/window.nostr.min.js.js') }}"></script>
</body>
</html>
