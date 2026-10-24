@extends('layouts.app')

@section('title', 'تعديل بيانات العميل')

@section('content')
<x-page-header 
    title="تعديل بيانات العميل" 
    :subtitle="$customer->name . ' (' . $customer->code . ')'"
    :breadcrumbs="[
        ['label' => 'المبيعات والعملاء'],
        ['label' => 'سجل العملاء', 'url' => route('customers.index')],
        ['label' => 'تعديل: ' . $customer->name]
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
                <i class="bi bi-pencil-square text-primary me-1"></i>
                <span>تعديل: {{ $customer->name }} ({{ $customer->code }})</span>
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

                <form method="POST" action="{{ route('customers.update', $customer) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="code" class="form-label">رمز العميل <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control font-monospace @error('code') is-invalid @enderror" 
                                   id="code" 
                                   name="code" 
                                   value="{{ old('code', $customer->code) }}" 
                                   required>
                        </div>
                        <div class="col-md-8">
                            <label for="name" class="form-label">اسم العميل <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $customer->name) }}" 
                                   required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="company_name" class="form-label">اسم الشركة / المؤسسة</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="company_name" 
                                   name="company_name" 
                                   value="{{ old('company_name', $customer->company_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="tax_number" class="form-label">الرقم الضريبي</label>
                            <input type="text" 
                                   class="form-control font-monospace" 
                                   id="tax_number" 
                                   name="tax_number" 
                                   value="{{ old('tax_number', $customer->tax_number) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label">رقم الهاتف</label>
                            <input type="text" 
                                   class="form-control font-monospace" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone', $customer->phone) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">البريد الإلكتروني</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $customer->email) }}">
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
                                   value="{{ old('credit_limit', $customer->credit_limit) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="address" class="form-label">العنوان الوطني / موقع العميل</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="address" 
                                   name="address" 
                                   value="{{ old('address', $customer->address) }}">
                        </div>
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $customer->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">العميل نشط ومتاح لإصدار فواتير البيع</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('customers.index') }}" class="btn btn-mz-outline">إلغاء</a>
                        <button type="submit" class="btn btn-mz-primary">
                            <i class="bi bi-check-lg"></i>
                            <span>تحديث بيانات العميل</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
