@props(['active' => null, 'status' => null, 'label' => null])

@php
    if ($status !== null) {
        $normalized = strtolower($status);
        $class = match($normalized) {
            'completed', 'approved', 'active' => 'status-completed',
            'in_transit', 'pending', 'low' => 'status-in_transit',
            'voided', 'danger', 'cancelled' => 'status-voided',
            'draft', 'inactive' => 'status-draft',
            default => 'status-draft',
        };
        $badgeLabel = $label ?? match($normalized) {
            'completed' => 'معتمدة ومكتملة',
            'approved' => 'معتمد',
            'active' => 'نشط',
            'in_transit' => 'مشحون وفي الطريق',
            'pending' => 'معلق',
            'low' => 'منخفض المخزون',
            'voided' => 'ملغي بحركة عكسية',
            'cancelled' => 'ملغي',
            'draft' => 'مسودة',
            'inactive' => 'معطل',
            default => $status,
        };
    } else {
        $class = $active ? 'status-active' : 'status-voided';
        $badgeLabel = $label ?? ($active ? 'نشط' : 'معطل');
    }
@endphp

<span class="status-badge {{ $class }}">{{ $badgeLabel }}</span>
