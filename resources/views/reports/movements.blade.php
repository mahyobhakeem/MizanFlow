@extends('layouts.app')

@section('title', 'كشف حركة صنف تفصيلي')

@section('content')
<x-page-header 
    title="كشف حركة صنف ودفتر أستاذ المخزون" 
    subtitle="التتبع الزمني الدقيق لكل حركة واردة أو منصرفة وربطها بالمستند المصدر وتحديثات متوسط التكلفة WAC"
    :breadcrumbs="['التقارير التحليلية' => null, 'حركة صنف' => null]"
>
    <x-slot:actions>
        <button onclick="window.print()" class="btn btn-sm btn-mz-outline">
            <i class="bi bi-printer me-1"></i> طباعة الكشف
        </button>
    </x-slot:actions>
</x-page-header>

<!-- بطاقات ملخص الكميات المنقولة -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <x-stat-card 
            label="إجمالي الكميات الواردة (+)" 
            :value="'+' . number_format($totalIn, 2)" 
            unit="قطعة" 
            variant="success" 
            icon="bi-box-arrow-in-down-left"
            footerText="مشتريات، استلام تحويلات، مردودات"
        />
    </div>
    <div class="col-md-4">
        <x-stat-card 
            label="إجمالي الكميات المنصرفة (-)" 
            :value="'-' . number_format($totalOut, 2)" 
            unit="قطعة" 
            variant="danger" 
            icon="bi-box-arrow-up-right"
            footerText="مبيعات وشحن تحويلات"
        />
    </div>
    <div class="col-md-4">
        @php $netMove = $totalIn - $totalOut; @endphp
        <x-stat-card 
            label="صافي الحركة في الفترة المحددة" 
            :value="($netMove >= 0 ? '+' : '') . number_format($netMove, 2)" 
            unit="قطعة" 
            :variant="$netMove >= 0 ? 'primary' : 'danger'" 
            icon="bi-arrow-left-right"
            footerText="الفرق الرياضي الصافي للحركات المعروضة"
        />
    </div>
</div>

<!-- تصفية الكشف -->
<div class="mz-card mb-3 no-print">
    <div class="mz-card-body p-2 px-3">
        <form method="GET" action="{{ route('reports.movements') }}">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">المنتج المعني:</label>
                    <select name="product_id" class="form-select form-select-sm">
                        <option value="">— جميع المنتجات —</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->sku }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">المستودع:</label>
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">— جميع المستودعات —</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">نوع الحركة:</label>
                    <select name="movement_type" class="form-select form-select-sm">
                        <option value="">— الكل —</option>
                        <option value="sale" {{ request('movement_type') === 'sale' ? 'selected' : '' }}>صرف مبيعات</option>
                        <option value="sale_return" {{ request('movement_type') === 'sale_return' ? 'selected' : '' }}>مردود مبيعات</option>
                        <option value="purchase" {{ request('movement_type') === 'purchase' ? 'selected' : '' }}>توريد مشتريات</option>
                        <option value="transfer_out" {{ request('movement_type') === 'transfer_out' ? 'selected' : '' }}>تحويل صادر</option>
                        <option value="transfer_in" {{ request('movement_type') === 'transfer_in' ? 'selected' : '' }}>تحويل وارد</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">من تاريخ:</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">إلى تاريخ:</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-1 d-flex gap-1 align-self-end">
                    <button type="submit" class="btn btn-sm btn-mz-primary w-100" title="تصفية"><i class="bi bi-filter"></i></button>
                    @if(request()->anyFilled(['product_id', 'warehouse_id', 'movement_type', 'date_from', 'date_to']))
                        <a href="{{ route('reports.movements') }}" class="btn btn-sm btn-mz-outline" title="إعادة تعيين"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<!-- جدول حركات دفتر الأستاذ التفصيلي -->
<div class="mz-card mb-4">
    <div class="mz-card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-clock-history me-1 text-primary"></i> السجل الزمني لحركات المخزون</span>
        <span class="badge bg-light text-secondary border">{{ $movements->total() }} حركة مطابقة</span>
    </div>
    <div class="mz-card-body p-0">
        <div class="table-responsive">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th>رقم القيد</th>
                        <th>التاريخ والوقت</th>
                        <th>المستودع</th>
                        <th>المنتج</th>
                        <th class="text-center">نوع الحركة</th>
                        <th class="text-start">الكمية</th>
                        <th class="text-center">الرصيد قبل</th>
                        <th class="text-center">الرصيد بعد</th>
                        <th class="text-start">التكلفة WAC</th>
                        <th class="text-start">إجمالي القيمة</th>
                        <th>المرجع</th>
                        <th>المستخدم</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                    <tr>
                        <td><span class="code-tag">#{{ $m->id }}</span></td>
                        <td class="small text-muted num-cell">{{ $m->created_at->format('Y-m-d H:i') }}</td>
                        <td class="fw-semibold text-dark">{{ $m->warehouse->name }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $m->product->name }}</div>
                            <div class="text-muted small num-cell" style="font-size: 0.725rem;">SKU: {{ $m->product->sku }}</div>
                        </td>
                        <td class="text-center">
                            @if($m->movement_type === 'purchase')
                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.725rem;">توريد شراء</span>
                            @elseif($m->movement_type === 'sale')
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.725rem;">صرف مبيعات</span>
                            @elseif($m->movement_type === 'transfer_in')
                                <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.725rem;">تحويل وارد</span>
                            @elseif($m->movement_type === 'transfer_out')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.725rem;">تحويل صادر</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.725rem;">{{ $m->movement_type }}</span>
                            @endif
                        </td>
                        <td class="text-start num-cell fw-bold {{ $m->quantity > 0 ? 'text-success' : 'text-danger' }}">
                            {{ $m->quantity > 0 ? '+' : '' }}{{ number_format($m->quantity, 3) }}
                        </td>
                        <td class="text-center num-cell text-muted">{{ number_format($m->before_quantity, 3) }}</td>
                        <td class="text-center num-cell fw-bold">{{ number_format($m->after_quantity, 3) }}</td>
                        <td class="text-start num-cell">{{ number_format($m->unit_cost, 4) }} <span class="currency-symbol">ر.س</span></td>
                        <td class="text-start num-cell fw-bold text-dark">{{ number_format($m->total_cost, 2) }} <span class="currency-symbol">ر.س</span></td>
                        <td>
                            @if($m->reference instanceof App\Models\Purchase)
                                <a href="{{ route('purchases.show', $m->reference_id) }}" class="code-tag text-decoration-none">
                                    {{ $m->reference->invoice_number }}
                                </a>
                            @elseif($m->reference instanceof App\Models\Sale)
                                <a href="{{ route('sales.show', $m->reference_id) }}" class="code-tag text-decoration-none">
                                    {{ $m->reference->invoice_number }}
                                </a>
                            @elseif($m->reference instanceof App\Models\StockTransfer)
                                <a href="{{ route('transfers.show', $m->reference_id) }}" class="code-tag text-decoration-none">
                                    {{ $m->reference->transfer_number }}
                                </a>
                            @else
                                <span class="code-tag">#{{ $m->reference_id }}</span>
                            @endif
                        </td>
                        <td class="small">{{ $m->user->name }}</td>
                    </tr>
                    @empty
                    <x-table-empty 
                        icon="bi-clock-history" 
                        title="لا توجد حركات مطابقة لمعايير البحث" 
                        description="جرّب تعديل خيارات التصفية أو اختيار منتج ومستودع مختلف." 
                        :colspan="12" 
                    />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
        <div class="p-3 border-top no-print">
            {{ $movements->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection
