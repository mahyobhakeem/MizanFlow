@extends('layouts.app')

@section('title', 'إضافة مورد جديد')

@section('content')
<x-page-header 
    title="إضافة مورد جديد" 
    subtitle="تسجيل مورد معتمد للمشتريات"
    :breadcrumbs="[
        ['label' => 'المشتريات والموردين'],
        ['label' => 'سجل الموردين', 'url' => route('suppliers.index')],
        ['label' => 'إضافة مورد']
    ]">
    <x-slot:actions>
        <a href="{{ route('suppliers.index') }}" class="btn btn-mz-outline">
            <i class="bi bi-arrow-right"></i>
            <span>العودة للقائمة</span>
        </a>
    </x-slot:actions>
</x-page-header>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="mz-card">
            <div class="mz-card-header">
                <i class="bi bi-truck text-primary me-1"></i>
                <span>بيانات المورد</span>
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

                <form method="POST" action="{{ route('suppliers.store') }}">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="code" class="form-label">رمز المورد <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control font-monospace @error('code') is-invalid @enderror" 
                                   id="code" 
                                   name="code" 
                                   value="{{ old('code', 'SUPP-' . rand(1000, 9999)) }}" 
                                   required>
                        </div>
                        <div class="col-md-8">
                            <label for="name" class="form-label">اسم المورد / المسؤول <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required 
                                   placeholder="اسم المورد أو مندوب الشركة">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="company_name" class="form-label">اسم الشركة / المؤسسة الموردة</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="company_name" 
                                   name="company_name" 
                                   value="{{ old('company_name') }}" 
                                   placeholder="مثال: شركة التوزيع المتحدة">
                        </div>
                        <div class="col-md-6">
                            <label for="tax_number" class="form-label">الرقم الضريبي للمورد</label>
                            <input type="text" 
                                   class="form-control font-monospace" 
                                   id="tax_number" 
                                   name="tax_number" 
                                   value="{{ old('tax_number') }}" 
                                   placeholder="3XXXXXXXXXXXXXX">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">رقم الهاتف</label>
                            <input type="text" 
                                   class="form-control font-monospace" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone') }}" 
                                   placeholder="05XXXXXXXX">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">البريد الإلكتروني</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   placeholder="supplier@company.com">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">العنوان والمستودع الرئيسي للمورد</label>
                        <input type="text" 
                               class="form-control" 
                               id="address" 
                               name="address" 
                               value="{{ old('address') }}" 
                               placeholder="المدينة، المنطقة الصناعية...">
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">المورد نشط ومتاح لإنشاء فواتير الشراء</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('suppliers.index') }}" class="btn btn-mz-outline">إلغاء</a>
                        <button type="submit" class="btn btn-mz-primary">
                            <i class="bi bi-check-lg"></i>
                            <span>حفظ المورد</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
