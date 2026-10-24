@extends('layouts.app')

@section('title', 'تعديل التصنيف')

@section('content')
<x-page-header 
    title="تعديل التصنيف" 
    :subtitle="$category->name . ' (' . $category->code . ')'"
    :breadcrumbs="[
        ['label' => 'المخزون والمنتجات'],
        ['label' => 'إدارة التصنيفات', 'url' => route('categories.index')],
        ['label' => 'تعديل: ' . $category->name]
    ]">
    <x-slot:actions>
        <a href="{{ route('categories.index') }}" class="btn btn-mz-outline">
            <i class="bi bi-arrow-right"></i>
            <span>العودة للقائمة</span>
        </a>
    </x-slot:actions>
</x-page-header>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="mz-card">
            <div class="mz-card-header">
                <i class="bi bi-pencil-square text-primary me-1"></i>
                <span>تعديل: {{ $category->name }} ({{ $category->code }})</span>
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

                <form method="POST" action="{{ route('categories.update', $category) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="code" class="form-label">رمز التصنيف <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control font-monospace @error('code') is-invalid @enderror" 
                                   id="code" 
                                   name="code" 
                                   value="{{ old('code', $category->code) }}" 
                                   required>
                        </div>
                        <div class="col-md-8">
                            <label for="name" class="form-label">اسم التصنيف <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $category->name) }}" 
                                   required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="parent_id" class="form-label">التصنيف الرئيسي (اختياري)</label>
                        <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                            <option value="">— تصنيف رئيسي مستقل —</option>
                            @foreach($parentCategories as $parent)
                                <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->name }} ({{ $parent->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">وصف التصنيف</label>
                        <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $category->description) }}</textarea>
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">التصنيف نشط ومتاح لاختيار المنتجات</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('categories.index') }}" class="btn btn-mz-outline">إلغاء</a>
                        <button type="submit" class="btn btn-mz-primary">
                            <i class="bi bi-check-lg"></i>
                            <span>تحديث التصنيف</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
