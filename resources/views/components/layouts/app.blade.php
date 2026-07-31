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
                $items = collect([[
                    'label' => __('app.nav.dashboard'),
                    'icon' => 'ph-house',
                    'route' => route('dashboard'),
                    'active' => request()->routeIs('dashboard'),
                ]]);

                foreach ($services as $service) {
                    $items->push([
                        'label' => $service->name,
                        'icon' => $service->icon_class,
                        'route' => route('service.detail', $service->key),
                        'active' => request()->routeIs('service.detail') && request()->route('key') === $service->key,
                    ]);
                }

                $items->push([
                    'label' => __('app.nav.laporan'),
                    'icon' => 'ph-chart-bar',
                    'route' => route('laporan'),
                    'active' => request()->routeIs('laporan'),
                ], [
                    'label' => __('app.nav.owner_report'),
                    'icon' => 'ph-users-three',
                    'route' => route('laporan.owner'),
                    'active' => request()->routeIs('laporan.owner'),
                ], [
                    'label' => __('app.nav.tetapan'),
                    'icon' => 'ph-gear',
                    'route' => route('tetapan'),
                    'active' => request()->routeIs('tetapan'),
                ]);

                if ($user?->isAdmin()) {
                    $items->push([
                        'label' => __('app.nav.sheets'),
                        'icon' => 'ph-plugs-connected',
                        'route' => route('admin.sheets'),
                        'active' => request()->routeIs('admin.sheets'),
                    ]);
                }
            @endphp

            @foreach ($items as $item)
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
