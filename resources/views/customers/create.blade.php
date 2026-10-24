@extends('layouts.app')

@section('title', 'إضافة عميل جديد')

@section('content')
<x-page-header 
    title="إضافة عميل جديد" 
    subtitle="تسجيل عميل جديد في النظام"
    :breadcrumbs="[
        ['label' => 'المبيعات والعملاء'],
        ['label' => 'سجل العملاء', 'url' => route('customers.index')],
        ['label' => 'إضافة عميل']
    ]">
    <x-slot:actions>
        <a href="{{ route('customers.index') }}" class="btn btn-mz-outline">
            <i class="bi bi-arrow-right"></i>
            <span>العودة للقائمة</span>
        </a>
    </x-slot:actions>
</x-page-header>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="mz-card">
            <div class="mz-card-header">
                <i class="bi bi-person-plus text-primary me-1"></i>
                <span>بيانات العميل</span>
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

                <form method="POST" action="{{ route('customers.store') }}">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="code" class="form-label">رمز العميل <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control font-monospace @error('code') is-invalid @enderror" 
                                   id="code" 
                                   name="code" 
                                   value="{{ old('code', 'CUST-' . rand(1000, 9999)) }}" 
                                   required>
                        </div>
                        <div class="col-md-8">
                            <label for="name" class="form-label">اسم العميل <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required 
                                   placeholder="اسم العميل أو جهة التعامل">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="company_name" class="form-label">اسم الشركة / المؤسسة</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="company_name" 
                                   name="company_name" 
                                   value="{{ old('company_name') }}" 
                                   placeholder="اختياري">
                        </div>
                        <div class="col-md-6">
                            <label for="tax_number" class="form-label">الرقم الضريبي</label>
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
                                   placeholder="client@example.com">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="credit_limit" class="form-label">سقف الائتمان المسموح به (ر.س)</label>
                            <input type="number" 
                                   step="100" 
                                   min="0" 
                                   class="form-control font-monospace" 
                                   id="credit_limit" 
                                   name="credit_limit" 
                                   value="{{ old('credit_limit', '0.00') }}">
                            <div class="form-text text-muted">الحد الأقصى للبيع الآجل لهذا العميل (0 = نقدي فقط).</div>
                        </div>
                        <div class="col-md-6">
                            <label for="address" class="form-label">العنوان الوطني / موقع العميل</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="address" 
                                   name="address" 
                                   value="{{ old('address') }}" 
                                   placeholder="المدينة، الحي...">
                        </div>
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">العميل نشط ومتاح لإصدار فواتير البيع</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('customers.index') }}" class="btn btn-mz-outline">إلغاء</a>
                        <button type="submit" class="btn btn-mz-primary">
                            <i class="bi bi-check-lg"></i>
                            <span>حفظ العميل</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
