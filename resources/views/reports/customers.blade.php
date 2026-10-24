@extends('layouts.app')

@section('title', 'تقرير مديونيات وحسابات العملاء')

@section('content')
<x-page-header 
    title="كشف مديونيات العملاء والذمم المدينة" 
    subtitle="مراقبة الأرصدة المستحقة وسقوف الائتمان ومتابعة نسب استغلال التسهيلات والتحصيل"
    :breadcrumbs="['التقارير التحليلية' => null, 'مديونيات العملاء' => null]"
>
    <x-slot:actions>
        <button onclick="window.print()" class="btn btn-sm btn-mz-outline">
            <i class="bi bi-printer me-1"></i> طباعة التقرير
        </button>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <x-stat-card 
            label="إجمالي الذمم المدينة للعملاء (المستحقات)" 
            :value="number_format($totalReceivables, 2)" 
            unit="ر.س" 
            variant="danger" 
            icon="bi-people"
            footerText="مجموع المديونيات الحالية لجميع العملاء"
        />
    </div>
    <div class="col-md-6">
        <x-stat-card 
            label="عدد العملاء المدينين حالياً" 
            :value="$indebtedCustomersCount" 
            unit="عميل" 
            variant="primary" 
            icon="bi-person-exclamation"
            footerText="عملاء لديهم رصيد متبقي مستحق الدفع"
        />
    </div>
</div>

<div class="mz-card mb-3 no-print">
    <div class="mz-card-body p-2 px-3">
        <form method="GET" action="{{ route('reports.customers') }}">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted border-start-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="بحث بالاسم، الكود، الهاتف...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="filter" class="form-select form-select-sm">
                        <option value="">— جميع العملاء —</option>
                        <option value="with_balance" {{ request('filter') === 'with_balance' ? 'selected' : '' }}>العملاء المدينين فقط (رصيد > 0)</option>
                        <option value="exceeded_limit" {{ request('filter') === 'exceeded_limit' ? 'selected' : '' }}>العملاء المتجاوزين لسقف الائتمان</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-mz-primary flex-fill">تصفية</button>
                    @if(request()->anyFilled(['search', 'filter']))
                        <a href="{{ route('reports.customers') }}" class="btn btn-sm btn-mz-outline" title="إعادة تعيين"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<div class="mz-card mb-4">
    <div class="mz-card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-people me-1 text-primary"></i> كشف حسابات العملاء المالي</span>
        <span class="badge bg-light text-secondary border">{{ $customers->total() }} عميل</span>
    </div>
    <div class="mz-card-body p-0">
        <div class="table-responsive">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th>العميل</th>
                        <th>الكود</th>
                        <th>الهاتف</th>
                        <th class="text-start">سقف الائتمان</th>
                        <th class="text-start">المديونية الحالية</th>
                        <th class="text-center">فواتير البيع</th>
                        <th class="text-center">حالة الائتمان</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $c)
                    @php
                        $balance = (float)$c->balance;
                        $limit = (float)$c->credit_limit;
                        $isExceeded = $limit > 0 && $balance > $limit;
                        $isNear = $limit > 0 && $balance >= ($limit * 0.85);
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $c->name }}</div>
                            @if($c->company_name)
                                <div class="text-muted small">{{ $c->company_name }}</div>
                            @endif
                        </td>
                        <td><span class="code-tag">{{ $c->code }}</span></td>
                        <td class="small num-cell">{{ $c->phone ?? '—' }}</td>
                        <td class="text-start num-cell small text-muted">
                            {{ $limit > 0 ? number_format($limit, 2) . ' ر.س' : 'غير محدد' }}
                        </td>
                        <td class="text-start num-cell fw-bold {{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($balance, 2) }} <span class="currency-symbol">ر.س</span>
                        </td>
                        <td class="text-center num-cell small">{{ $c->sales_count }}</td>
                        <td class="text-center">
                            @if($isExceeded)
                                <span class="status-badge status-danger">متجاوز للسقف</span>
                            @elseif($isNear)
                                <span class="status-badge status-warning">اقترب من السقف</span>
                            @elseif($balance > 0)
                                <span class="status-badge status-in_transit">مدين ضمن السقف</span>
                            @else
                                <span class="status-badge status-completed">خالص الذمة</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <x-table-empty 
                        icon="bi-person-check" 
                        title="لا توجد سجلات عملاء مطابقة لشروط التصفية" 
                        description="جميع حسابات العملاء مسددة أو لا توجد نتائج مطابقة لبحثك." 
                        :colspan="7" 
                    />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
        <div class="p-3 border-top no-print">
            {{ $customers->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection
