@extends('layouts.app')

@section('title', 'إدارة العملاء')

@section('content')
<x-page-header title="إدارة العملاء" subtitle="سجل بيانات العملاء والذمم المالية وسقوف الائتمان">
    <x-slot:actions>
        @can('create', App\Models\Customer::class)
            <a href="{{ route('customers.create') }}" class="btn btn-mz-primary">
                <i class="bi bi-person-plus-fill"></i>
                <span>إضافة عميل جديد</span>
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<x-search-filter :route="route('customers.index')" placeholder="بحث باسم العميل، المنشأة، الهاتف، أو الرمز..." />

<div class="table-dense-container">
    <table class="table-dense">
        <thead>
            <tr>
                <th>رمز العميل</th>
                <th>اسم العميل</th>
                <th>اسم المنشأة / الشركة</th>
                <th>رقم الهاتف</th>
                <th>الرقم الضريبي</th>
                <th class="text-start">سقف الائتمان (ر.س)</th>
                <th class="text-start">الرصيد القائم (ر.س)</th>
                <th>الحالة</th>
                <th class="text-start">العمليات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td class="font-monospace fw-bold">{{ $customer->code }}</td>
                    <td class="fw-semibold text-dark">{{ $customer->name }}</td>
                    <td class="text-muted">{{ $customer->company_name ?: '—' }}</td>
                    <td class="tabular-nums font-monospace">{{ $customer->phone ?: '—' }}</td>
                    <td class="tabular-nums font-monospace text-muted">{{ $customer->tax_number ?: '—' }}</td>
                    <td class="text-start tabular-nums">
                        {{ number_format($customer->credit_limit, 2) }}
                    </td>
                    <td class="text-start tabular-nums fw-bold {{ $customer->balance > 0 ? 'text-danger' : 'text-dark' }}">
                        {{ number_format($customer->balance, 2) }}
                    </td>
                    <td><x-status-badge :active="$customer->is_active" /></td>
                    <td class="text-start">
                        <div class="d-inline-flex gap-1">
                            @can('update', $customer)
                                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="تعديل">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            @endcan
                            @can('delete', $customer)
                                <x-delete-button :action="route('customers.destroy', $customer)" message="هل أنت متأكد من حذف هذا العميل؟" />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                        لا يوجد عملاء مسجلين مطابقين للبحث.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $customers->links('pagination::bootstrap-5') }}
</div>
@endsection
