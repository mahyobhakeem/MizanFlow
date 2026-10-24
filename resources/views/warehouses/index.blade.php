@extends('layouts.app')

@section('title', 'إدارة المستودعات')

@section('content')
<x-page-header title="إدارة المستودعات" subtitle="قائمة المستودعات ونقاط التخزين التابعة للشركة">
    <x-slot:actions>
        @can('create', App\Models\Warehouse::class)
            <a href="{{ route('warehouses.create') }}" class="btn btn-mz-primary">
                <i class="bi bi-plus-lg"></i>
                <span>إضافة مستودع جديد</span>
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<x-search-filter :route="route('warehouses.index')" placeholder="بحث باسم المستودع، الرمز، أو المسؤول..." />

<div class="table-dense-container">
    <table class="table-dense">
        <thead>
            <tr>
                <th>الرمز</th>
                <th>اسم المستودع</th>
                <th>الموقع الجغرافي</th>
                <th>المسؤول</th>
                <th>رقم الهاتف</th>
                <th>المستخدمين</th>
                <th>الحالة</th>
                <th class="text-start">العمليات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($warehouses as $warehouse)
                <tr>
                    <td class="font-monospace fw-bold">{{ $warehouse->code }}</td>
                    <td class="fw-semibold text-dark">{{ $warehouse->name }}</td>
                    <td class="text-muted">{{ $warehouse->location ?: '—' }}</td>
                    <td>{{ $warehouse->manager_name ?: '—' }}</td>
                    <td class="tabular-nums font-monospace">{{ $warehouse->phone ?: '—' }}</td>
                    <td>
                        <span class="badge bg-light text-dark border tabular-nums">
                            {{ $warehouse->users_count }} مستخدم
                        </span>
                    </td>
                    <td><x-status-badge :active="$warehouse->is_active" /></td>
                    <td class="text-start">
                        <div class="d-inline-flex gap-1">
                            @can('update', $warehouse)
                                <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="تعديل">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            @endcan
                            @can('delete', $warehouse)
                                <x-delete-button :action="route('warehouses.destroy', $warehouse)" message="هل أنت متأكد من حذف هذا المستودع؟" />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                        لا توجد مستودعات مطابقة للبحث.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $warehouses->links('pagination::bootstrap-5') }}
</div>
@endsection
