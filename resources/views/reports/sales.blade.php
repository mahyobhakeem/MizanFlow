@extends('layouts.app')

@section('title', 'تقرير المبيعات وهوامش الأرباح')

@section('content')
<x-page-header 
    title="تقرير المبيعات وهوامش الربح الإجمالية" 
    subtitle="تحليل مالي وتشغيلي للمبيعات وتكاليف البضاعة المباعة (COGS) وهوامش الربحية من واقع لقطات WAC التاريخية"
    :breadcrumbs="['التقارير التحليلية' => null, 'المبيعات والأرباح' => null]"
>
    <x-slot:actions>
        <button onclick="window.print()" class="btn btn-sm btn-mz-outline">
            <i class="bi bi-printer me-1"></i> طباعة التقرير
        </button>
    </x-slot:actions>
</x-page-header>

<!-- بطاقات الملخص المالي المحسوبة برمجياً بدقة تامة -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <x-stat-card 
            label="صافي المبيعات (شامل الضريبة)" 
            :value="number_format($netSalesTotal, 2)" 
            unit="ر.س" 
            variant="primary" 
            icon="bi-receipt"
            :footerText="'الضريبة المحصلة: ' . number_format($totalTax, 2) . ' ر.س'"
        />
    </div>
    <div class="col-6 col-md-3">
        <x-stat-card 
            label="تكلفة البضاعة المباعة (COGS)" 
            :value="number_format($totalCogs, 2)" 
            unit="ر.س" 
            variant="warning" 
            icon="bi-calculator"
            footerText="من واقع لقطات WAC التاريخية"
        />
    </div>
    <div class="col-6 col-md-3">
        <x-stat-card 
            label="مجمل الربح الفعلي (Gross Profit)" 
            :value="number_format($grossProfit, 2)" 
            unit="ر.س" 
            variant="success" 
            icon="bi-graph-up-arrow"
            footerText="الإيراد غير الضريبي - التكلفة"
        />
    </div>
    <div class="col-6 col-md-3">
        <x-stat-card 
            label="هامش الربح الإجمالي (Margin %)" 
            :value="$grossMarginPct" 
            unit="%" 
            variant="primary" 
            icon="bi-percent"
            :footerText="'المحصل نقداً: ' . number_format($totalPaid, 2) . ' ر.س'"
        />
    </div>
</div>

<!-- تصفية التقرير -->
<div class="mz-card mb-3 no-print">
    <div class="mz-card-body p-2 px-3">
        <form method="GET" action="{{ route('reports.sales') }}">
            <div class="row g-2 align-items-center">
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">من تاريخ:</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">إلى تاريخ:</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">المستودع:</label>
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">— جميع المستودعات —</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}{{ ! $wh->is_active ? ' (معطل)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">العميل:</label>
                    <select name="customer_id" class="form-select form-select-sm">
                        <option value="">— جميع العملاء —</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}{{ ! $c->is_active ? ' (معطل)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">طريقة الدفع:</label>
                    <select name="payment_type" class="form-select form-select-sm">
                        <option value="">— الكل —</option>
                        <option value="cash" {{ request('payment_type') === 'cash' ? 'selected' : '' }}>نقدي</option>
                        <option value="card" {{ request('payment_type') === 'card' ? 'selected' : '' }}>شبكة</option>
                        <option value="credit" {{ request('payment_type') === 'credit' ? 'selected' : '' }}>آجل</option>
                        <option value="split" {{ request('payment_type') === 'split' ? 'selected' : '' }}>مجزأ</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1 align-self-end">
                    <button type="submit" class="btn btn-sm btn-mz-primary flex-fill">تحديث التقرير</button>
                    @if(request()->anyFilled(['date_from', 'date_to', 'warehouse_id', 'customer_id', 'payment_type']))
                        <a href="{{ route('reports.sales') }}" class="btn btn-sm btn-mz-outline" title="إعادة تعيين"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<!-- المنتجات الأعلى مبيعاً وربحية -->
@if(count($topProducts) > 0)
<div class="mz-card mb-4">
    <div class="mz-card-header">
        <span class="fw-bold"><i class="bi bi-trophy me-1 text-primary"></i> الأصناف الأكثر مساهمة في الإيرادات والأرباح</span>
    </div>
    <div class="mz-card-body p-0">
        <div class="table-responsive">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>رمز SKU</th>
                        <th class="text-center">الكمية المباعة</th>
                        <th class="text-start">إجمالي الإيراد</th>
                        <th class="text-start">مجمل الربح المحقق</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($topProducts as $tp)
                    <tr>
                        <td class="fw-semibold text-dark">{{ $tp['name'] }}</td>
                        <td><span class="code-tag">{{ $tp['sku'] }}</span></td>
                        <td class="text-center num-cell">{{ number_format($tp['quantity'], 2) }}</td>
                        <td class="text-start num-cell fw-bold text-dark">{{ number_format($tp['revenue'], 2) }} <span class="currency-symbol">ر.س</span></td>
                        <td class="text-start num-cell fw-bold text-success">{{ number_format($tp['profit'], 2) }} <span class="currency-symbol">ر.س</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- جدول تفاصيل الفواتير المعتمدة الخاضعة للتقرير -->
<div class="mz-card mb-4">
    <div class="mz-card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-table me-1 text-primary"></i> كشف فواتير المبيعات التفصيلي</span>
        <span class="badge bg-light text-secondary border">{{ $sales->total() }} فاتورة مطابقة</span>
    </div>
    <div class="mz-card-body p-0">
        <div class="table-responsive">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>العميل</th>
                        <th>المستودع</th>
                        <th>التاريخ</th>
                        <th>طريقة الدفع</th>
                        <th class="text-start">المبيعات</th>
                        <th class="text-start">تكلفة COGS</th>
                        <th class="text-start">الربح</th>
                        <th class="text-center">هامش %</th>
                        <th class="text-start no-print">عرض</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    @php
                        $saleCogs = $sale->items->sum(fn($i) => (float)$i->quantity * (float)$i->unit_cost_snapshot);
                        $saleProfit = $sale->totalGrossProfit();
                        $saleNetRev = (float)$sale->subtotal - (float)$sale->discount_amount;
                        $margin = $saleNetRev > 0 ? round(($saleProfit / $saleNetRev) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('sales.show', $sale) }}" class="code-tag text-decoration-none">
                                {{ $sale->invoice_number }}
                            </a>
                        </td>
                        <td class="fw-semibold text-dark">{{ $sale->customer?->name ?? 'عميل نقدي عابر' }}</td>
                        <td class="text-muted small">{{ $sale->warehouse->name }}</td>
                        <td class="small num-cell">{{ $sale->invoice_date->format('Y-m-d') }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $sale->paymentTypeLabel() }}</span></td>
                        <td class="text-start num-cell fw-bold text-dark">{{ number_format($sale->total_amount, 2) }} <span class="currency-symbol">ر.س</span></td>
                        <td class="text-start num-cell text-muted">{{ number_format($saleCogs, 2) }} <span class="currency-symbol">ر.س</span></td>
                        <td class="text-start num-cell fw-bold {{ $saleProfit >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($saleProfit, 2) }} <span class="currency-symbol">ر.س</span>
                        </td>
                        <td class="text-center num-cell">{{ $margin }}%</td>
                        <td class="text-start no-print">
                            <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-mz-outline p-1 px-2" title="عرض الفاتورة">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <x-table-empty 
                        icon="bi-graph-down" 
                        title="لا توجد فواتير مبيعات مسجلة ومطابقة للتصفية" 
                        description="جرّب تعديل نطاق التواريخ أو اختيار مستودع مختلف لتحديث التقرير." 
                        :colspan="10" 
                    />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
        <div class="p-3 border-top no-print">
            {{ $sales->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection
