@extends('layouts.app')

@section('title', 'إدارة الموردين')

@section('content')
<x-page-header title="إدارة الموردين" subtitle="سجل بيانات الموردين والمستحقات والذمم الدائنة">
    <x-slot:actions>
        @can('create', App\Models\Supplier::class)
            <a href="{{ route('suppliers.create') }}" class="btn btn-mz-primary">
                <i class="bi bi-truck-front-fill"></i>
                <span>إضافة مورد جديد</span>
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<x-search-filter :route="route('suppliers.index')" placeholder="بحث باسم المورد، المنشأة، الهاتف، أو الرمز..." />

<div class="table-dense-container">
    <table class="table-dense">
        <thead>
            <tr>
                <th>رمز المورد</th>
                <th>اسم المورد</th>
                <th>اسم الشركة / المؤسسة</th>
                <th>رقم الهاتف</th>
                <th>الرقم الضريبي</th>
                <th class="text-start">المستحقات القائمة (ر.س)</th>
                <th>الحالة</th>
                <th class="text-start">العمليات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suppliers as $supplier)
                <tr>
                    <td class="font-monospace fw-bold">{{ $supplier->code }}</td>
                    <td class="fw-semibold text-dark">{{ $supplier->name }}</td>
                    <td class="text-muted">{{ $supplier->company_name ?: '—' }}</td>
                    <td class="tabular-nums font-monospace">{{ $supplier->phone ?: '—' }}</td>
                    <td class="tabular-nums font-monospace text-muted">{{ $supplier->tax_number ?: '—' }}</td>
                    <td class="text-start tabular-nums fw-bold {{ $supplier->balance > 0 ? 'text-danger' : 'text-dark' }}">
                        {{ number_format($supplier->balance, 2) }}
                    </td>
                    <td><x-status-badge :active="$supplier->is_active" /></td>
                    <td class="text-start">
                        <div class="d-inline-flex gap-1">
                            @can('update', $supplier)
                                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="تعديل">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            @endcan
                            @can('delete', $supplier)
                                <x-delete-button :action="route('suppliers.destroy', $supplier)" message="هل أنت متأكد من حذف هذا المورد؟" />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                        لا يوجد موردين مسجلين مطابقين للبحث.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $suppliers->links('pagination::bootstrap-5') }}
</div>
@endsection
