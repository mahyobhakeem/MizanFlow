@extends('layouts.app')

@section('title', 'فواتير المبيعات')

@section('content')
<x-page-header 
    title="فواتير المبيعات ونقاط البيع" 
    subtitle="إدارة دورة المبيعات والتحصيل وإصدار الفواتير المعتمدة ومتابعة المستحقات"
    :breadcrumbs="['فواتير المبيعات' => null]"
>
    <x-slot:actions>
        @can('create', App\Models\Sale::class)
        <a href="{{ route('sales.create') }}" class="btn btn-mz-primary">
            <i class="bi bi-cart-plus"></i>
            <span>نقطة بيع / فاتورة جديدة (POS)</span>
        </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="mz-card mb-3">
    <div class="mz-card-body p-2 px-3">
        <form method="GET" action="{{ route('sales.index') }}">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted border-start-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="رقم الفاتورة، العميل، الهاتف...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">— جميع الحالات —</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>مسودة (Draft)</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>معتمدة ومكتملة (Completed)</option>
                        <option value="voided" {{ request('status') === 'voided' ? 'selected' : '' }}>ملغاة (عكسية)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="payment_type" class="form-select form-select-sm">
                        <option value="">— طريقة الدفع —</option>
                        <option value="cash" {{ request('payment_type') === 'cash' ? 'selected' : '' }}>نقدي (Cash)</option>
                        <option value="card" {{ request('payment_type') === 'card' ? 'selected' : '' }}>بطاقة / شبكة (Card)</option>
                        <option value="credit" {{ request('payment_type') === 'credit' ? 'selected' : '' }}>آجل (Credit)</option>
                        <option value="split" {{ request('payment_type') === 'split' ? 'selected' : '' }}>دفع مجزأ (Split)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">— جميع المستودعات —</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-mz-primary flex-fill">تصفية</button>
                    @if(request()->anyFilled(['search', 'status', 'warehouse_id', 'payment_type']))
                        <a href="{{ route('sales.index') }}" class="btn btn-sm btn-mz-outline" title="إعادة تعيين"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<div class="table-dense-container">
    <table class="table-dense">
        <thead>
            <tr>
                <th>رقم الفاتورة</th>
                <th>العميل</th>
                <th>المستودع المصدر</th>
                <th>التاريخ والوقت</th>
                <th>طريقة الدفع</th>
                <th class="text-start">الإجمالي الصافي</th>
                <th class="text-start">المدفوع / المتبقي</th>
                <th class="text-center">الحالة</th>
                <th class="text-start">العمليات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
            <tr>
                <td>
                    <a href="{{ route('sales.show', $sale) }}" class="code-tag text-decoration-none">
                        {{ $sale->invoice_number }}
                    </a>
                    <div class="text-muted small" style="font-size: 0.725rem;">الكاشير: {{ $sale->user->name }}</div>
                </td>
                <td>
                    @if($sale->customer)
                        <span class="fw-semibold text-dark">{{ $sale->customer->name }}</span>
                        @if($sale->customer->phone)
                            <div class="text-muted small num-cell" style="font-size: 0.725rem;">{{ $sale->customer->phone }}</div>
                        @endif
                    @else
                        <span class="badge bg-light text-secondary border">عميل نقدي عابر</span>
                    @endif
                </td>
                <td class="text-muted small">{{ $sale->warehouse->name }}</td>
                <td>
                    <div class="num-cell small">{{ $sale->invoice_date->format('Y-m-d') }}</div>
                    <div class="text-muted num-cell" style="font-size: 0.7rem;">{{ $sale->invoice_date->format('H:i') }}</div>
                </td>
                <td>
                    <span class="badge bg-light text-dark border">
                        {{ $sale->paymentTypeLabel() }}
                    </span>
                </td>
                <td class="text-start num-cell fw-bold text-dark">
                    {{ number_format($sale->total_amount, 2) }} <span class="currency-symbol">ر.س</span>
                </td>
                <td class="text-start num-cell">
                    <span class="{{ $sale->remainingAmount() > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                        {{ number_format($sale->paid_amount, 2) }} <span class="currency-symbol">ر.س</span>
                    </span>
                    @if($sale->remainingAmount() > 0)
                        <div class="text-danger small" style="font-size: 0.7rem;">آجل: {{ number_format($sale->remainingAmount(), 2) }}</div>
                    @endif
                </td>
                <td class="text-center">
                    <x-status-badge :status="$sale->status" />
                </td>
                <td class="text-start">
                    <div class="d-inline-flex gap-1">
                        <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-mz-outline p-1 px-2" title="عرض تفاصيل الفاتورة">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('sales.print', $sale) }}" target="_blank" class="btn btn-sm btn-mz-outline p-1 px-2" title="طباعة الفاتورة الضريبية">
                            <i class="bi bi-printer"></i>
                        </a>
                        @can('update', $sale)
                        <a href="{{ route('sales.edit', $sale) }}" class="btn btn-sm btn-mz-outline p-1 px-2 text-primary" title="تعديل المسودة">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <x-table-empty 
                icon="bi-receipt" 
                title="لا توجد فواتير مبيعات مسجلة مطابقة للبحث" 
                description="ابدأ بإصدار فواتير المبيعات الفورية أو حفظ عروض الأسعار كمسودات." 
                :actionUrl="route('sales.create')" 
                actionText="إصدار فاتورة بيع جديدة" 
                :colspan="9" 
            />
            @endforelse
        </tbody>
    </table>
</div>

@if($sales->hasPages())
<div class="mt-3">
    {{ $sales->links('pagination::bootstrap-5') }}
</div>
@endif
@endsection
