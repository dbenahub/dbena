@props(['url' => null, 'initials' => '?', 'size' => 36, 'ring' => true])

@if ($url)
    <img src="{{ $url }}" alt="{{ $initials }}"
         class="shrink-0 rounded-full object-cover"
         style="width: {{ $size }}px; height: {{ $size }}px; {{ $ring ? 'border: 1.5px solid oklch(0.78 0.12 85/0.5)' : '' }}">
@else
    <div class="flex shrink-0 items-center justify-center rounded-full bg-hover2 font-display font-bold text-t80"
         style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.36) }}px;
                {{ $ring ? 'border: 1.5px solid oklch(0.78 0.12 85/0.5)' : '' }}"
         aria-hidden="true">{{ $initials }}</div>
@endif
