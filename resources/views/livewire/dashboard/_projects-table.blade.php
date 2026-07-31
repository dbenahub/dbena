{{-- Jadual Projek — keputusan D2.
     Prototaip mempunyai `projectsByService` (16 projek) tetapi markupnya
     dibuang; data hanya digunakan untuk kiraan Purata Nilai Projek. --}}
<div class="dbena-card p-5 sm:p-6">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-base font-bold">{{ __('service.projects') }}</h2>
            <p class="mt-0.5 text-[12px] text-t55">{{ __('service.projects_hint') }}</p>
        </div>
        @can('create', App\Models\Project::class)
            <a href="{{ route('admin.panel') }}" wire:navigate
               class="flex items-center gap-1.5 rounded-[9px] px-3.5 py-2.5 text-[12.5px] font-semibold text-t80"
               style="border: 1px solid var(--border2)">
                <i class="ph-duotone ph-plus text-base" aria-hidden="true"></i> {{ __('service.add_project') }}
            </a>
        @endcan
    </div>

    @if ($projects->isEmpty())
        <p class="py-6 text-center text-[12.5px] text-t55">{{ __('service.no_projects') }}</p>
    @else
        {{-- Desktop --}}
        <div class="hidden md:block">
            <div class="grid gap-4 border-b pb-3 text-[11.5px] text-t55"
                 style="grid-template-columns: 2.2fr 1.8fr 1.2fr 1.4fr 1fr; border-color: var(--border)">
                <div>{{ __('service.col_project') }}</div>
                <div>{{ __('service.col_client') }}</div>
                <div>{{ __('service.col_value') }}</div>
                <div>{{ __('service.col_status') }}</div>
                <div>{{ __('service.col_date') }}</div>
            </div>

            @foreach ($projects as $project)
                <div class="grid items-center gap-4 border-b py-3.5 text-[13px]"
                     style="grid-template-columns: 2.2fr 1.8fr 1.2fr 1.4fr 1fr; border-color: var(--border3)">
                    <div class="font-semibold">{{ $project->name }}</div>
                    <div class="text-t70">{{ $project->client_name }}</div>
                    <div class="font-semibold">{{ $metrics->formatRm($project->value) }}</div>
                    <x-status-dot :color="$project->status->color()" :label="$project->status->label()" size="7px" />
                    <div class="text-t65">{{ $project->project_date->translatedFormat('d M Y') }}</div>
                </div>
            @endforeach
        </div>

        {{-- Mobile --}}
        <div class="flex flex-col gap-3 md:hidden">
            @foreach ($projects as $project)
                <div class="rounded-xl p-3.5" style="background: var(--hover-bg3); border: 1px solid var(--border3)">
                    <div class="mb-2 flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-[13px] font-semibold">{{ $project->name }}</div>
                            <div class="mt-0.5 text-[12px] text-t65">{{ $project->client_name }}</div>
                        </div>
                        <div class="shrink-0 text-right text-[13px] font-semibold">{{ $metrics->formatRm($project->value) }}</div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <x-status-dot :color="$project->status->color()" :label="$project->status->label()" size="7px" />
                        <span class="text-[11.5px] text-t55">{{ $project->project_date->translatedFormat('d M Y') }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
