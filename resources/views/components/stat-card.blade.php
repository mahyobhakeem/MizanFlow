@props([
    'label',
    'value',
    'unit' => 'ر.س',
    'variant' => 'primary',
    'icon' => 'bi-graph-up',
    'footerText' => null,
    'footerLink' => null,
    'footerLinkText' => 'التفاصيل'
])

<div class="mz-stat-card stat-{{ $variant }}">
    <div>
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="stat-label mb-0">{{ $label }}</div>
            <div class="stat-icon-wrapper">
                <i class="bi {{ $icon }}"></i>
            </div>
        </div>
        <div class="stat-value text-dark mt-1">
            {{ $value }}
            @if($unit)
                <span class="currency-symbol">{{ $unit }}</span>
            @endif
        </div>
    </div>
    @if($footerText || $footerLink)
    <div class="stat-footer">
        <span>{{ $footerText }}</span>
        @if($footerLink)
            <a href="{{ $footerLink }}" class="text-decoration-none fw-semibold small text-primary d-inline-flex align-items-center gap-1">
                <span>{{ $footerLinkText }}</span>
                <i class="bi bi-arrow-left"></i>
            </a>
        @endif
    </div>
    @endif
</div>
