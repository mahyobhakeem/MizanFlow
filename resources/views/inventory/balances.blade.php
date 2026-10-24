@extends('layouts.app')

@section('title', 'أرصدة المستودعات وتقييم المخزون')

@section('content')
<x-page-header 
    title="أرصدة المستودعات وتقييم المخزون" 
    subtitle="متابعة الأرصدة اللحظية لكل صنف في كل مستودع مع احتساب متوسط التكلفة المرجح (WAC)"
    :breadcrumbs="['أرصدة المستودعات' => null]"
>
    <x-slot:actions>
        <button onclick="window.exportTableToCSV('.table-dense', 'warehouse_stock_balances.csv')" class="btn btn-sm btn-mz-outline">
            <i class="bi bi-file-earmark-spreadsheet me-1 text-success"></i> تصدير Excel / CSV
        </button>
        <a href="{{ route('inventory.movements') }}" class="btn btn-sm btn-mz-outline">
            <i class="bi bi-clock-history me-1 text-primary"></i>
            <span>سجل حركات دفتر الأستاذ</span>
        </a>
    </x-slot:actions>
</x-page-header>

<div class="mz-card mb-3 no-print">
    <div class="mz-card-body p-2 px-3">
        <form method="GET" action="{{ route('inventory.balances') }}">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent text-muted border-start-0 ps-3"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control border-end-0" placeholder="اسم المنتج، SKU، أو الباركود...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">— جميع المستودعات —</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-mz-primary flex-fill">تصفية الأرصدة</button>
                    @if(request()->anyFilled(['search', 'warehouse_id']))
                        <a href="{{ route('inventory.balances') }}" class="btn btn-sm btn-mz-outline" title="إعادة تعيين"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<!-- كبسولات الفلاتر النشطة -->
@if(request()->anyFilled(['search', 'warehouse_id']))
<div class="active-filters-bar no-print">
    <span class="small text-muted fw-semibold me-1"><i class="bi bi-funnel me-1 text-primary"></i>الفلاتر النشطة:</span>
    @if(request('warehouse_id'))
        @php $activeWh = $warehouses->firstWhere('id', request('warehouse_id')); @endphp
        @if($activeWh)
            <span class="filter-chip">
                <span class="chip-label">المستودع:</span>
                <span>{{ $activeWh->name }}</span>
                <a href="{{ request()->fullUrlWithQuery(['warehouse_id' => null]) }}" class="chip-remove" title="إلغاء الفلتر">&times;</a>
            </span>
        @endif
    @endif

    @if(request('search'))
        <span class="filter-chip">
            <span class="chip-label">البحث:</span>
            <span>"{{ request('search') }}"</span>
            <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="chip-remove" title="إلغاء الفلتر">&times;</a>
        </span>
    @endif

    <a href="{{ route('inventory.balances') }}" class="btn btn-sm btn-link text-danger text-decoration-none py-0 px-2 small ms-auto">
        <i class="bi bi-arrow-counterclockwise me-1"></i>إلغاء الكل
    </a>
</div>
@endif

<div class="table-dense-container mb-4">
    <table class="table-dense">
        <thead>
            <tr>
                <th>المستودع</th>
                <th>رمز الصنف (SKU)</th>
                <th>اسم المنتج</th>
                <th>التصنيف</th>
                <th>الوحدة</th>
                <th class="text-start" style="min-width: 130px;">الرصيد الفعلي والمؤشر</th>
                <th class="text-start">حد إعادة الطلب</th>
                <th class="text-start">متوسط التكلفة (WAC)</th>
                <th class="text-start">إجمالي تقييم المخزون</th>
            </tr>
        </thead>
        <tbody>
            @forelse($stocks as $stock)
            @php
                $qty = (float) $stock->quantity;
                $minLevel = (float) $stock->product->min_reorder_level;
                $isLow = $qty <= $minLevel;
                $totalVal = $qty * (float) $stock->weighted_average_cost;

                $maxThreshold = $minLevel > 0 ? ($minLevel * 2) : 100;
                $healthPercent = $qty > 0 ? min(100, round(($qty / $maxThreshold) * 100)) : 0;
                $healthClass = $qty <= 0 ? 'health-danger' : ($minLevel > 0 && $qty <= $minLevel ? 'health-warning' : 'health-safe');
            @endphp
            <tr>
                <td class="fw-semibold text-dark">{{ $stock->warehouse->name }}</td>
                <td><span class="code-tag copyable" title="انقر لنسخ SKU">{{ $stock->product->sku }}</span></td>
                <td>
                    <span class="fw-semibold text-dark">{{ $stock->product->name }}</span>
                    @if($isLow)
                        <span class="status-badge status-low ms-1" style="font-size: 0.65rem;">
                            منخفض
                        </span>
                    @endif
                </td>
                <td class="text-muted small">{{ $stock->product->category->name }}</td>
                <td>{{ $stock->product->unit->name }}</td>
                <td class="text-start num-cell">
                    <div class="fw-bold {{ $qty > 0 ? ($isLow ? 'text-warning' : 'text-dark') : 'text-danger' }}">
                        {{ number_format($qty, 3) }}
                    </div>
                    <div class="stock-health-track" title="نسبة التوفر: {{ $healthPercent }}%">
                        <div class="stock-health-fill {{ $healthClass }}" style="width: {{ $healthPercent }}%;"></div>
                    </div>
                </td>
                <td class="text-start num-cell text-muted">
                    {{ number_format($minLevel, 3) }}
                </td>
                <td class="text-start num-cell">
                    {{ number_format($stock->weighted_average_cost, 4) }} <span class="currency-symbol">ر.س</span>
                </td>
                <td class="text-start num-cell fw-bold text-success">
                    {{ number_format($totalVal, 2) }} <span class="currency-symbol">ر.س</span>
                </td>
            </tr>
            @empty
            <x-table-empty 
                icon="bi-stack" 
                title="لا توجد أرصدة مخزون مسجلة مطابقة للبحث" 
                description="يتم تسجيل الأرصدة تلقائياً عند اعتماد فواتير الشراء أو استلام التحويلات المخزنية." 
                :colspan="9" 
            />
            @endforelse
        </tbody>
    </table>
</div>

@if($stocks->hasPages())
<div class="p-3 border-top no-print">
    {{ $stocks->links('pagination::bootstrap-5') }}
</div>
@endif

@endsection
