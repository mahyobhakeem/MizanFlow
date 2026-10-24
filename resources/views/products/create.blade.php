@extends('layouts.app')

@section('title', 'إضافة منتج جديد')

@section('content')
<x-page-header 
    title="إضافة منتج جديد" 
    subtitle="إدراج صنف جديد في الكتالوج"
    :breadcrumbs="[
        ['label' => 'المخزون والمنتجات'],
        ['label' => 'المنتجات والأصناف', 'url' => route('products.index')],
        ['label' => 'إضافة منتج']
    ]">
    <x-slot:actions>
        <a href="{{ route('products.index') }}" class="btn btn-mz-outline">
            <i class="bi bi-arrow-right"></i>
            <span>العودة للقائمة</span>
        </a>
    </x-slot:actions>
</x-page-header>

<div class="row justify-content-center">
    <div class="col-12 col-lg-9">
        <div class="mz-card">
            <div class="mz-card-header">
                <i class="bi bi-tag text-primary me-1"></i>
                <span>بيانات الصنف والتسعير</span>
            </div>
            <div class="mz-card-body">
                @if ($errors->any())
                    <div class="alert alert-mz alert-mz-danger mb-4">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('products.store') }}">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="sku" class="form-label">رمز الصنف الفريد (SKU) <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control font-monospace @error('sku') is-invalid @enderror" 
                                   id="sku" 
                                   name="sku" 
                                   value="{{ old('sku') }}" 
                                   required 
                                   placeholder="مثال: PROD-1001">
                            <div class="form-text text-muted">رمز فريد إلزامي لا يتكرر لتمييز الصنف.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="barcode" class="form-label">الباركود (Barcode)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-start-0">
                                    <i class="bi bi-upc-scan"></i>
                                </span>
                                <input type="text" 
                                       class="form-control font-monospace @error('barcode') is-invalid @enderror" 
                                       id="barcode" 
                                       name="barcode" 
                                       value="{{ old('barcode') }}" 
                                       placeholder="امسح الباركود أو اكتبه...">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">اسم المنتج <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               required 
                               placeholder="الاسم التجاري للمنتج">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label">التصنيف <span class="text-danger">*</span></label>
                            <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                                <option value="">— اختر التصنيف —</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }} ({{ $category->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="unit_id" class="form-label">وحدة القياس الأساسية <span class="text-danger">*</span></label>
                            <select class="form-select @error('unit_id') is-invalid @enderror" id="unit_id" name="unit_id" required>
                                <option value="">— اختر وحدة القياس —</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->name }} ({{ $unit->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="default_sale_price" class="form-label">سعر البيع الافتراضي (ر.س) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted border-start-0">ر.س</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       class="form-control font-monospace @error('default_sale_price') is-invalid @enderror" 
                                       id="default_sale_price" 
                                       name="default_sale_price" 
                                       value="{{ old('default_sale_price', '0.00') }}" 
                                       required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="min_reorder_level" class="form-label">حد إعادة الطلب (Reorder Level)</label>
                            <input type="number" 
                                   step="1" 
                                   min="0" 
                                   class="form-control font-monospace @error('min_reorder_level') is-invalid @enderror" 
                                   id="min_reorder_level" 
                                   name="min_reorder_level" 
                                   value="{{ old('min_reorder_level', '10') }}">
                            <div class="form-text text-muted">إطلاق تنبيه عند انخفاض الرصيد عن هذا الحد.</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">الوصف والملاحظات</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="المواصفات، المقاسات، أو الملاحظات الفنية...">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">المنتج نشط ومتاح في عمليات البيع والشراء</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('products.index') }}" class="btn btn-mz-outline">إلغاء</a>
                        <button type="submit" class="btn btn-mz-primary">
                            <i class="bi bi-check-lg"></i>
                            <span>حفظ المنتج</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
