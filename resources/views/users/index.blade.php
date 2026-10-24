@extends('layouts.app')

@section('title', 'المستخدمين والصلاحيات')

@section('content')
<x-page-header title="المستخدمين والصلاحيات" subtitle="إدارة حسابات النظام وتعيين الأدوار والمستودعات">
    <x-slot:actions>
        @can('create', App\Models\User::class)
            <a href="{{ route('users.create') }}" class="btn btn-mz-primary">
                <i class="bi bi-person-plus-fill"></i>
                <span>إضافة مستخدم جديد</span>
            </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<div class="mz-card mb-3">
    <div class="mz-card-body p-3">
        <form method="GET" action="{{ route('users.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted border-start-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="بحث بالاسم، اسم المستخدم، أو البريد..." 
                           value="{{ request('search') }}">
                </div>
            </div>

            <div class="col-6 col-md-3">
                <select name="role" class="form-select form-select-sm">
                    <option value="">جميع الأدوار والصلاحيات</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>المدير العام (Admin)</option>
                    <option value="manager" {{ request('role') === 'manager' ? 'selected' : '' }}>مدير العمليات (Manager)</option>
                    <option value="cashier" {{ request('role') === 'cashier' ? 'selected' : '' }}>أمين الصندوق (Cashier)</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">جميع الحالات</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>نشط فقط</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>معطل فقط</option>
                </select>
            </div>

            <div class="col-6 col-md-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-mz-primary">
                    <i class="bi bi-filter"></i>
                    <span>تصفية</span>
                </button>
                @if(request()->hasAny(['search', 'role', 'status']))
                    <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary" title="إلغاء التصفية">
                        <i class="bi bi-x-circle"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="table-dense-container">
    <table class="table-dense">
        <thead>
            <tr>
                <th>اسم المستخدم</th>
                <th>الاسم الكامل</th>
                <th>البريد الإلكتروني</th>
                <th>الدور والصلاحية</th>
                <th>المستودع الافتراضي</th>
                <th>الحالة</th>
                <th class="text-start">العمليات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $u)
                <tr>
                    <td class="font-monospace fw-bold text-dark">{{ $u->username }}</td>
                    <td class="fw-semibold">{{ $u->name }}</td>
                    <td class="text-muted">{{ $u->email }}</td>
                    <td>
                        @if($u->role === 'admin')
                            <span class="badge bg-dark text-white px-2 py-1">المدير العام</span>
                        @elseif($u->role === 'manager')
                            <span class="badge bg-primary text-white px-2 py-1">مدير العمليات</span>
                        @else
                            <span class="badge bg-success text-white px-2 py-1">أمين الصندوق (كاشير)</span>
                        @endif
                    </td>
                    <td>
                        @if($u->warehouse)
                            <span class="badge bg-light text-secondary border">
                                <i class="bi bi-building me-1"></i>{{ $u->warehouse->name }}
                            </span>
                        @else
                            <span class="text-muted small">غير مخصص</span>
                        @endif
                    </td>
                    <td><x-status-badge :active="$u->is_active" /></td>
                    <td class="text-start">
                        <div class="d-inline-flex gap-1">
                            @can('update', $u)
                                <a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-outline-secondary p-1 px-2" title="تعديل">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            @endcan
                            @can('delete', $u)
                                <x-delete-button :action="route('users.destroy', $u)" message="هل أنت متأكد من حذف هذا المستخدم؟" />
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                        لا يوجد مستخدمين مطابقين لخيارات البحث.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $users->links('pagination::bootstrap-5') }}
</div>
@endsection
