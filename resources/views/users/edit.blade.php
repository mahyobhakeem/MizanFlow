@extends('layouts.app')

@section('title', 'تعديل بيانات المستخدم: ' . $user->name)

@section('content')
<x-page-header 
    title="تعديل بيانات المستخدم" 
    :subtitle="$user->name . ' (' . $user->username . ')'"
    :breadcrumbs="[
        ['label' => 'إدارة النظام والرقابة'],
        ['label' => 'المستخدمين والصلاحيات', 'url' => route('users.index')],
        ['label' => 'تعديل: ' . $user->name]
    ]">
    <x-slot:actions>
        <a href="{{ route('users.index') }}" class="btn btn-mz-outline">
            <i class="bi bi-arrow-right"></i>
            <span>العودة للقائمة</span>
        </a>
    </x-slot:actions>
</x-page-header>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="mz-card">
            <div class="mz-card-header">
                <i class="bi bi-person-gear text-primary me-1"></i>
                <span>تعديل حساب: {{ $user->name }} ({{ $user->username }})</span>
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

                <form method="POST" action="{{ route('users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">الاسم الكامل للمستخدم <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $user->name) }}" 
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label for="username" class="form-label">اسم تسجيل الدخول (Username) <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control font-monospace @error('username') is-invalid @enderror" 
                                   id="username" 
                                   name="username" 
                                   value="{{ old('username', $user->username) }}" 
                                   required>
                            <div class="form-text text-muted">حروف وأرقام وشرطات فقط، بدون مسافات.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}" 
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">كلمة المرور الجديدة</label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   placeholder="اترك الحقل فارغاً إذا لم ترغب بتغييرها">
                            <div class="form-text text-muted">8 خانات على الأقل في حال الرغبة بتغييرها.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="role" class="form-label">الدور والصلاحية <span class="text-danger">*</span></label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                <option value="cashier" {{ old('role', $user->role) === 'cashier' ? 'selected' : '' }}>أمين الصندوق (Cashier)</option>
                                <option value="manager" {{ old('role', $user->role) === 'manager' ? 'selected' : '' }}>مدير العمليات والمستودعات (Manager)</option>
                                <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>المدير العام (Administrator)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="default_warehouse_id" class="form-label">المستودع الافتراضي للمستخدم</label>
                            <select class="form-select @error('default_warehouse_id') is-invalid @enderror" id="default_warehouse_id" name="default_warehouse_id">
                                <option value="">— غير مخصص لمستودع محدد —</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ old('default_warehouse_id', $user->default_warehouse_id) == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }} ({{ $warehouse->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-4 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">الحساب نشط ويسمح له بتسجيل الدخول</label>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('users.index') }}" class="btn btn-mz-outline">إلغاء</a>
                        <button type="submit" class="btn btn-mz-primary">
                            <i class="bi bi-check-lg"></i>
                            <span>حفظ التعديلات</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
