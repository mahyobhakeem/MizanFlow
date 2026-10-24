@props([
    'icon' => 'bi-inbox',
    'title' => 'لا توجد سجلات مطابقة',
    'description' => null,
    'actionUrl' => null,
    'actionText' => null,
    'actionIcon' => 'bi-plus-lg',
    'colspan' => 1
])

<tr>
    <td colspan="{{ $colspan }}" class="p-0 border-0">
        <div class="mz-empty-state">
            <div class="mz-empty-state-icon">
                <i class="bi {{ $icon }}"></i>
            </div>
            <div class="mz-empty-state-title">{{ $title }}</div>
            @if($description)
                <div class="mz-empty-state-description">{{ $description }}</div>
            @endif
            @if($actionUrl && $actionText)
                <a href="{{ $actionUrl }}" class="btn btn-sm btn-mz-primary mt-1">
                    <i class="bi {{ $actionIcon }} me-1"></i>
                    <span>{{ $actionText }}</span>
                </a>
            @endif
        </div>
    </td>
</tr>
