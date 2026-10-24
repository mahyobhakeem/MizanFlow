@extends('layouts.app')

@section('title', 'فاتورة مبيعات ' . $sale->invoice_number)

@section('content')
<x-page-header 
    :title="'فاتورة مبيعات: ' . $sale->invoice_number" 
    subtitle="تفاصيل مستند البيع والفوترة وحركات الصرف المالي والمخزني المرتبطة"
    :breadcrumbs="['فواتير المبيعات' => route('sales.index'), $sale->invoice_number => null]"
>
    <x-slot:actions>
        <div class="btn-group">
            <a href="{{ route('sales.print', $sale) }}" target="_blank" class="btn btn-sm btn-mz-outline">
                <i class="bi bi-printer me-1"></i> طباعة الفاتورة
            </a>
            <a href="{{ route('sales.index') }}" class="btn btn-sm btn-mz-outline">
                <i class="bi bi-arrow-right me-1"></i> الفواتير
            </a>
        </div>

        @if($sale->isDraft())
            @can('update', $sale)
            <a href="{{ route('sales.edit', $sale) }}" class="btn btn-sm btn-mz-outline text-primary">
                <i class="bi bi-pencil me-1"></i> تعديل المسودة
            </a>
            @endcan

            @can('complete', $sale)
            <form method="POST" action="{{ route('sales.complete', $sale) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد من اعتماد الفاتورة وصرف الكميات من المستودع؟ لا يمكن التراجع إلا بحركة عكسية.');">
                @csrf
                <button type="submit" class="btn btn-sm btn-mz-primary">
                    <i class="bi bi-check-circle me-1"></i> اعتماد وصرف المخزون
                </button>
            </form>
            @endcan

            @can('delete', $sale)
            <form method="POST" action="{{ route('sales.destroy', $sale) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه المسودة نهائياً؟');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-mz-outline text-danger">
                    <i class="bi bi-trash me-1"></i> حذف المسودة
                </button>
            </form>
            @endcan
        @elseif($sale->isCompleted())
            @can('void', $sale)
            <button type="button" class="btn btn-sm btn-mz-outline text-danger" data-bs-toggle="modal" data-bs-target="#voidModal">
                <i class="bi bi-arrow-counterclockwise me-1"></i> إلغاء عكسي واسترجاع المخزون
            </button>
            @endcan
        @endif
    </x-slot:actions>
</x-page-header>

@if($sale->isVoided())
<div class="alert alert-mz alert-mz-danger d-flex align-items-center mb-4">
    <i class="bi bi-exclamation-octagon-fill fs-3 flex-shrink-0"></i>
    <div>
        <div class="fw-bold mb-1">تم إلغاء هذه الفاتورة بحركة عكسية رسمية واسترجاع المخزون</div>
        <div class="small opacity-90">
            تاريخ الإلغاء: {{ $sale->voided_at?->format('Y-m-d H:i') }} 
            | بواسطة: <strong>{{ $sale->voidedByUser?->name ?? 'مستخدم غير محدد' }}</strong>
            | سبب الإلغاء: {{ $sale->void_reason ?? 'لا يوجد سبب محدد' }}
        </div>
    </div>
</div>
@endif

<div class="row g-3 mb-4">
    <!-- معلومات الفاتورة والعميل -->
    <div class="col-lg-4">
        <div class="mz-card mb-3">
            <div class="mz-card-header">
                <span class="fw-bold"><i class="bi bi-info-circle me-1 text-primary"></i> بيانات المستند</span>
                <x-status-badge :status="$sale->status" />
            </div>
            <div class="mz-card-body p-0">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between py-2">
                        <span class="text-muted">رقم الفاتورة:</span>
                        <span class="code-tag">{{ $sale->invoice_number }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2">
                        <span class="text-muted">المستودع المصرف منه:</span>
                        <span class="fw-semibold text-dark">{{ $sale->warehouse->name }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2">
                        <span class="text-muted">تاريخ ووقت الفاتورة:</span>
                        <span class="num-cell">{{ $sale->invoice_date->format('Y-m-d H:i') }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2">
                        <span class="text-muted">الكاشير / منشئ الفاتورة:</span>
                        <span class="fw-semibold">{{ $sale->user->name }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2">
                        <span class="text-muted">طريقة الدفع:</span>
                        <span class="badge bg-light text-dark border">{{ $sale->paymentTypeLabel() }}</span>
                    </li>
                    @if($sale->completed_at)
                    <li class="list-group-item d-flex justify-content-between py-2">
                        <span class="text-muted">تاريخ الاعتماد:</span>
                        <span class="text-success num-cell">{{ $sale->completed_at->format('Y-m-d H:i') }}</span>
                    </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="mz-card mb-3">
            <div class="mz-card-header">
                <span class="fw-bold"><i class="bi bi-person me-1 text-primary"></i> بيانات العميل والذمة</span>
            </div>
            <div class="mz-card-body p-0">
                @if($sale->customer)
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between py-2">
                        <span class="text-muted">الاسم:</span>
                        <span class="fw-bold text-dark">{{ $sale->customer->name }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2">
                        <span class="text-muted">الكود:</span>
                        <span class="code-tag">{{ $sale->customer->code }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2">
                        <span class="text-muted">الهاتف:</span>
                        <span class="num-cell">{{ $sale->customer->phone ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2">
                        <span class="text-muted">الرقم الضريبي:</span>
                        <span class="num-cell text-muted">{{ $sale->customer->tax_number ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between py-2">
                        <span class="text-muted">مديونية العميل القائمة:</span>
                        <span class="num-cell text-danger fw-bold">{{ number_format($sale->customer->balance, 2) }} ر.س</span>
                    </li>
                </ul>
                @else
                <div class="p-3 text-center text-muted small">
                    <i class="bi bi-person-x fs-3 d-block mb-1 text-secondary opacity-50"></i>
                    عميل نقدي عابر (Walk-in Customer)
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- بنود الفاتورة والملخص المالي -->
    <div class="col-lg-8">
        <div class="mz-card mb-3">
            <div class="mz-card-header">
                <span class="fw-bold"><i class="bi bi-box-seam me-1 text-primary"></i> أصناف وبنود الفاتورة</span>
                <span class="badge bg-light text-secondary border">{{ $sale->items->count() }} صنف</span>
            </div>
            <div class="mz-card-body p-0">
                <div class="table-responsive">
                    <table class="table-dense">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>المنتج</th>
                                <th class="text-center">الكمية</th>
                                <th class="text-start">سعر البيع</th>
                                @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                                <th class="text-start">التكلفة (WAC)</th>
                                <th class="text-start">الربح</th>
                                @endif
                                <th class="text-center">الضريبة</th>
                                <th class="text-start">الإجمالي الصافي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->items as $idx => $item)
                            <tr>
                                <td class="num-cell text-muted">{{ $idx + 1 }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item->product->name }}</div>
                                    <div class="text-muted small num-cell" style="font-size: 0.725rem;">SKU: {{ $item->product->sku }}</div>
                                </td>
                                <td class="text-center num-cell">
                                    {{ number_format($item->quantity, 3) }}
                                    <span class="text-muted small">{{ $item->product->unit?->name }}</span>
                                </td>
                                <td class="text-start num-cell">{{ number_format($item->unit_price, 2) }} <span class="currency-symbol">ر.س</span></td>
                                @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                                <td class="text-start num-cell text-muted">
                                    {{ number_format($item->unit_cost_snapshot, 2) }}
                                </td>
                                <td class="text-start num-cell {{ $item->totalProfit() >= 0 ? 'text-success fw-semibold' : 'text-danger fw-semibold' }}">
                                    {{ number_format($item->totalProfit(), 2) }}
                                    <div class="small text-muted" style="font-size: 0.7rem;">{{ $item->profitMargin() }}%</div>
                                </td>
                                @endif
                                <td class="text-center num-cell">{{ $item->tax_percent }}%</td>
                                <td class="text-start num-cell fw-bold text-dark">{{ number_format($item->total_price, 2) }} <span class="currency-symbol">ر.س</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- الملخص المالي -->
                <div class="p-3 border-top" style="background-color: var(--mz-surface-subtle);">
                    <div class="row justify-content-end">
                        <div class="col-md-7 col-lg-6">
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted">المجموع قبل الضريبة:</span>
                                <span class="num-cell fw-semibold">{{ number_format($sale->subtotal, 2) }} ر.س</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted">ضريبة القيمة المضافة (15%):</span>
                                <span class="num-cell fw-semibold">{{ number_format($sale->tax_amount, 2) }} ر.س</span>
                            </div>
                            @if($sale->discount_amount > 0)
                            <div class="d-flex justify-content-between mb-1 small text-danger">
                                <span>خصم الفاتورة:</span>
                                <span class="num-cell">- {{ number_format($sale->discount_amount, 2) }} ر.س</span>
                            </div>
                            @endif
                            <hr class="my-2">
                            <div class="d-flex justify-content-between mb-2 fs-6">
                                <span class="fw-bold text-dark">الصافي الإجمالي المستحق:</span>
                                <span class="num-cell fw-bold text-primary">{{ number_format($sale->total_amount, 2) }} ر.س</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="text-muted">المبلغ المدفوع (المحصل):</span>
                                <span class="num-cell text-success fw-semibold">{{ number_format($sale->paid_amount, 2) }} ر.س</span>
                            </div>
                            @if($sale->remainingAmount() > 0)
                            <div class="d-flex justify-content-between small text-danger fw-bold">
                                <span>المتبقي (آجل في ذمة العميل):</span>
                                <span class="num-cell">{{ number_format($sale->remainingAmount(), 2) }} ر.س</span>
                            </div>
                            @endif

                            @if((auth()->user()->isAdmin() || auth()->user()->isManager()) && $sale->isCompleted())
                            <div class="d-flex justify-content-between mt-2 pt-2 border-top text-success small fw-bold">
                                <span>مجمل الربح المحقق (Gross Profit):</span>
                                <span class="num-cell">{{ number_format($sale->totalGrossProfit(), 2) }} ر.س</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- سجل حركات دفتر الأستاذ المخزني المرتبطة -->
        @if($sale->movements->isNotEmpty())
        <div class="mz-card">
            <div class="mz-card-header">
                <span class="fw-bold"><i class="bi bi-clock-history me-1 text-primary"></i> القيود المخزنية المنفذة بدفتر الأستاذ</span>
            </div>
            <div class="mz-card-body p-0">
                <div class="table-responsive">
                    <table class="table-dense">
                        <thead>
                            <tr>
                                <th>نوع الحركة</th>
                                <th>المنتج</th>
                                <th class="text-center">الكمية</th>
                                <th class="text-center">الرصيد قبل</th>
                                <th class="text-center">الرصيد بعد</th>
                                <th class="text-start">التكلفة المطبقة</th>
                                <th>الوقت</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->movements as $m)
                            <tr>
                                <td>
                                    <span class="badge {{ $m->quantity < 0 ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle' }}" style="font-size: 0.725rem;">
                                        {{ $m->movement_type === 'sale' ? 'صرف مبيعات' : ($m->movement_type === 'sale_return' ? 'مردود مبيعات' : $m->movement_type) }}
                                    </span>
                                </td>
                                <td class="fw-semibold text-dark">{{ $m->product->name }}</td>
                                <td class="text-center num-cell fw-bold {{ $m->quantity < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $m->quantity > 0 ? '+' : '' }}{{ number_format($m->quantity, 3) }}
                                </td>
                                <td class="text-center num-cell text-muted">{{ number_format($m->before_quantity, 3) }}</td>
                                <td class="text-center num-cell fw-semibold">{{ number_format($m->after_quantity, 3) }}</td>
                                <td class="text-start num-cell">{{ number_format($m->unit_cost, 4) }} <span class="currency-symbol">ر.س</span></td>
                                <td class="small text-muted num-cell">{{ $m->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal إلغاء الفاتورة -->
@if($sale->isCompleted() && (auth()->user()->isAdmin() || auth()->user()->isManager()))
<div class="modal fade" id="voidModal" tabindex="-1" aria-labelledby="voidModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border">
            <form method="POST" action="{{ route('sales.void', $sale) }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fs-6 fw-bold" id="voidModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i> إلغاء عكسي لفاتورة المبيعات</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-mz alert-mz-danger mb-3">
                        <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                        <div>
                            تحذير: سيؤدي هذا الإجراء إلى إلغاء الفاتورة نهائياً، واسترجاع كميات جميع الأصناف إلى المستودع (حركات عكسية)، وتعديل رصيد مديونية العميل.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">سبب الإلغاء أو المردود <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required placeholder="اذكر سبب إلغاء الفاتورة بدقة للتوثيق والتدقيق..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-mz-outline" data-bs-dismiss="modal">تراجع</button>
                    <button type="submit" class="btn btn-sm btn-mz-danger">تأكيد الإلغاء واسترجاع المخزون</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
