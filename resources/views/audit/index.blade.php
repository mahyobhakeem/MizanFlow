@extends('layouts.app')

@section('title', 'سجل التدقيق والأمان والرقابة')

@section('content')
<x-page-header 
    title="سجل التدقيق والأمان والرقابة" 
    subtitle="تسجيل العمليات الحساسة وتتبع المستخدمين والتحقق من سلامة العمليات المالية والمخزنية"
    :breadcrumbs="[
        ['label' => 'مركز الرقابة والعمليات'],
        ['label' => 'سجل التدقيق الأمني']
    ]">
    <x-slot:actions>
        <span class="code-tag px-3 py-2 fs-6">
            <i class="bi bi-shield-check me-1 text-primary"></i> {{ $logs->total() }} قيد تدقيق
        </span>
    </x-slot:actions>
</x-page-header>

<!-- تصفية سجل التدقيق -->
<div class="mz-card mb-4 no-print">
    <div class="mz-card-body p-3">
        <form method="GET" action="{{ route('audit-logs.index') }}">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">بحث في الوصف أو IP:</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="ابحث برقم الفاتورة، الوصف، عنوان IP...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">نوع العملية:</label>
                    <select name="event" class="form-select form-select-sm">
                        <option value="">— جميع العمليات —</option>
                        @foreach($events as $key => $lbl)
                            <option value="{{ $key }}" {{ request('event') === $key ? 'selected' : '' }}>{{ $lbl }} ({{ $key }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">المستخدم:</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">— جميع المستخدمين —</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">من تاريخ:</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2 d-flex gap-1 align-self-end">
                    <button type="submit" class="btn btn-sm btn-mz-primary flex-fill">تصفية السجل</button>
                    @if(request()->anyFilled(['search', 'event', 'user_id', 'date_from', 'date_to']))
                        <a href="{{ route('audit-logs.index') }}" class="btn btn-sm btn-outline-secondary" title="إعادة تعيين"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<!-- جدول سجلات التدقيق -->
<div class="mz-card mb-4">
    <div class="mz-card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-shield-shaded text-primary me-1"></i> القيود الأمنية والتنفيذية المسجلة</span>
        <span class="badge bg-secondary-subtle text-secondary">{{ $logs->count() }} معروض في هذه الصفحة</span>
    </div>
    <div class="mz-card-body p-0">
        <div class="table-dense-container">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th style="width: 14%;">الوقت والتاريخ</th>
                        <th style="width: 16%;">المستخدم</th>
                        <th style="width: 15%;">نوع الحدث</th>
                        <th style="width: 42%;">بيان العملية والتفاصيل</th>
                        <th style="width: 13%;">عنوان IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="small">
                            <div class="fw-semibold text-dark">{{ $log->created_at->format('Y-m-d') }}</div>
                            <div class="text-muted num-cell" style="font-size: 0.75rem;">{{ $log->created_at->format('H:i:s') }}</div>
                        </td>
                        <td>
                            @if($log->user)
                                <div class="fw-semibold text-dark">{{ $log->user->name }}</div>
                                <div class="text-muted small">{{ $log->user->role_label }}</div>
                            @else
                                <span class="badge bg-light text-muted border">نظام آلي / ضيف</span>
                            @endif
                        </td>
                        <td>
                            <span class="{{ $log->eventBadgeClass() }} py-1 px-2" style="font-size: 0.75rem; border-radius: var(--mz-radius-xs);">
                                {{ $log->eventLabel() }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-medium text-dark">{{ $log->description ?? '—' }}</div>
                            @if($log->new_values)
                                <div class="mt-1 code-tag d-inline-block text-truncate" style="max-width: 500px; font-size: 0.75rem;">
                                    {{ json_encode($log->new_values, JSON_UNESCAPED_UNICODE) }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="code-tag">{{ $log->ip_address ?? '127.0.0.1' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-0 border-0">
                            <x-table-empty 
                                icon="bi-shield-check text-secondary" 
                                message="لا توجد قيود أمنية مسجلة مطابقة لمعايير البحث الحالية." 
                            />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="p-3 border-top no-print">
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>
@endsection
