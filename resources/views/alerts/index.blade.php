@extends('layouts.app')

@section('title', 'مركز التنبيهات والرقابة التشغيلية')

@section('content')
<x-page-header 
    title="مركز التنبيهات والرقابة التشغيلية" 
    subtitle="مراقبة نقص المخزون وسقوف الائتمان والفواتير المعلقة في الوقت الحقيقي"
    :breadcrumbs="[
        ['label' => 'مركز الرقابة والعمليات'],
        ['label' => 'التنبيهات التشغيلية']
    ]">
    <x-slot:actions>
        <span class="status-badge {{ $summary['critical_count'] > 0 ? 'status-voided' : 'status-completed' }} px-3 py-2 fs-6">
            <span class="status-dot"></span>
            {{ $summary['total_count'] }} تنبيه نشط ({{ $summary['critical_count'] }} حرج)
        </span>
    </x-slot:actions>
</x-page-header>

<!-- تبويبات تصنيف التنبيهات -->
<ul class="nav nav-pills mb-4 gap-2">
    <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'all' ? 'active' : '' }}" href="{{ route('alerts.index', ['type' => 'all']) }}">
            جميع التنبيهات <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $summary['total_count'] }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'low_stock' ? 'active' : '' }}" href="{{ route('alerts.index', ['type' => 'low_stock']) }}">
            <i class="bi bi-box-seam me-1"></i> نقص المخزون
            <span class="badge {{ $summary['low_stock']->count() > 0 ? 'bg-warning text-dark' : 'bg-secondary-subtle text-secondary' }} ms-1">
                {{ $summary['low_stock']->count() }}
            </span>
        </a>
    </li>
    @if($canViewFinancials)
    <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'credit_limit' ? 'active' : '' }}" href="{{ route('alerts.index', ['type' => 'credit_limit']) }}">
            <i class="bi bi-credit-card me-1"></i> سقوف الائتمان
            <span class="badge {{ $summary['credit_alerts']->count() > 0 ? 'bg-danger' : 'bg-secondary-subtle text-secondary' }} ms-1">
                {{ $summary['credit_alerts']->count() }}
            </span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $activeTab === 'unpaid_sales' ? 'active' : '' }}" href="{{ route('alerts.index', ['type' => 'unpaid_sales']) }}">
            <i class="bi bi-receipt me-1"></i> فواتير غير مسددة
            <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $summary['unpaid_sales']->count() }}</span>
        </a>
    </li>
    @endif
</ul>

<!-- 1. تنبيهات نقص المخزون -->
@if($activeTab === 'all' || $activeTab === 'low_stock')
<div class="mz-card mb-4">
    <div class="mz-card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-box-seam text-warning me-1"></i> تنبيهات الأصناف منخفضة ونافدة المخزون</span>
        <span class="badge bg-warning-subtle text-warning-emphasis">{{ $summary['low_stock']->count() }} صنف</span>
    </div>
    <div class="mz-card-body p-0">
        <div class="table-dense-container">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>المستودع</th>
                        <th class="text-start">الرصيد الحالي</th>
                        <th class="text-start">حد إعادة الطلب</th>
                        <th class="text-start">الكمية الناقصة</th>
                        <th>درجة الخطورة</th>
                        <th class="text-start">إجراء سريع</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary['low_stock'] as $item)
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">{{ $item['product_name'] }}</div>
                            <span class="code-tag">SKU: {{ $item['sku'] }}</span>
                        </td>
                        <td>{{ $item['warehouse_name'] }}</td>
                        <td class="text-start num-cell {{ $item['current_quantity'] <= 0 ? 'text-danger' : 'text-warning' }}">
                            {{ number_format($item['current_quantity'], 2) }} {{ $item['unit'] }}
                        </td>
                        <td class="text-start num-cell">{{ number_format($item['min_reorder_level'], 2) }}</td>
                        <td class="text-start num-cell text-danger fw-semibold">{{ number_format($item['deficit'], 2) }}</td>
                        <td>
                            @if($item['severity'] === 'danger')
                                <span class="status-badge status-voided">
                                    <span class="status-dot"></span> نفاد تام (رصيد 0)
                                </span>
                            @else
                                <span class="status-badge status-draft">
                                    <span class="status-dot"></span> تحت حد الطلب
                                </span>
                            @endif
                        </td>
                        <td class="text-start">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('purchases.create') }}" class="btn btn-sm btn-outline-primary py-1 px-2" title="إنشاء أمر شراء وتوريد">
                                    <i class="bi bi-truck me-1"></i> شراء
                                </a>
                                <a href="{{ route('transfers.create') }}" class="btn btn-sm btn-outline-secondary py-1 px-2" title="تحويل من مستودع آخر">
                                    <i class="bi bi-arrow-left-right me-1"></i> تحويل
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-0 border-0">
                            <x-table-empty 
                                icon="bi-check-circle-fill text-success" 
                                message="جميع أصناف المستودعات متوفرة فوق الحد الأدنى لإعادة الطلب ولا يوجد عجز مخزني." 
                            />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- 2. تنبيهات سقوف الائتمان للعملاء -->
@if($canViewFinancials && ($activeTab === 'all' || $activeTab === 'credit_limit'))
<div class="mz-card mb-4">
    <div class="mz-card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-credit-card text-danger me-1"></i> تنبيهات سقوف الائتمان ومديونيات العملاء</span>
        <span class="badge bg-danger-subtle text-danger">{{ $summary['credit_alerts']->count() }} عميل</span>
    </div>
    <div class="mz-card-body p-0">
        <div class="table-dense-container">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th>العميل</th>
                        <th>الكود</th>
                        <th>الهاتف</th>
                        <th class="text-start">سقف الائتمان</th>
                        <th class="text-start">المديونية الحالية</th>
                        <th style="width: 140px;">نسبة الاستغلال</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary['credit_alerts'] as $c)
                    <tr>
                        <td class="fw-semibold text-dark">{{ $c['customer_name'] }}</td>
                        <td><span class="code-tag">{{ $c['code'] }}</span></td>
                        <td class="num-cell">{{ $c['phone'] ?? '—' }}</td>
                        <td class="text-start num-cell">{{ number_format($c['credit_limit'], 2) }} ر.س</td>
                        <td class="text-start num-cell fw-bold text-danger">{{ number_format($c['balance'], 2) }} ر.س</td>
                        <td>
                            <div class="progress" style="height: 10px; border-radius: 4px;">
                                <div class="progress-bar {{ $c['severity'] === 'danger' ? 'bg-danger' : 'bg-warning' }}" 
                                     role="progressbar" 
                                     style="width: {{ min(100, $c['utilization_pct']) }}%;" 
                                     aria-valuenow="{{ $c['utilization_pct'] }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                            <span class="small num-cell text-muted mt-1 d-block">{{ $c['utilization_pct'] }}%</span>
                        </td>
                        <td>
                            @if($c['severity'] === 'danger')
                                <span class="status-badge status-voided">
                                    <span class="status-dot"></span> تجاوز السقف المسموح
                                </span>
                            @else
                                <span class="status-badge status-draft">
                                    <span class="status-dot"></span> استهلك أكثر من 85%
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-0 border-0">
                            <x-table-empty 
                                icon="bi-shield-check text-success" 
                                message="جميع العملاء ملتزمون بسقوف الائتمان المعتمدة ولا توجد أي تجاوزات ائتمانية." 
                            />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- 3. فواتير البيع الآجلة غير المسددة -->
@if($canViewFinancials && ($activeTab === 'all' || $activeTab === 'unpaid_sales'))
<div class="mz-card mb-4">
    <div class="mz-card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-receipt text-primary me-1"></i> فواتير المبيعات الآجلة المعلقة قيد التحصيل</span>
        <span class="badge bg-secondary-subtle text-secondary">{{ $summary['unpaid_sales']->count() }} فاتورة</span>
    </div>
    <div class="mz-card-body p-0">
        <div class="table-dense-container">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>العميل</th>
                        <th>المستودع</th>
                        <th>تاريخ الفاتورة</th>
                        <th class="text-start">الإجمالي</th>
                        <th class="text-start">المتبقي المطلوب</th>
                        <th>المدة المنقضية</th>
                        <th class="text-start">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary['unpaid_sales'] as $inv)
                    <tr>
                        <td>
                            <a href="{{ route('sales.show', $inv['sale_id']) }}" class="code-tag text-primary text-decoration-none fw-bold">
                                {{ $inv['invoice_number'] }}
                            </a>
                        </td>
                        <td class="fw-semibold text-dark">{{ $inv['customer_name'] }}</td>
                        <td>{{ $inv['warehouse_name'] }}</td>
                        <td class="num-cell">{{ $inv['invoice_date'] }}</td>
                        <td class="text-start num-cell">{{ number_format($inv['total_amount'], 2) }} ر.س</td>
                        <td class="text-start num-cell fw-bold text-danger">{{ number_format($inv['remaining_amount'], 2) }} ر.س</td>
                        <td>
                            <span class="status-badge {{ $inv['days_elapsed'] > 14 ? 'status-voided' : 'status-in-transit' }}">
                                <span class="status-dot"></span> منذ {{ $inv['days_elapsed'] }} يوم
                            </span>
                        </td>
                        <td class="text-start">
                            <a href="{{ route('sales.show', $inv['sale_id']) }}" class="btn btn-sm btn-outline-secondary py-1 px-2">
                                <i class="bi bi-eye me-1"></i> عرض الفاتورة
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-0 border-0">
                            <x-table-empty 
                                icon="bi-check-all text-success" 
                                message="لا توجد فواتير مبيعات آجلة معلقة قيد التحصيل." 
                            />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
