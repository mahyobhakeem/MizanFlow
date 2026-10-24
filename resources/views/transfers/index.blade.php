@extends('layouts.app')

@section('title', 'تحويلات المخزون بين المستودعات')

@section('content')
<x-page-header 
    title="تحويلات المخزون بين المستودعات" 
    subtitle="إدارة أوامر النقل والتحويل بين الفروع ومتابعة الشحنات وتحديث متوسط التكلفة WAC"
    :breadcrumbs="['تحويلات المخزون' => null]"
>
    <x-slot:actions>
        @can('create', App\Models\StockTransfer::class)
        <a href="{{ route('transfers.create') }}" class="btn btn-mz-primary">
            <i class="bi bi-arrow-left-right"></i>
            <span>تحويل مخزني جديد</span>
        </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="mz-card mb-3">
    <div class="mz-card-body p-2 px-3">
        <form method="GET" action="{{ route('transfers.index') }}">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted border-start-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="بحث برقم التحويل أو اسم المستودع...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">— جميع الحالات —</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>مسودة (Draft)</option>
                        <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>قيد الشحن / في الطريق (In Transit)</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>مكتمل ومستلم (Completed)</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-mz-primary flex-fill">تصفية</button>
                    @if(request()->anyFilled(['search', 'status']))
                        <a href="{{ route('transfers.index') }}" class="btn btn-sm btn-mz-outline" title="إعادة تعيين"><i class="bi bi-x-lg"></i></a>
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
                <th>رقم التحويل</th>
                <th>المستودع المصدر</th>
                <th>المستودع الوجهة</th>
                <th class="text-center">الأصناف</th>
                <th class="text-center">الحالة</th>
                <th>تاريخ الإجراء</th>
                <th class="text-start">العمليات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transfers as $transfer)
            <tr>
                <td>
                    <a href="{{ route('transfers.show', $transfer) }}" class="code-tag text-decoration-none">
                        {{ $transfer->transfer_number }}
                    </a>
                </td>
                <td>
                    <i class="bi bi-box-arrow-up-right text-danger me-1"></i>
                    <span class="fw-semibold text-dark">{{ $transfer->fromWarehouse->name }}</span>
                </td>
                <td>
                    <i class="bi bi-box-arrow-in-down-left text-success me-1"></i>
                    <span class="fw-semibold text-dark">{{ $transfer->toWarehouse->name }}</span>
                </td>
                <td class="text-center num-cell">{{ $transfer->items()->count() }} صنف</td>
                <td class="text-center">
                    <x-status-badge :status="$transfer->status" />
                </td>
                <td class="small text-muted num-cell">{{ $transfer->created_at->format('Y-m-d H:i') }}</td>
                <td class="text-start">
                    <div class="d-inline-flex gap-1">
                        <a href="{{ route('transfers.show', $transfer) }}" class="btn btn-sm btn-mz-outline p-1 px-2" title="عرض التفاصيل">
                            <i class="bi bi-eye"></i>
                        </a>
                        @can('delete', $transfer)
                            <x-delete-button :action="route('transfers.destroy', $transfer)" title="حذف مسودة التحويل" />
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <x-table-empty 
                icon="bi-arrow-left-right" 
                title="لا توجد تحويلات مخزنية مسجلة" 
                description="يمكنك إنشاء أمر تحويل مخزني لنقل البضائع بين الفروع والمستودعات المركزية."
                :actionUrl="route('transfers.create')"
                actionText="إنشاء تحويل مخزني جديد"
                :colspan="7" 
            />
            @endforelse
        </tbody>
    </table>
</div>

@if($transfers->hasPages())
<div class="mt-3">
    {{ $transfers->links('pagination::bootstrap-5') }}
</div>
@endif
@endsection
