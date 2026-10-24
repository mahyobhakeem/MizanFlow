@extends('layouts.app')

@section('title', 'لوحة التحكم والمؤشرات التشغيلية')

@section('content')
<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div>
        <h1 class="page-title">لوحة التحكم والمؤشرات التشغيلية</h1>
        <div class="page-subtitle">نظرة عامة حية ومباشرة على المبيعات، المخزون، السيولة، والتنبيهات الحرجة من واقع قاعدة البيانات.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('sales.create') }}" class="btn btn-mz-primary">
            <i class="bi bi-cart-plus"></i>
            <span>نقطة بيع جديدة (POS)</span>
        </a>
        @if(auth()->user()->hasRole(['admin', 'manager']))
        <a href="{{ route('purchases.create') }}" class="btn btn-mz-outline">
            <i class="bi bi-truck"></i>
            <span>فاتورة شراء وتوريد</span>
        </a>
        @endif
    </div>
</div>


<!-- Operational KPIs (Role-Aware Real Aggregates) -->
<div class="row g-3 mb-4">
    <!-- إجمالي المبيعات المعتمدة -->
    <div class="col-12 col-sm-6 col-xl-3">
        <x-stat-card 
            label="إجمالي المبيعات المعتمدة" 
            :value="number_format($totalSalesRevenue, 2)" 
            unit="ر.س" 
            variant="primary" 
            icon="bi-receipt-cutoff"
            :footerText="$completedSalesCount . ' فاتورة معتمدة'"
            :footerLink="route('sales.index')"
            footerLinkText="فواتير المبيعات"
        />
    </div>

    @if($canViewFinancials)
    <!-- مجمل الأرباح التاريخية (WAC Moving Average) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <x-stat-card 
            label="مجمل الأرباح التاريخية المحققة" 
            :value="number_format($totalGrossProfit, 2)" 
            unit="ر.س" 
            variant="success" 
            icon="bi-graph-up-arrow"
            footerText="صافي الإيراد ناقص تكلفة WAC"
            :footerLink="route('reports.sales')"
            footerLinkText="تقرير الأرباح"
        />
    </div>

    <!-- تقييم المخزون الحالي (WAC) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <x-stat-card 
            label="تقييم المخزون الكلي بالمستودعات" 
            :value="number_format($totalInventoryValuation, 2)" 
            unit="ر.س" 
            variant="warning" 
            icon="bi-boxes"
            :footerText="number_format($totalStockUnits, 2) . ' قطعة بالمخازن'"
            :footerLink="route('inventory.balances')"
            footerLinkText="أرصدة المستودعات"
        />
    </div>

    <!-- مديونيات العملاء القائمة -->
    <div class="col-12 col-sm-6 col-xl-3">
        <x-stat-card 
            label="مديونيات العملاء (ذمم مدينة)" 
            :value="number_format($totalReceivables, 2)" 
            unit="ر.س" 
            variant="danger" 
            icon="bi-people"
            :footerText="'مستحقات الموردين: ' . number_format($totalPayables, 2) . ' ر.س'"
            :footerLink="route('reports.customers')"
            footerLinkText="سجل الذمم"
        />
    </div>
    @else
    <!-- للكاشير: مبيعات اليوم، الفواتير المسودة، والوحدات بالمخازن -->
    <div class="col-12 col-sm-6 col-xl-3">
        <x-stat-card 
            label="مبيعات اليوم المسجلة" 
            :value="number_format($todaySalesTotal, 2)" 
            unit="ر.س" 
            variant="primary" 
            icon="bi-calendar-check"
            :footerText="$todaySalesCount . ' عملية بيع اليوم'"
            :footerLink="route('sales.index')"
            footerLinkText="فواتير اليوم"
        />
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <x-stat-card 
            label="فواتير مسودة معلقة" 
            :value="$draftSalesCount" 
            unit="مسودة" 
            variant="warning" 
            icon="bi-pencil-square"
            footerText="لم تؤثر على المخزون بعد"
            :footerLink="route('sales.index', ['status' => 'draft'])"
            footerLinkText="متابعة المسودات"
        />
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <x-stat-card 
            label="إجمالي الوحدات بالمخازن" 
            :value="number_format($totalStockUnits, 2)" 
            unit="قطعة" 
            variant="primary" 
            icon="bi-box-seam"
            footerText="مجموع الأرصدة المتاحة"
        />
    </div>
    @endif
</div>

<!-- Recent Invoices and Inventory Ledger -->
<div class="row g-3 mb-4">
    <!-- أحدث فواتير المبيعات -->
    <div class="col-lg-7">
        <div class="mz-card h-100 mb-0">
            <div class="mz-card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-receipt me-1 text-primary"></i> أحدث فواتير المبيعات الصادرة</span>
                <a href="{{ route('sales.index') }}" class="btn btn-sm btn-mz-outline py-1 px-2" style="font-size: 0.775rem;">
                    عرض الكل <i class="bi bi-arrow-left ms-1"></i>
                </a>
            </div>
            <div class="mz-card-body p-0">
                <div class="table-responsive">
                    <table class="table-dense">
                        <thead>
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>العميل</th>
                                <th>المستودع</th>
                                <th class="text-start">المبلغ الإجمالي</th>
                                <th class="text-center">الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                            <tr>
                                <td>
                                    <a href="{{ route('sales.show', $sale) }}" class="code-tag text-decoration-none">
                                        {{ $sale->invoice_number }}
                                    </a>
                                    <div class="text-muted small" style="font-size: 0.725rem;">{{ $sale->invoice_date->format('Y-m-d H:i') }}</div>
                                </td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $sale->customer?->name ?? 'عميل نقدي عابر' }}</span>
                                </td>
                                <td class="text-muted small">{{ $sale->warehouse->name }}</td>
                                <td class="text-start num-cell fw-bold text-dark">
                                    {{ number_format($sale->total_amount, 2) }} <span class="currency-symbol">ر.س</span>
                                </td>
                                <td class="text-center">
                                    <x-status-badge :status="$sale->status" />
                                </td>
                            </tr>
                            @empty
                            <x-table-empty 
                                icon="bi-receipt" 
                                title="لا توجد فواتير مبيعات مسجلة بعد" 
                                description="يمكنك إصدار أول فاتورة مبيعات مباشرة عبر نقطة البيع." 
                                :actionUrl="route('sales.create')" 
                                actionText="إنشاء فاتورة بيع جديدة" 
                                :colspan="5" 
                            />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- أحدث حركات دفتر الأستاذ المخزني -->
    <div class="col-lg-5">
        <div class="mz-card h-100 mb-0">
            <div class="mz-card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-clock-history me-1 text-primary"></i> أحدث حركات دفتر الأستاذ</span>
                @if(auth()->user()->hasRole(['admin', 'manager']))
                <a href="{{ route('inventory.movements') }}" class="btn btn-sm btn-mz-outline py-1 px-2" style="font-size: 0.775rem;">
                    سجل الحركات <i class="bi bi-arrow-left ms-1"></i>
                </a>
                @endif
            </div>
            <div class="mz-card-body p-0">
                <div class="table-responsive">
                    <table class="table-dense">
                        <thead>
                            <tr>
                                <th>نوع الحركة</th>
                                <th>المنتج / المستودع</th>
                                <th class="text-center">الكمية</th>
                                <th class="text-start">الرصيد بعد</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentMovements as $m)
                            <tr>
                                <td>
                                    <span class="badge {{ $m->quantity > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }}" style="font-size: 0.7rem; font-weight: 600;">
                                        {{ match($m->movement_type) {
                                            'purchase' => 'شراء',
                                            'sale' => 'بيع',
                                            'sale_return' => 'مردود بيع',
                                            'transfer_out' => 'تحويل صادر',
                                            'transfer_in' => 'تحويل وارد',
                                            default => $m->movement_type,
                                        } }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark text-truncate" style="max-width: 140px;">{{ $m->product->name }}</div>
                                    <div class="text-muted small" style="font-size: 0.7rem;">{{ $m->warehouse->name }}</div>
                                </td>
                                <td class="text-center num-cell fw-bold {{ $m->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $m->quantity > 0 ? '+' : '' }}{{ number_format($m->quantity, 2) }}
                                </td>
                                <td class="text-start num-cell small text-muted">
                                    {{ number_format($m->after_quantity, 2) }}
                                </td>
                            </tr>
                            @empty
                            <x-table-empty 
                                icon="bi-journal-x" 
                                title="لا توجد حركات مخزنية مسجلة" 
                                description="تُقيد حركات دفتر الأستاذ آلياً عند الشراء والبيع والتحويل." 
                                :colspan="4" 
                            />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if($canViewFinancials)
<!-- ملخص المستودعات التشغيلية وتقييم WAC (للمدراء والمسؤولين) -->
<div class="mz-card mb-4">
    <div class="mz-card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-geo-alt me-1 text-primary"></i> الحالة التشغيلية للمستودعات والفروع</span>
        <a href="{{ route('inventory.balances') }}" class="btn btn-sm btn-mz-outline py-1 px-2" style="font-size: 0.775rem;">
            أرصدة المستودعات <i class="bi bi-arrow-left ms-1"></i>
        </a>
    </div>
    <div class="mz-card-body p-3">
        <div class="row g-3">
            @forelse($warehousesSummary as $wh)
            <div class="col-md-4">
                <div class="p-3 rounded border h-100" style="background-color: var(--mz-surface-subtle); border-color: var(--mz-border) !important;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="fw-bold text-dark fs-6">{{ $wh['name'] }}</span>
                            <span class="code-tag ms-1" style="font-size: 0.7rem;">{{ $wh['code'] ?? 'WH' }}</span>
                        </div>
                        <span class="status-badge status-active">نشط</span>
                    </div>
                    <div class="text-muted small mb-3"><i class="bi bi-geo-alt me-1"></i>{{ $wh['location'] ?? 'المقر الرئيسي' }}</div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="text-muted">الكميات المخزنة:</span>
                        <span class="num-cell fw-bold">{{ number_format($wh['quantity'], 2) }} قطعة</span>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="text-muted">قيمة المخزون (WAC):</span>
                        <span class="num-cell fw-bold text-success">{{ number_format($wh['valuation'], 2) }} ر.س</span>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-3 text-muted">
                لا توجد مستودعات مسجلة في النظام.
            </div>
            @endforelse
        </div>
    </div>
</div>
@endif
@endsection
