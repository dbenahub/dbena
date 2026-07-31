{{-- Toast global — auto-hilang 2600ms seperti prototaip --}}
<div x-data="{ show: false, message: '', variant: 'success', timer: null }"
     x-on:dbena-toast.window="
        message = $event.detail.message;
        variant = $event.detail.variant || 'success';
        show = true;
        clearTimeout(timer);
        timer = setTimeout(() => show = false, 2600);
     "
     x-show="show"
     x-cloak
     x-transition:enter="transition ease-out duration-250"
     x-transition:enter-start="opacity-0 translate-y-3"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-end="opacity-0"
     role="status"
     aria-live="polite"
     class="fixed bottom-7 right-4 z-[100] flex max-w-[calc(100vw-2rem)] items-center gap-2.5 rounded-xl px-5 py-3.5 text-[13.5px] shadow-2xl sm:right-8"
     :style="variant === 'error'
        ? 'background: oklch(0.22 0.03 25); border: 1px solid oklch(0.6 0.2 25/0.4)'
        : 'background: var(--hover-bg); border: 1px solid oklch(0.78 0.12 85/0.4)'">
    <i class="ph-duotone text-[19px]"
       :class="variant === 'error' ? 'ph-warning-circle' : 'ph-check-circle'"
       :style="variant === 'error' ? 'color: oklch(0.7 0.18 25)' : 'color: oklch(0.72 0.15 145)'"
       aria-hidden="true"></i>
    <span x-text="message" class="text-t92"></span>
</div>
