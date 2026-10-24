@extends('layouts.app')

@section('title', 'إدارة التصنيفات')

@section('content')
<x-page-header title="إدارة التصنيفات" subtitle="تصنيف المنتجات والأصناف التجارية في مجموعات">
    <x-slot:actions>
        @can('create', App\Models\Category::class)
            <a href="{{ route('categories.create') }}" class="btn btn-mz-primary">
                <i class="bi bi-plus-lg"></i>
                <span>إضافة تصنيف جديد</span>
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<x-search-filter :route="route('categories.index')" placeholder="بحث باسم التصنيف، الرمز، أو الوصف..." />

<div class="table-dense-container">
    <table class="table-dense">
        <thead>
            <tr>
                <th>الرمز</th>
                <th>اسم التصنيف</th>
                <th>التصنيف الرئيسي</th>
                <th>الوصف</th>
                <th>عدد المنتجات</th>
                <th>الحالة</th>
                <th class="text-start">العمليات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $category)
                <tr>
                    <td class="font-monospace fw-bold">{{ $category->code }}</td>
                    <td class="fw-semibold text-dark">{{ $category->name }}</td>
                    <td>
                        @if($category->parent)
                            <span class="badge bg-light text-secondary border">
                                <i class="bi bi-folder-fill me-1"></i>{{ $category->parent->name }}
                            </span>
                        @else
                            <span class="text-muted small">تصنيف رئيسي</span>
                        @endif
                    </td>
                    <td class="text-muted small">{{ Str::limit($category->description, 50) ?: '—' }}</td>
                    <td>
                        <span class="badge bg-light text-dark border tabular-nums">
                            {{ $category->products_count }} صنف
                        </span>
                    </td>
                    <td><x-status-badge :active="$category->is_active" /></td>
                    <td class="text-start">
                        <div class="d-inline-flex gap-1">
                            @can('update', $category)
                                <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="تعديل">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            @endcan
                            @can('delete', $category)
                                <x-delete-button :action="route('categories.destroy', $category)" message="هل أنت متأكد من حذف هذا التصنيف؟" />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                        لا توجد تصنيفات مطابقة للبحث.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $categories->links('pagination::bootstrap-5') }}
</div>
@endsection
