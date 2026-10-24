@extends('layouts.app')

@section('title', 'تعديل وحدة القياس')

@section('content')
<x-page-header 
    title="تعديل وحدة القياس" 
    :subtitle="$unit->name . ' (' . $unit->code . ')'"
    :breadcrumbs="[
        ['label' => 'المخزون والمنتجات'],
        ['label' => 'وحدات القياس', 'url' => route('units.index')],
        ['label' => 'تعديل: ' . $unit->name]
    ]">
    <x-slot:actions>
        <a href="{{ route('units.index') }}" class="btn btn-mz-outline">
            <i class="bi bi-arrow-right"></i>
            <span>العودة للقائمة</span>
        </a>
    </x-slot:actions>
</x-page-header>

<div class="row justify-content-center">
    <div class="col-12 col-lg-6">
        <div class="mz-card">
            <div class="mz-card-header">
                <i class="bi bi-pencil-square text-primary me-1"></i>
                <span>تعديل: {{ $unit->name }} ({{ $unit->code }})</span>
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

                <form method="POST" action="{{ route('units.update', $unit) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="code" class="form-label">رمز الوحدة <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control font-monospace @error('code') is-invalid @enderror" 
                               id="code" 
                               name="code" 
                               value="{{ old('code', $unit->code) }}" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">اسم الوحدة بالعربية <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $unit->name) }}" 
                               required>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="allow_decimals" name="allow_decimals" value="1" {{ old('allow_decimals', $unit->allow_decimals) ? 'checked' : '' }}>
                        <label class="form-check-label" for="allow_decimals">تقبل الكميات المجزأة والكسور (مثل 1.250 كجم)</label>
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $unit->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">الوحدة نشطة ومتاحة للاختيار</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('units.index') }}" class="btn btn-mz-outline">إلغاء</a>
                        <button type="submit" class="btn btn-mz-primary">
                            <i class="bi bi-check-lg"></i>
                            <span>تحديث الوحدة</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
