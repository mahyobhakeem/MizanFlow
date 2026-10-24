@extends('layouts.guest')

@section('title', 'خطأ في النظام - 500')

@section('content')
<div class="mz-card text-center p-4">
    <div class="mz-card-body">
        <div class="display-3 text-danger mb-3">
            <i class="bi bi-exclamation-octagon"></i>
        </div>
        <h2 class="h4 fw-bold text-dark mb-2">500 — خطأ غير متوقع في المعالجة</h2>
        <p class="text-muted mb-4">
            حدث خطأ غير متوقع أثناء معالجة العملية. تم تسجيل تفاصيل الخطأ في سجل التدقيق والمراقبة للتحقق والمعالجة الفورية.
        </p>
        <div class="d-grid gap-2 d-sm-flex justify-content-center">
            <a href="{{ route('dashboard') }}" class="btn btn-mz-primary">
                <i class="bi bi-speedometer2 me-1"></i> العودة للوحة التحكم
            </a>
            <a href="javascript:history.back()" class="btn btn-mz-outline">
                <i class="bi bi-arrow-right me-1"></i> العودة للخلف
            </a>
        </div>
    </div>
</div>
@endsection
