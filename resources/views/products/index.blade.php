@extends('layouts.app')

@section('title', 'المنتجات والأصناف')

@section('content')
<x-page-header 
    title="كتالوج المنتجات والأصناف" 
    subtitle="إدارة بيانات الأصناف التجارية، أكواد SKU والباركود، وأسعار البيع وحدود إعادة الطلب"
    :breadcrumbs="['دليل المنتجات' => null]"
>
    <x-slot:actions>
        @can('create', App\Models\Product::class)
            <a href="{{ route('products.create') }}" class="btn btn-mz-primary">
                <i class="bi bi-plus-lg"></i>
                <span>إضافة منتج جديد</span>
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="mz-card mb-3">
    <div class="mz-card-body p-2 px-3">
        <form method="GET" action="{{ route('products.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted border-start-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="بحث بالاسم، SKU، أو الباركود..." 
                           value="{{ request('search') }}">
                </div>
            </div>

            <div class="col-6 col-md-3">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">— جميع التصنيفات —</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="unit_id" class="form-select form-select-sm">
                    <option value="">— جميع الوحدات —</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ request('unit_id') == $unit->id ? 'selected' : '' }}>
                            {{ $unit->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">جميع الحالات</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>نشط فقط</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>معطل فقط</option>
                </select>
            </div>

            <div class="col-6 col-md-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-mz-primary">
                    <i class="bi bi-filter"></i>
                    <span>تصفية</span>
                </button>
                @if(request()->hasAny(['search', 'category_id', 'unit_id', 'status']))
                    <a href="{{ route('products.index') }}" class="btn btn-sm btn-mz-outline" title="إلغاء التصفية">
                        <i class="bi bi-x-circle"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="table-dense-container">
    <table class="table-dense">
        <thead>
            <tr>
                <th>رمز الصنف (SKU)</th>
                <th>الباركود</th>
                <th>اسم المنتج</th>
                <th>التصنيف</th>
                <th>الوحدة</th>
                <th class="text-start">سعر البيع الافتراضي</th>
                <th class="text-start">حد إعادة الطلب</th>
                <th>الحالة</th>
                <th class="text-start">العمليات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
                <tr>
                    <td>
                        <span class="code-tag">{{ $product->sku }}</span>
                    </td>
                    <td class="num-cell text-muted">{{ $product->barcode ?: '—' }}</td>
                    <td class="fw-semibold text-dark">{{ $product->name }}</td>
                    <td>
                        <span class="badge bg-light text-secondary border">
                            {{ $product->category->name }}
                        </span>
                    </td>
                    <td>{{ $product->unit->name }}</td>
                    <td class="text-start num-cell fw-bold text-dark">
                        {{ number_format($product->default_sale_price, 2) }} <span class="currency-symbol">ر.س</span>
                    </td>
                    <td class="text-start num-cell text-muted">
                        {{ number_format($product->min_reorder_level, 0) }}
                    </td>
                    <td><x-status-badge :active="$product->is_active" /></td>
                    <td class="text-start">
                        <div class="d-inline-flex gap-1">
                            @can('update', $product)
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-mz-outline p-1 px-2" title="تعديل بيانات المنتج">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            @endcan
                            @can('delete', $product)
                                <x-delete-button :action="route('products.destroy', $product)" message="هل أنت متأكد من حذف هذا المنتج؟" />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <x-table-empty 
                    icon="bi-tags" 
                    title="لا توجد منتجات مطابقة لخيارات البحث" 
                    description="يمكنك إضافة أصناف جديدة وتحديد أسعار البيع وحدود التنبيه للمخزون."
                    :actionUrl="route('products.create')"
                    actionText="إضافة منتج جديد"
                    :colspan="9" 
                />
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $products->links('pagination::bootstrap-5') }}
</div>
@endsection
