@extends('layouts.guest')

@section('title', 'الصفحة غير موجودة - 404')

@section('content')
<div class="mz-card text-center p-4">
    <div class="mz-card-body">
        <div class="display-3 text-secondary mb-3">
            <i class="bi bi-question-diamond"></i>
        </div>
        <h2 class="h4 fw-bold text-dark mb-2">404 — الصفحة غير موجودة</h2>
        <p class="text-muted mb-4">
            عذراً، الرابط المطلوب غير متوفر أو تم نقله أو حذفه من النظام.
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
