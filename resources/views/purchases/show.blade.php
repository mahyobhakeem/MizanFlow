@extends('layouts.app')

@section('title', 'تفاصيل فاتورة الشراء: ' . $purchase->invoice_number)

@section('content')
<x-page-header 
    title="فاتورة مشتريات: {{ $purchase->invoice_number }}" 
    subtitle="عرض تفاصيل الفاتورة، المورد، البنود، والتأثير على متوسط تكلفة WAC المخزني"
    :breadcrumbs="['فواتير المشتريات' => route('purchases.index'), $purchase->invoice_number => null]"
>
    <x-slot:actions>
        <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-mz-outline">
            <i class="bi bi-arrow-right me-1"></i>
            <span>العودة للقائمة</span>
        </a>

        @if($purchase->isDraft())
            @can('update', $purchase)
            <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-sm btn-mz-outline text-primary">
                <i class="bi bi-pencil me-1"></i>
                <span>تعديل المسودة</span>
            </a>
            @endcan

            @can('approve', $purchase)
            <form method="POST" action="{{ route('purchases.approve', $purchase) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد من اعتماد الفاتورة؟ سيتم إيداع البضاعة وتحديث متوسط التكلفة WAC ولن يمكن تعديلها بعد ذلك.');">
                @csrf
                <button type="submit" class="btn btn-sm btn-mz-primary">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    <span>اعتماد الفاتورة وإيداع المخزون</span>
                </button>
            </form>
            @endcan

            @can('delete', $purchase)
                <x-delete-button :action="route('purchases.destroy', $purchase)" title="حذف المسودة" />
            @endcan
        @endif
    </x-slot:actions>
</x-page-header>

@if($purchase->isApproved())
<div class="alert alert-mz alert-mz-success mb-4">
    <i class="bi bi-shield-check fs-4 flex-shrink-0"></i>
    <div>
        <strong>فاتورة معتمدة ومقيدة مخزنياً:</strong>
        تم اعتماد هذه الفاتورة وإيداع كمياتها في مستودع <strong>{{ $purchase->warehouse->name }}</strong> بتاريخ {{ $purchase->approved_at?->format('Y-m-d H:i') }} بواسطة <strong>{{ $purchase->approvedByUser?->name ?? 'النظام' }}</strong>.
        الفاتورة مقفلة بشكل نهائي وغير قابلة للتعديل أو الحذف المباشر لضمان دقة السجلات المالية والمخزنية.
    </div>
</div>
@elseif($purchase->isDraft())
<div class="alert alert-mz alert-mz-warning mb-4">
    <i class="bi bi-info-circle-fill fs-4 flex-shrink-0"></i>
    <div>
        <strong>فاتورة مسودة (Draft):</strong>
        لم يتم إيداع هذه البضاعة في المخزون حتى الآن، ولم يتأثر رصيد أو متوسط تكلفة (WAC) أي صنف. قم بمراجعة البنود ثم اضغط على زر <strong>اعتماد الفاتورة</strong> عند استلام البضاعة فعلياً.
    </div>
</div>
@endif

<div class="row g-3 mb-4">
    <!-- بيانات الفاتورة والمورد -->
    <div class="col-md-8">
        <div class="mz-card h-100 mb-0">
            <div class="mz-card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt text-primary me-1"></i> معلومات المستند</span>
                <x-status-badge :status="$purchase->status" />
            </div>
            <div class="mz-card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <span class="text-muted d-block small">المورد:</span>
                        <span class="fw-bold text-dark fs-6">{{ $purchase->supplier->name }}</span>
                        <div class="text-muted small mt-1">
                            <span class="code-tag" style="font-size: 0.7rem;">{{ $purchase->supplier->code }}</span>
                            <span class="ms-1">هاتف: {{ $purchase->supplier->phone ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted d-block small">المستودع المستلم:</span>
                        <span class="fw-bold text-dark fs-6">{{ $purchase->warehouse->name }}</span>
                        <div class="text-muted small mt-1"><i class="bi bi-geo-alt me-1"></i>{{ $purchase->warehouse->location }}</div>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-muted d-block small">تاريخ الفاتورة:</span>
                        <span class="num-cell fw-semibold">{{ $purchase->issue_date->format('Y-m-d') }}</span>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-muted d-block small">رقم فاتورة المورد:</span>
                        <span class="num-cell fw-semibold">{{ $purchase->supplier_invoice_ref ?? '—' }}</span>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-muted d-block small">أنشئت بواسطة:</span>
                        <span class="fw-semibold">{{ $purchase->user->name }}</span>
                    </div>
                    @if($purchase->notes)
                    <div class="col-12 border-top pt-2 mt-2">
                        <span class="text-muted d-block small">ملاحظات الفاتورة:</span>
                        <p class="mb-0 text-secondary small">{{ $purchase->notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- بطاقة الملخص المالي -->
    <div class="col-md-4">
        <div class="mz-card h-100 mb-0">
            <div class="mz-card-header">
                <span><i class="bi bi-calculator text-primary me-1"></i> الملخص المالي للفاتورة</span>
            </div>
            <div class="mz-card-body">
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">المجموع قبل الضريبة:</span>
                    <span class="num-cell fw-semibold">{{ number_format($purchase->subtotal, 2) }} ر.س</span>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">ضريبة القيمة المضافة:</span>
                    <span class="num-cell fw-semibold">{{ number_format($purchase->tax_amount, 2) }} ر.س</span>
                </div>
                @if($purchase->discount_amount > 0)
                <div class="d-flex justify-content-between mb-2 small text-danger">
                    <span>الخصم الممنوح:</span>
                    <span class="num-cell">- {{ number_format($purchase->discount_amount, 2) }} ر.س</span>
                </div>
                @endif
                <hr class="my-2">
                <div class="d-flex justify-content-between mb-2 fs-6">
                    <span class="fw-bold text-dark">الإجمالي الصافي:</span>
                    <span class="num-cell fw-bold text-primary">{{ number_format($purchase->total_amount, 2) }} ر.س</span>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                    <span class="text-muted">المبلغ المدفوع:</span>
                    <span class="num-cell text-success fw-semibold">{{ number_format($purchase->paid_amount, 2) }} ر.س</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span class="text-muted">المتبقي ذمة للمورد:</span>
                    <span class="num-cell text-danger fw-bold">{{ number_format(max(0, $purchase->total_amount - $purchase->paid_amount), 2) }} ر.س</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- جدول بنود الأصناف -->
<div class="mz-card mb-4">
    <div class="mz-card-header">
        <span><i class="bi bi-boxes text-primary me-1"></i> أصناف الفاتورة ({{ $purchase->items->count() }} صنف)</span>
    </div>
    <div class="mz-card-body p-0">
        <div class="table-responsive">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>رمز الصنف (SKU)</th>
                        <th>اسم المنتج</th>
                        <th>الوحدة</th>
                        <th class="text-start">الكمية</th>
                        <th class="text-start">تكلفة الوحدة</th>
                        <th class="text-center">الضريبة</th>
                        <th class="text-start">إجمالي البند</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchase->items as $idx => $item)
                    <tr>
                        <td class="num-cell text-muted">{{ $idx + 1 }}</td>
                        <td><span class="code-tag">{{ $item->product->sku }}</span></td>
                        <td class="fw-semibold text-dark">{{ $item->product->name }}</td>
                        <td>{{ $item->product->unit->name }}</td>
                        <td class="text-start num-cell fw-bold">{{ number_format($item->quantity, 3) }}</td>
                        <td class="text-start num-cell">{{ number_format($item->unit_cost, 4) }} <span class="currency-symbol">ر.س</span></td>
                        <td class="text-center num-cell">{{ number_format($item->tax_percent, 0) }}%</td>
                        <td class="text-start num-cell fw-bold text-dark">{{ number_format($item->total_cost, 2) }} <span class="currency-symbol">ر.س</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- قيود دفتر الأستاذ المخزني المرتبطة -->
@if($purchase->movements->isNotEmpty())
<div class="mz-card">
    <div class="mz-card-header">
        <span><i class="bi bi-clock-history text-primary me-1"></i> سجل حركات دفتر الأستاذ الناتجة عن هذه الفاتورة</span>
    </div>
    <div class="mz-card-body p-0">
        <div class="table-responsive">
            <table class="table-dense">
                <thead>
                    <tr>
                        <th>رقم القيد</th>
                        <th>المنتج</th>
                        <th class="text-start">الكمية الواردة</th>
                        <th class="text-center">الرصيد قبل</th>
                        <th class="text-center">الرصيد بعد</th>
                        <th class="text-start">تكلفة الوحدة</th>
                        <th class="text-start">إجمالي القيمة</th>
                        <th>توقيت الحركة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchase->movements as $mov)
                    <tr>
                        <td><span class="code-tag">#{{ $mov->id }}</span></td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $mov->product->name }}</div>
                            <div class="text-muted small num-cell" style="font-size: 0.725rem;">SKU: {{ $mov->product->sku }}</div>
                        </td>
                        <td class="text-start num-cell text-success fw-bold">+{{ number_format($mov->quantity, 3) }}</td>
                        <td class="text-center num-cell text-muted">{{ number_format($mov->before_quantity, 3) }}</td>
                        <td class="text-center num-cell fw-bold">{{ number_format($mov->after_quantity, 3) }}</td>
                        <td class="text-start num-cell">{{ number_format($mov->unit_cost, 4) }} <span class="currency-symbol">ر.س</span></td>
                        <td class="text-start num-cell fw-bold">{{ number_format($mov->total_cost, 2) }} <span class="currency-symbol">ر.س</span></td>
                        <td class="small text-muted num-cell">{{ $mov->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
