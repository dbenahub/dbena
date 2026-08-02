@php
    $user = auth()->user();
    $theme = $user?->theme ?? 'dark';
    $services = \App\Models\Service::orderBy('sort_order')->get();
    // Diberikan oleh setiap komponen Livewire melalui ->layoutData([...]).
    $pageTitle ??= __('dashboard.page_title');
    $pageSubtitle ??= __('dashboard.page_subtitle');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="{{ $theme }}"
      class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/logo-dbena.png') }}">

    {{-- Kepadatan dipakai SEBELUM stylesheet dimuatkan. Menetapkannya
         selepas paint bermakna halaman berkelip dari lapang ke padat
         setiap kali dimuatkan. --}}
    <script>
        try {
            var d = localStorage.getItem('dbena_density');
            if (d) document.documentElement.dataset.density = d;
        } catch (e) { /* noop */ }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased">
<div x-data="{ sidebarOpen: false }" class="flex min-h-screen w-full">

    {{-- ══ SIDEBAR ══ 264px sticky pada lg+, off-canvas drawer di bawahnya ══ --}}

    {{-- Latar gelap drawer (mobile sahaja) --}}
    <div x-show="sidebarOpen" x-cloak x-transition.opacity
         x-on:click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-black/55 lg:hidden"
         aria-hidden="true"></div>

    <aside class="fixed inset-y-0 left-0 z-50 flex h-screen w-[264px] shrink-0 flex-col overflow-y-auto border-r px-4 py-6 transition-transform duration-200 lg:sticky lg:top-0 lg:translate-x-0"
           style="background: var(--sidebar-bg); border-color: var(--border)"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           aria-label="{{ __('app.menu') }}">

        {{-- Logo dalam kotak putih berbingkai emas (seperti prototaip) --}}
        <div class="mb-7 flex items-center justify-between gap-2">
            <a href="{{ route('dashboard') }}" wire:navigate
               class="flex-1 rounded-[14px] px-3 py-4 text-center"
               style="background: oklch(0.97 0.005 260); border: 1px solid oklch(0.78 0.12 85/0.4)">
                <img src="{{ asset('images/logo-dbena.png') }}" alt="DBENA SDN BHD" class="block h-auto w-full">
            </a>
            <button type="button" x-on:click="sidebarOpen = false"
                    class="touch-target flex items-center justify-center rounded-lg text-t60 lg:hidden"
                    aria-label="{{ __('app.close_menu') }}">
                <i class="ph-duotone ph-x text-xl" aria-hidden="true"></i>
            </button>
        </div>

        {{-- Menu navigasi --}}
        <nav class="flex-1" aria-label="{{ __('app.menu') }}">
            @php
                /*
                 * Servis ialah SUB-ITEM di bawah Dashboard Utama, bukan
                 * item peringkat atas.
                 *
                 * Lima servis di peringkat atas menjadikan sidebar senarai
                 * lapan destinasi yang sama rata, jadi hubungan sebenar —
                 * bahawa setiap servis ialah pecahan dashboard — hilang.
                 * Ia juga bertambah panjang setiap kali servis ditambah,
                 * dan kini servis boleh ditambah dari Admin Panel.
                 */
                $diServis = request()->routeIs('service.detail');
                $kunciServis = request()->route('key');

                $dashboardAktif = request()->routeIs('dashboard');

                $lain = collect([
                    /*
                     * Carta Organisasi duduk di atas Reports, sejurus
                     * selepas Projects: ia menjawab "siapa" sebelum
                     * laporan menjawab "berapa".
                     *
                     * Kekal aktif untuk kedua-dua laluan paparan dan
                     * editor — admin yang menyunting carta masih berada
                     * dalam bahagian Carta Organisasi, dan sidebar yang
                     * tiada apa-apa disorot kelihatan seperti mereka telah
                     * tersesat keluar dari aplikasi.
                     */
                    ['label' => __('org.nav'), 'icon' => 'ph-tree-structure',
                     'route' => route('carta'),
                     'active' => request()->routeIs('carta') || request()->routeIs('admin.carta')],
                    ['label' => __('app.nav.laporan'), 'icon' => 'ph-chart-bar',
                     'route' => route('laporan'), 'active' => request()->routeIs('laporan')],
                    ['label' => __('app.nav.owner_report'), 'icon' => 'ph-users-three',
                     'route' => route('laporan.owner'), 'active' => request()->routeIs('laporan.owner')],
                    ['label' => __('app.nav.tetapan'), 'icon' => 'ph-gear',
                     'route' => route('tetapan'), 'active' => request()->routeIs('tetapan')],
                ]);

                if ($user?->isAdmin()) {
                    $lain->push([
                        'label' => __('app.nav.roadmap'), 'icon' => 'ph-road-horizon',
                        'route' => route('admin.roadmap'), 'active' => request()->routeIs('admin.roadmap'),
                    ]);

                    $lain->push([
                        'label' => __('app.nav.sheets'), 'icon' => 'ph-plugs-connected',
                        'route' => route('admin.sheets'), 'active' => request()->routeIs('admin.sheets'),
                    ]);
                }
            @endphp

            {{-- ── Dashboard Utama + servis ── --}}
            <div x-data="{
                    buka: @js($dashboardAktif || $diServis),
                 }" class="mb-0.5">

                <div class="flex items-stretch gap-1">
                    <a href="{{ route('dashboard') }}" wire:navigate
                       x-on:click="sidebarOpen = false; buka = true"
                       @if ($dashboardAktif) aria-current="page" @endif
                       class="flex min-w-0 flex-1 items-center gap-3 rounded-[10px] px-3.5 py-3 transition-colors hover:bg-hover"
                       @style([
                           'background: var(--hover-bg2); border-left: 3px solid oklch(0.78 0.12 85)' => $dashboardAktif,
                           'border-left: 3px solid transparent' => ! $dashboardAktif,
                       ])>
                        <i class="ph-duotone ph-house shrink-0 text-[22px]"
                           style="color: {{ $dashboardAktif ? 'oklch(0.78 0.12 85)' : 'var(--t68)' }}"
                           aria-hidden="true"></i>
                        <span class="truncate text-sm font-semibold"
                              style="color: {{ $dashboardAktif ? 'var(--t96)' : 'var(--t68)' }}">
                            {{ __('app.nav.dashboard') }}
                        </span>
                    </a>

                    {{-- Butang buka/tutup DIASINGKAN daripada pautan. Menjadikan
                         keseluruhan baris sebagai suis bermakna tiada cara untuk
                         pergi ke Dashboard Utama itu sendiri tanpa menutup
                         senarai servis. --}}
                    {{-- Hanya berat DUOTONE Phosphor diimport dalam app.css.
                         Versi pertama butang ini menggunakan ph-bold, yang
                         tidak wujud — jadi anak panah tidak dirender langsung
                         dan butang itu kelihatan seperti ruang kosong. --}}
                    <button type="button" x-on:click="buka = ! buka"
                            :aria-expanded="buka ? 'true' : 'false'"
                            aria-controls="nav-servis"
                            aria-label="{{ __('app.nav.services') }}"
                            :title="buka ? '{{ __('app.nav.hide_services') }}' : '{{ __('app.nav.show_services') }}'"
                            class="flex w-10 shrink-0 items-center justify-center rounded-[10px] transition-colors hover:bg-hover"
                            style="background: var(--hover-bg3); border: 1px solid var(--border2)">
                        <i class="ph-duotone ph-caret-down text-[18px] transition-transform duration-200"
                           :class="buka ? 'rotate-180' : ''"
                           style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                    </button>
                </div>

                <div id="nav-servis" x-show="buka" x-collapse x-cloak class="mt-0.5">
                    @foreach ($services as $service)
                        @php $aktif = $diServis && $kunciServis === $service->key; @endphp
                        <a href="{{ route('service.detail', $service->key) }}" wire:navigate
                           x-on:click="sidebarOpen = false"
                           @if ($aktif) aria-current="page" @endif
                           class="mb-0.5 ml-4 flex items-center gap-2.5 rounded-[9px] py-2.5 pl-3 pr-3 transition-colors hover:bg-hover"
                           @style([
                               'background: var(--hover-bg2); border-left: 2px solid oklch(0.78 0.12 85)' => $aktif,
                               'border-left: 2px solid var(--border2)' => ! $aktif,
                           ])>
                            <i class="ph-duotone {{ $service->icon_class }} shrink-0 text-[17px]"
                               style="color: {{ $aktif ? 'oklch(0.78 0.12 85)' : 'var(--t60)' }}"
                               aria-hidden="true"></i>
                            <span class="truncate text-[12.5px] font-semibold"
                                  style="color: {{ $aktif ? 'var(--t96)' : 'var(--t65)' }}">
                                {{ $service->name }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- ── Projek + kategori ──
                 Corak yang sama seperti Dashboard Utama: satu item peringkat
                 atas, kategori sebagai sub-item. Kategori ialah servis yang
                 sama, jadi menambah servis di Admin Panel menambah kategori
                 projek tanpa perubahan kod. --}}
            @php
                $diProjek = request()->routeIs('projek');
                $servisProjek = request()->query('servis');
            @endphp

            <div x-data="{ bukaProjek: @js($diProjek) }" class="mb-0.5">
                <div class="flex items-stretch gap-1">
                    <a href="{{ route('projek') }}" wire:navigate
                       x-on:click="sidebarOpen = false; bukaProjek = true"
                       @if ($diProjek) aria-current="page" @endif
                       class="flex min-w-0 flex-1 items-center gap-3 rounded-[10px] px-3.5 py-3 transition-colors hover:bg-hover"
                       @style([
                           'background: var(--hover-bg2); border-left: 3px solid oklch(0.78 0.12 85)' => $diProjek,
                           'border-left: 3px solid transparent' => ! $diProjek,
                       ])>
                        <i class="ph-duotone ph-folders shrink-0 text-[22px]"
                           style="color: {{ $diProjek ? 'oklch(0.78 0.12 85)' : 'var(--t68)' }}"
                           aria-hidden="true"></i>
                        <span class="truncate text-sm font-semibold"
                              style="color: {{ $diProjek ? 'var(--t96)' : 'var(--t68)' }}">
                            {{ __('project.nav') }}
                        </span>
                    </a>

                    <button type="button" x-on:click="bukaProjek = ! bukaProjek"
                            :aria-expanded="bukaProjek ? 'true' : 'false'"
                            aria-controls="nav-projek"
                            aria-label="{{ __('project.nav') }}"
                            class="flex w-10 shrink-0 items-center justify-center rounded-[10px] transition-colors hover:bg-hover"
                            style="background: var(--hover-bg3); border: 1px solid var(--border2)">
                        <i class="ph-duotone ph-caret-down text-[18px] transition-transform duration-200"
                           :class="bukaProjek ? 'rotate-180' : ''"
                           style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                    </button>
                </div>

                <div id="nav-projek" x-show="bukaProjek" x-collapse x-cloak class="mt-0.5">
                    @foreach ($services as $service)
                        @php $aktifP = $diProjek && $servisProjek === $service->key; @endphp
                        <a href="{{ route('projek', ['servis' => $service->key]) }}" wire:navigate
                           x-on:click="sidebarOpen = false"
                           @if ($aktifP) aria-current="page" @endif
                           class="mb-0.5 ml-4 flex items-center gap-2.5 rounded-[9px] py-2.5 pl-3 pr-3 transition-colors hover:bg-hover"
                           @style([
                               'background: var(--hover-bg2); border-left: 2px solid oklch(0.78 0.12 85)' => $aktifP,
                               'border-left: 2px solid var(--border2)' => ! $aktifP,
                           ])>
                            <i class="ph-duotone {{ $service->icon_class }} shrink-0 text-[17px]"
                               style="color: {{ $aktifP ? 'oklch(0.78 0.12 85)' : 'var(--t60)' }}"
                               aria-hidden="true"></i>
                            <span class="truncate text-[12.5px] font-semibold"
                                  style="color: {{ $aktifP ? 'var(--t96)' : 'var(--t65)' }}">
                                {{ $service->name }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- ── Selebihnya ── --}}
            @foreach ($lain as $item)
                <a href="{{ $item['route'] }}" wire:navigate
                   x-on:click="sidebarOpen = false"
                   @if ($item['active']) aria-current="page" @endif
                   class="mb-0.5 flex items-center gap-3 rounded-[10px] px-3.5 py-3 transition-colors hover:bg-hover"
                   @style([
                       'background: var(--hover-bg2); border-left: 3px solid oklch(0.78 0.12 85)' => $item['active'],
                       'border-left: 3px solid transparent' => ! $item['active'],
                   ])>
                    <i class="ph-duotone {{ $item['icon'] }} shrink-0 text-[22px]"
                       style="color: {{ $item['active'] ? 'oklch(0.78 0.12 85)' : 'var(--t68)' }}"
                       aria-hidden="true"></i>
                    <span class="text-sm font-semibold"
                          style="color: {{ $item['active'] ? 'var(--t96)' : 'var(--t68)' }}">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Toggle Dark Mode --}}
        <div class="mt-3 border-t pt-4" style="border-color: var(--border)">
            <livewire:theme-toggle />
        </div>
    </aside>

    {{-- ══ KANDUNGAN UTAMA ══ --}}
    <div class="flex min-w-0 flex-1 flex-col">

        {{-- ══ TOPBAR ══ --}}
        <header class="sticky top-0 z-30 flex h-[68px] shrink-0 items-center justify-between gap-3 border-b px-4 sm:h-[76px] sm:px-6 lg:px-8"
                style="background: var(--bg); border-color: var(--border)">

            <div class="flex min-w-0 items-center gap-3">
                <button type="button" x-on:click="sidebarOpen = true"
                        class="touch-target flex items-center justify-center rounded-lg text-t80 lg:hidden"
                        aria-label="{{ __('app.open_menu') }}">
                    <i class="ph-duotone ph-list text-2xl" aria-hidden="true"></i>
                </button>
                <div class="min-w-0">
                    <h1 class="truncate font-display text-[17px] font-extrabold tracking-wide sm:text-[22px]"
                        style="color: oklch(0.78 0.12 85); letter-spacing: 1px">
                        {{ $pageTitle }}
                    </h1>
                    <p class="mt-0.5 hidden truncate text-[13px] text-t65 sm:block">
                        {{ $pageSubtitle }}
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-2 sm:gap-3.5">
                <livewire:language-switcher />
                <x-density-toggle />
                <livewire:notification-bell />

                {{-- Dropdown profil --}}
                <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">
                    <button type="button" x-on:click="open = !open"
                            :aria-expanded="open.toString()" aria-haspopup="menu"
                            class="flex items-center gap-2.5 rounded-[10px] py-1.5 pl-1.5 pr-2 transition-colors hover:bg-hover3 sm:gap-3">
                        <x-avatar :url="$user?->avatarUrl()" :initials="$user?->initials() ?? '?'" :size="36" />
                        <span class="hidden leading-tight sm:block">
                            <span class="block text-[13px] font-semibold text-t94">{{ $user?->name }}</span>
                            <span class="block text-[11px] italic text-t55">{{ $user?->role->label() }}</span>
                        </span>
                        <i class="ph-duotone ph-caret-down hidden text-sm text-t60 sm:block" aria-hidden="true"></i>
                    </button>

                    <div x-show="open" x-cloak x-transition role="menu"
                         class="absolute right-0 top-[56px] z-30 w-[220px] overflow-hidden rounded-xl bg-card shadow-2xl"
                         style="border: 1px solid var(--border2)">
                        <div class="border-b px-4 py-3 sm:hidden" style="border-color: var(--border)">
                            <div class="text-[13px] font-semibold text-t94">{{ $user?->name }}</div>
                            <div class="text-[11px] text-t55">{{ $user?->email }}</div>
                        </div>
                        <a href="{{ route('tetapan') }}" wire:navigate role="menuitem"
                           class="flex items-center gap-2.5 px-4 py-3 text-[13px] text-t90 transition-colors hover:bg-hover2">
                            <i class="ph-duotone ph-gear text-[17px]" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                            {{ __('app.settings') }}
                        </a>
                        @if ($user?->isAdmin())
                            <a href="{{ route('admin.panel') }}" wire:navigate role="menuitem"
                               class="flex items-center gap-2.5 px-4 py-3 text-[13px] text-t90 transition-colors hover:bg-hover2">
                                <i class="ph-duotone ph-shield-check text-[17px]" style="color: oklch(0.78 0.12 85)" aria-hidden="true"></i>
                                {{ __('app.admin_panel') }}
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" role="menuitem"
                                    class="flex w-full items-center gap-2.5 px-4 py-3 text-left text-[13px] transition-colors hover:bg-hover2"
                                    style="color: oklch(0.75 0.1 20)">
                                <i class="ph-duotone ph-sign-out text-[17px]" aria-hidden="true"></i>
                                {{ __('app.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- ══ KANDUNGAN ══ --}}
        <main class="flex flex-1 flex-col gap-5 px-4 pb-16 pt-5 sm:px-6 sm:gap-6 sm:pt-7 lg:px-8">
            {{ $slot }}
        </main>
    </div>
</div>

<x-toast />

@livewireScripts
</body>
</html>
