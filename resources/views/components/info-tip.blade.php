@props(['text', 'placement' => 'top'])

<span {{ $attributes->merge(['class' => 'info-tip']) }}
      tabindex="0"
      role="button"
      aria-label="{{ $text }}"
      data-placement="{{ $placement }}">
    <svg aria-hidden="true" class="info-tip-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 17v-5m0-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span class="info-tip-bubble" role="tooltip">{{ $text }}</span>
</span>
