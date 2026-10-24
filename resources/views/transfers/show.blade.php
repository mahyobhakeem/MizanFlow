@extends('layouts.app')

@section('title', 'تفاصيل التحويل المخزني: ' . $transfer->transfer_number)

@section('content')
<x-page-header 
    title="أمر تحويل مخزني: {{ $transfer->transfer_number }}" 
    subtitle="عرض تفاصيل الشحنة، المستودعات، وحركات الخصم والإيداع"
    :breadcrumbs="[
        ['label' => 'المخزون والتحويلات'],
        ['label' => 'أوامر التحويل المخزني', 'url' => route('transfers.index')],
        ['label' => $transfer->transfer_number]
    ]">
    <x-slot:actions>
        <a href="{{ route('transfers.index') }}" class="btn btn-mz-outline">
            <i class="bi bi-arrow-right"></i>
            <span>العودة للقائمة</span>
        </a>

        @if($transfer->isDraft())
            @can('dispatch', $transfer)
            <form method="POST" action="{{ route('transfers.dispatch', $transfer) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد من شحن البضاعة؟ سيتم خصم الكميات من المستودع المصدر وتحويل الحالة إلى (في الطريق).');">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-truck"></i>
                    <span>شحن البضاعة الآن</span>
                </button>
            </form>
            @endcan

            @can('delete', $transfer)
                <x-delete-button :action="route('transfers.destroy', $transfer)" title="حذف مسودة التحويل" />
            @endcan
        @elseif($transfer->isInTransit())
            @can('receive', $transfer)
            <form method="POST" action="{{ route('transfers.receive', $transfer) }}" class="d-inline" onsubmit="return confirm('هل تؤكد استلام الشحنة في المستودع الهدف؟ سيتم إيداع الكميات واحتساب متوسط التكلفة WAC.');">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>تأكيد استلام الشحنة في المستودع الهدف</span>
                </button>
            </form>
            @endcan
        @endif
    </x-slot:actions>
</x-page-header>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="mz-card">
            <div class="mz-card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-arrow-left-right text-primary me-1"></i> مسار الشحنة وحالتها التشغيلية</span>
                @if($transfer->isCompleted())
                    <span class="status-badge status-completed">
                        <span class="status-dot"></span> مكتمل ومستلم (Completed)
                    </span>
                @elseif($transfer->isInTransit())
                    <span class="status-badge status-in-transit">
                        <span class="status-dot"></span> مشحون وفي الطريق (In Transit)
                    </span>
                @else
                    <span class="status-badge status-draft">
                        <span class="status-dot"></span> مسودة (Draft)
                    </span>
                @endif
            </div>
            <div class="mz-card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <div class="p-3 bg-light rounded border text-center">
                            <span class="text-danger fw-bold d-block mb-1 small"><i class="bi bi-box-arrow-up-right me-1"></i> المستودع المصدر (تم الخصم منه)</span>
                            <h5 class="mb-0 fw-bold text-dark">{{ $transfer->fromWarehouse->name }}</h5>
                            <div class="small text-muted font-monospace mt-1">{{ $transfer->fromWarehouse->location ?? $transfer->fromWarehouse->code }}</div>
                        </div>
                    </div>
                    <div class="col-md-2 text-center">
                        <i class="bi bi-arrow-left fs-2 text-primary d-none d-md-inline-block"></i>
                        <i class="bi bi-arrow-down fs-2 text-primary d-md-none"></i>
                    </div>
                    <div class="col-md-5">
                        <div class="p-3 bg-light rounded border text-center">
                            <span class="text-success fw-bold d-block mb-1 small"><i class="bi bi-box-arrow-in-down-left me-1"></i> المستودع الوجهة (تم الإيداع فيه)</span>
                            <h5 class="mb-0 fw-bold text-dark">{{ $transfer->toWarehouse->name }}</h5>
                            <div class="small text-muted font-monospace mt-1">{{ $transfer->toWarehouse->location ?? $transfer->toWarehouse->code }}</div>
                        </div>
                    </div>
                </div>

                <hr class="my-3">

                <div class="row g-3 small text-muted">
                    <div class="col-sm-3">
                        <span class="d-block">أنشئ بواسطة:</span>
                        <strong class="text-dark">{{ $transfer->createdByUser->name }}</strong>
                    </div>
                    <div class="col-sm-3">
                        <span class="d-block">تاريخ الإنشاء:</span>
                        <strong class="text-dark num-cell">{{ $transfer->created_at->format('Y-m-d H:i') }}</strong>
                    </div>
                    <div class="col-sm-3">
                        <span class="d-block">تاريخ الشحن:</span>
                        <strong class="text-dark num-cell">{{ $transfer->dispatched_at ? $transfer->dispatched_at->format('Y-m-d H:i') : 'لم يُشحن بعد' }}</strong>
                    </div>
                    <div class="col-sm-3">
                        <span class="d-block">تاريخ ومستلم الشحنة:</span>
                        <strong class="text-dark">
                            @if($transfer->received_at)
                                <span class="num-cell">{{ $transfer->received_at->format('Y-m-d H:i') }}</span> ({{ $transfer->receivedByUser?->name }})
                            @else
                                قيد الانتظار
                            @endif
                        </strong>
                    </div>
                    @if($transfer->notes)
                    <div class="col-12 mt-2">
                        <span class="d-block">ملاحظات التحويل:</span>
                        <span class="text-secondary">{{ $transfer->notes }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- جدول الأصناف المحولة -->
<div class="mz-card mb-4">
    <div class="mz-card-header">
        <i class="bi bi-boxes text-primary me-1"></i>
        <span>الأصناف المحولة ({{ $transfer->items->count() }} صنف)</span>
    </div>
    <div class="mz-card-body p-0">
        <div class="table-dense-container">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 15%;">رمز الصنف (SKU)</th>
                        <th style="width: 35%;">اسم المنتج</th>
                        <th style="width: 10%;">الوحدة</th>
                        <th class="text-start" style="width: 15%;">الكمية المشحونة</th>
                        <th class="text-start" style="width: 15%;">الكمية المستلمة</th>
                        <th class="text-start" style="width: 15%;">تكلفة الوحدة (WAC)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfer->items as $idx => $item)
                    <tr>
                        <td class="num-cell text-muted">{{ $idx + 1 }}</td>
                        <td><span class="code-tag">{{ $item->product->sku }}</span></td>
                        <td class="fw-semibold text-dark">{{ $item->product->name }}</td>
                        <td class="text-muted small">{{ $item->product->unit->name }}</td>
                        <td class="text-start num-cell fw-bold">{{ number_format($item->quantity_sent, 3) }}</td>
                        <td class="text-start num-cell text-success fw-bold">
                            {{ $item->quantity_received !== null ? number_format($item->quantity_received, 3) : '—' }}
                        </td>
                        <td class="text-start num-cell">{{ number_format($item->unit_cost_snapshot, 4) }} ر.س</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- قيود دفتر الأستاذ المخزني المرتبطة -->
@if($transfer->movements->isNotEmpty())
<div class="mz-card">
    <div class="mz-card-header">
        <i class="bi bi-clock-history text-primary me-1"></i>
        <span>قيود دفتر الأستاذ لحركات المخزون الناتجة عن التحويل</span>
    </div>
    <div class="mz-card-body p-0">
        <div class="table-dense-container">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th>رقم القيد</th>
                        <th>المستودع</th>
                        <th>نوع الحركة</th>
                        <th>المنتج</th>
                        <th class="text-start">كمية الحركة</th>
                        <th class="text-start">الرصيد قبل</th>
                        <th class="text-start">الرصيد بعد</th>
                        <th class="text-start">التكلفة</th>
                        <th>التوقيت</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transfer->movements as $mov)
                    <tr>
                        <td><span class="code-tag">#{{ $mov->id }}</span></td>
                        <td class="fw-semibold text-dark">{{ $mov->warehouse->name }}</td>
                        <td>
                            @if($mov->movement_type === 'transfer_out')
                                <span class="status-badge status-voided">
                                    <span class="status-dot"></span> صادر (خصم)
                                </span>
                            @else
                                <span class="status-badge status-completed">
                                    <span class="status-dot"></span> وارد (إيداع)
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-medium text-dark">{{ $mov->product->name }}</div>
                            <span class="code-tag">{{ $mov->product->sku }}</span>
                        </td>
                        <td class="text-start num-cell fw-bold {{ $mov->quantity < 0 ? 'text-danger' : 'text-success' }}">
                            {{ $mov->quantity > 0 ? '+' : '' }}{{ number_format($mov->quantity, 3) }}
                        </td>
                        <td class="text-start num-cell text-muted">{{ number_format($mov->before_quantity, 3) }}</td>
                        <td class="text-start num-cell fw-bold">{{ number_format($mov->after_quantity, 3) }}</td>
                        <td class="text-start num-cell">{{ number_format($mov->unit_cost, 4) }} ر.س</td>
                        <td class="small num-cell text-muted">{{ $mov->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
