@extends('layouts.app')

@section('title', 'فواتير المشتريات')

@section('content')
<x-page-header 
    title="فواتير المشتريات والتوريد" 
    subtitle="إدارة أوامر الشراء، اعتماد الواردات المخزنية، واحتساب تكاليف المتوسط المرجح (WAC)"
    :breadcrumbs="['فواتير المشتريات' => null]"
>
    <x-slot:actions>
        @can('create', App\Models\Purchase::class)
        <a href="{{ route('purchases.create') }}" class="btn btn-mz-primary">
            <i class="bi bi-cart-plus"></i>
            <span>فاتورة شراء جديدة</span>
        </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="mz-card mb-3">
    <div class="mz-card-body p-2 px-3">
        <form method="GET" action="{{ route('purchases.index') }}">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted border-start-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="رقم الفاتورة، مرجع المورد، اسم المورد...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">— جميع الحالات —</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>مسودة (Draft)</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>معتمدة ومودعة (Approved)</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغاة (Cancelled)</option>
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
                    @if(request()->anyFilled(['search', 'status', 'warehouse_id']))
                        <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-mz-outline" title="إعادة تعيين"><i class="bi bi-x-lg"></i></a>
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
                <th>المورد</th>
                <th>المستودع المستلم</th>
                <th>تاريخ الفاتورة</th>
                <th class="text-start">الإجمالي الصافي</th>
                <th class="text-start">المبلغ المسدد</th>
                <th class="text-center">الحالة</th>
                <th class="text-start">العمليات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($purchases as $purchase)
            <tr>
                <td>
                    <a href="{{ route('purchases.show', $purchase) }}" class="code-tag text-decoration-none">
                        {{ $purchase->invoice_number }}
                    </a>
                    @if($purchase->supplier_invoice_ref)
                        <div class="text-muted small" style="font-size: 0.725rem;">مرجع: {{ $purchase->supplier_invoice_ref }}</div>
                    @endif
                </td>
                <td class="fw-semibold text-dark">{{ $purchase->supplier->name }}</td>
                <td class="text-muted small">{{ $purchase->warehouse->name }}</td>
                <td class="num-cell small">{{ $purchase->issue_date->format('Y-m-d') }}</td>
                <td class="text-start num-cell fw-bold text-dark">
                    {{ number_format($purchase->total_amount, 2) }} <span class="currency-symbol">ر.س</span>
                </td>
                <td class="text-start num-cell text-success">
                    {{ number_format($purchase->paid_amount, 2) }} <span class="currency-symbol">ر.س</span>
                </td>
                <td class="text-center">
                    <x-status-badge :status="$purchase->status" />
                </td>
                <td class="text-start">
                    <div class="d-inline-flex gap-1">
                        <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-sm btn-mz-outline p-1 px-2" title="عرض تفاصيل الفاتورة">
                            <i class="bi bi-eye"></i>
                        </a>
                        @can('update', $purchase)
                            <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-sm btn-mz-outline p-1 px-2 text-primary" title="تعديل">
                                <i class="bi bi-pencil"></i>
                            </a>
                        @endcan
                        @can('delete', $purchase)
                            <x-delete-button :action="route('purchases.destroy', $purchase)" title="حذف مسودة الفاتورة" />
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <x-table-empty 
                icon="bi-truck" 
                title="لا توجد فواتير مشتريات مطابقة للبحث" 
                description="قم بإنشاء فاتورة شراء جديدة لتوريد المنتجات وإيداعها في المستودع واحتساب متوسط التكلفة WAC."
                :actionUrl="route('purchases.create')"
                actionText="إنشاء فاتورة شراء وتوريد"
                :colspan="8" 
            />
            @endforelse
        </tbody>
    </table>
</div>

@if($purchases->hasPages())
<div class="mt-3">
    {{ $purchases->links('pagination::bootstrap-5') }}
</div>
@endif
@endsection
