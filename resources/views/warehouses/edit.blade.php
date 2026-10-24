@extends('layouts.app')

@section('title', 'تعديل بيانات المستودع')

@section('content')
<x-page-header 
    title="تعديل بيانات المستودع" 
    :subtitle="$warehouse->name . ' (' . $warehouse->code . ')'"
    :breadcrumbs="[
        ['label' => 'المخزون والتحويلات'],
        ['label' => 'إدارة المستودعات', 'url' => route('warehouses.index')],
        ['label' => 'تعديل: ' . $warehouse->name]
    ]">
    <x-slot:actions>
        <a href="{{ route('warehouses.index') }}" class="btn btn-mz-outline">
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
                <span>تعديل: {{ $warehouse->name }} ({{ $warehouse->code }})</span>
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

                <form method="POST" action="{{ route('warehouses.update', $warehouse) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="code" class="form-label">رمز المستودع <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control font-monospace @error('code') is-invalid @enderror" 
                                   id="code" 
                                   name="code" 
                                   value="{{ old('code', $warehouse->code) }}" 
                                   required>
                        </div>
                        <div class="col-md-8">
                            <label for="name" class="form-label">اسم المستودع <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $warehouse->name) }}" 
                                   required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="manager_name" class="form-label">اسم المسؤول / أمين المستودع</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="manager_name" 
                                   name="manager_name" 
                                   value="{{ old('manager_name', $warehouse->manager_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">رقم الهاتف للتواصل</label>
                            <input type="text" 
                                   class="form-control font-monospace" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone', $warehouse->phone) }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">العنوان والموقع الجغرافي</label>
                        <input type="text" 
                               class="form-control" 
                               id="location" 
                               name="location" 
                               value="{{ old('location', $warehouse->location) }}">
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $warehouse->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">المستودع نشط وجاهز للعمليات</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('warehouses.index') }}" class="btn btn-mz-outline">إلغاء</a>
                        <button type="submit" class="btn btn-mz-primary">
                            <i class="bi bi-check-lg"></i>
                            <span>تحديث المستودع</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
