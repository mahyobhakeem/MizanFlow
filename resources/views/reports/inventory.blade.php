@extends('layouts.app')

@section('title', 'تقرير تقييم المخزون وحالة الأصناف')

@section('content')
<x-page-header 
    title="تقرير تقييم المخزون والحد الأدنى للطلب" 
    subtitle="حساب القيمة الإجمالية للمخزون وفق متوسط التكلفة المرجح (WAC) ومراقبة الأصناف المنخفضة والنافدة"
    :breadcrumbs="['التقارير التحليلية' => null, 'تقييم المخزون' => null]"
>
    <x-slot:actions>
        <button onclick="window.exportTableToCSV('.table-dense', 'inventory_valuation_report.csv')" class="btn btn-sm btn-mz-outline">
            <i class="bi bi-file-earmark-spreadsheet me-1 text-success"></i> تصدير Excel / CSV
        </button>
        <button onclick="window.print()" class="btn btn-sm btn-mz-outline">
            <i class="bi bi-printer me-1 text-primary"></i> طباعة التقرير
        </button>
    </x-slot:actions>
</x-page-header>

<!-- ترويسة الطباعة الرسمية للأوراق والمستندات -->
<div class="d-none d-print-block mb-4 p-3 border-bottom">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold mb-1">منظومة ميزان فلو — تقرير تقييم المخزون والحد الأدنى للطلب</h3>
            <div class="small text-muted">تاريخ الاستخراج: {{ date('Y-m-d H:i') }} | المستودع: {{ request('warehouse_id') ? ($warehouses->firstWhere('id', request('warehouse_id'))?->name ?? 'مستودع محدد') : 'كافة المستودعات المركزية والفرعية' }}</div>
        </div>
        <div class="text-start">
            <div class="fw-bold fs-5 text-primary">MizanFlow Cloud ERP</div>
            <small class="text-muted">تقرير رسمي موثق بتكاليف WAC</small>
        </div>
    </div>
</div>

<!-- بطاقات الملخص المخزني المباشر -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <x-stat-card 
            label="إجمالي القيمة التقديرية (WAC)" 
            :value="number_format($totalValuation, 2)" 
            unit="ر.س" 
            variant="primary" 
            icon="bi-boxes"
            footerText="القيمة الإجمالية الفعلية للبضاعة"
        />
    </div>
    <div class="col-6 col-md-3">
        <x-stat-card 
            label="إجمالي الكميات بالمستودعات" 
            :value="number_format($totalQuantity, 2)" 
            unit="قطعة" 
            variant="primary" 
            icon="bi-stack"
            footerText="مجموع الوحدات المخزنة"
        />
    </div>
    <div class="col-6 col-md-3">
        <x-stat-card 
            label="أصناف تحت حد الطلب" 
            :value="$lowStockCount" 
            unit="صنف" 
            variant="warning" 
            icon="bi-exclamation-triangle"
            footerText="تحتاج إصدار أمر شراء توريدي"
        />
    </div>
    <div class="col-6 col-md-3">
        <x-stat-card 
            label="أصناف نافدة تماماً (رصيد 0)" 
            :value="$outOfStockCount" 
            unit="صنف" 
            variant="danger" 
            icon="bi-x-circle"
            footerText="غير متوفرة للبيع الفوري"
        />
    </div>
</div>

<!-- تصفية التقرير -->
<div class="mz-card mb-3 no-print">
    <div class="mz-card-body p-2 px-3">
        <form method="GET" action="{{ route('reports.inventory') }}">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">المستودع:</label>
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">— جميع المستودعات —</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">التصنيف:</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">— جميع التصنيفات —</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">حالة توفر المخزون:</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">— جميع الحالات —</option>
                        <option value="in_stock" {{ request('status') === 'in_stock' ? 'selected' : '' }}>متوفر برصيد إيجابي</option>
                        <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>منخفض (تحت حد الطلب)</option>
                        <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>نافد تماماً (صفر)</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2 align-self-end">
                    <button type="submit" class="btn btn-sm btn-mz-primary flex-fill">تحديث التقرير</button>
                    @if(request()->anyFilled(['warehouse_id', 'category_id', 'status']))
                        <a href="{{ route('reports.inventory') }}" class="btn btn-sm btn-mz-outline" title="إعادة تعيين"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<!-- كبسولات الفلاتر النشطة (Google Active Filter Chips) -->
@if(request()->anyFilled(['warehouse_id', 'category_id', 'status']))
<div class="active-filters-bar no-print">
    <span class="small text-muted fw-semibold me-1"><i class="bi bi-funnel me-1 text-primary"></i>الفلاتر النشطة:</span>
    @if(request('warehouse_id'))
        @php $activeWh = $warehouses->firstWhere('id', request('warehouse_id')); @endphp
        @if($activeWh)
            <span class="filter-chip">
                <span class="chip-label">المستودع:</span>
                <span>{{ $activeWh->name }}</span>
                <a href="{{ request()->fullUrlWithQuery(['warehouse_id' => null]) }}" class="chip-remove" title="إلغاء هذا الفلتر">&times;</a>
            </span>
        @endif
    @endif

    @if(request('category_id'))
        @php $activeCat = $categories->firstWhere('id', request('category_id')); @endphp
        @if($activeCat)
            <span class="filter-chip">
                <span class="chip-label">التصنيف:</span>
                <span>{{ $activeCat->name }}</span>
                <a href="{{ request()->fullUrlWithQuery(['category_id' => null]) }}" class="chip-remove" title="إلغاء هذا الفلتر">&times;</a>
            </span>
        @endif
    @endif

    @if(request('status'))
        @php 
            $statusLabels = [
                'in_stock' => 'متوفر فقط',
                'low_stock' => 'منخفض المخزون',
                'out_of_stock' => 'نافد تماماً'
            ];
        @endphp
        <span class="filter-chip">
            <span class="chip-label">الحالة:</span>
            <span>{{ $statusLabels[request('status')] ?? request('status') }}</span>
            <a href="{{ request()->fullUrlWithQuery(['status' => null]) }}" class="chip-remove" title="إلغاء هذا الفلتر">&times;</a>
        </span>
    @endif

    <a href="{{ route('reports.inventory') }}" class="btn btn-sm btn-link text-danger text-decoration-none py-0 px-2 small ms-auto">
        <i class="bi bi-arrow-counterclockwise me-1"></i>إلغاء الكل
    </a>
</div>
@endif

<!-- جدول تقييم المخزون التفصيلي -->
<div class="mz-card mb-4">
    <div class="mz-card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-boxes me-1 text-primary"></i> أرصدة وتقييم الأصناف بالمستودعات</span>
        <span class="badge bg-light text-secondary border">{{ $stocks->total() }} سجل</span>
    </div>
    <div class="mz-card-body p-0">
        <div class="table-responsive">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>المستودع</th>
                        <th>التصنيف</th>
                        <th class="text-center" style="min-width: 130px;">الرصيد المتاح والمؤشر</th>
                        <th class="text-center">حد إعادة الطلب</th>
                        <th class="text-start">متوسط التكلفة WAC</th>
                        <th class="text-start">القيمة الإجمالية</th>
                        <th class="text-center">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $stock)
                    @php
                        $qty = (float) $stock->quantity;
                        $minLevel = (float) $stock->product->min_reorder_level;
                        $wac = (float) $stock->weighted_average_cost;
                        $lineVal = round($qty * $wac, 2);

                        // حساب مؤشر شريط السلامة المخزنية
                        $maxThreshold = $minLevel > 0 ? ($minLevel * 2) : 100;
                        $healthPercent = $qty > 0 ? min(100, round(($qty / $maxThreshold) * 100)) : 0;
                        $healthClass = $qty <= 0 ? 'health-danger' : ($minLevel > 0 && $qty <= $minLevel ? 'health-warning' : 'health-safe');
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $stock->product->name }}</div>
                            <div class="mt-1">
                                <span class="code-tag copyable" title="انقر لنسخ SKU">SKU: {{ $stock->product->sku }}</span>
                            </div>
                        </td>
                        <td class="fw-semibold text-dark">{{ $stock->warehouse->name }}</td>
                        <td class="small text-muted">{{ $stock->product->category?->name ?? '—' }}</td>
                        <td class="text-center num-cell">
                            <div class="fw-bold {{ $qty <= 0 ? 'text-danger' : ($minLevel > 0 && $qty <= $minLevel ? 'text-warning' : 'text-dark') }}">
                                {{ number_format($qty, 2) }}
                                <span class="small text-muted fw-normal">{{ $stock->product->unit?->name }}</span>
                            </div>
                            <!-- شريط السلامة المخزنية الميكروي -->
                            <div class="stock-health-track" title="نسبة التوفر: {{ $healthPercent }}%">
                                <div class="stock-health-fill {{ $healthClass }}" style="width: {{ $healthPercent }}%;"></div>
                            </div>
                        </td>
                        <td class="text-center num-cell text-muted small">
                            {{ $minLevel > 0 ? number_format($minLevel, 2) : '—' }}
                        </td>
                        <td class="text-start num-cell">{{ number_format($wac, 4) }} <span class="currency-symbol">ر.س</span></td>
                        <td class="text-start num-cell fw-bold text-success">{{ number_format($lineVal, 2) }} <span class="currency-symbol">ر.س</span></td>
                        <td class="text-center">
                            @if($qty <= 0)
                                <span class="status-badge status-danger">نافد</span>
                            @elseif($minLevel > 0 && $qty <= $minLevel)
                                <span class="status-badge status-low">منخفض</span>
                            @else
                                <span class="status-badge status-active">متاح</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <x-table-empty 
                        icon="bi-boxes" 
                        title="لا توجد بيانات مخزنية مطابقة لمعايير البحث" 
                        description="جرّب اختيار مستودع أو تصنيف آخر لتحديث تقييم المخزون." 
                        :colspan="8" 
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
    </div>
</div>
@endsection
