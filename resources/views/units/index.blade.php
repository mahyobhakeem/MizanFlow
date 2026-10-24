@extends('layouts.app')

@section('title', 'وحدات القياس')

@section('content')
<x-page-header title="وحدات القياس" subtitle="وحدات قياس المنتجات (حبة، كرتون، كجم، لتر)">
    <x-slot:actions>
        @can('create', App\Models\Unit::class)
            <a href="{{ route('units.create') }}" class="btn btn-mz-primary">
                <i class="bi bi-plus-lg"></i>
                <span>إضافة وحدة جديدة</span>
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<x-search-filter :route="route('units.index')" placeholder="بحث باسم الوحدة أو الرمز..." />

<div class="table-dense-container">
    <table class="table-dense">
        <thead>
            <tr>
                <th>الرمز</th>
                <th>اسم الوحدة</th>
                <th>تقبل الكسور والعلامات العشرية</th>
                <th>عدد المنتجات المرتبطة</th>
                <th>الحالة</th>
                <th class="text-start">العمليات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($units as $unit)
                <tr>
                    <td class="font-monospace fw-bold">{{ $unit->code }}</td>
                    <td class="fw-semibold text-dark">{{ $unit->name }}</td>
                    <td>
                        @if($unit->allow_decimals)
                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">نعم (تدعم الكسور)</span>
                        @else
                            <span class="badge bg-light text-secondary border">أعداد صحيحة فقط</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border tabular-nums">
                            {{ $unit->products_count }} منتج
                        </span>
                    </td>
                    <td><x-status-badge :active="$unit->is_active" /></td>
                    <td class="text-start">
                        <div class="d-inline-flex gap-1">
                            @can('update', $unit)
                                <a href="{{ route('units.edit', $unit) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="تعديل">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            @endcan
                            @can('delete', $unit)
                                <x-delete-button :action="route('units.destroy', $unit)" message="هل أنت متأكد من حذف هذه الوحدة؟" />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                        لا توجد وحدات قياس مسجلة.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $units->links('pagination::bootstrap-5') }}
</div>
@endsection
