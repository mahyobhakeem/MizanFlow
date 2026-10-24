<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاتورة ضريبية مبسطة - {{ $sale->invoice_number }}</title>

    <!-- Local Bundled Assets (Vite) - Zero CDN dependency for full offline and compliance integrity -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            background-color: #f8fafc;
            color: #0f172a;
            font-size: 13px;
        }
        .invoice-box {
            max-width: 820px;
            margin: 20px auto;
            padding: 28px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .table-invoice th {
            background-color: #f1f5f9 !important;
            border-bottom: 2px solid #cbd5e1;
            font-weight: 700;
            color: #1e293b;
        }
        .table-invoice td, .table-invoice th {
            padding: 8px 10px;
            vertical-align: middle;
        }
        .qr-placeholder {
            width: 95px;
            height: 95px;
            border: 1px dashed #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            text-align: center;
            background: #f8fafc;
            color: #475569;
            padding: 4px;
        }
        @media print {
            body {
                background: #ffffff !important;
                font-size: 12px;
            }
            .invoice-box {
                margin: 0 !important;
                padding: 10px !important;
                border: none !important;
                box-shadow: none !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
            @page {
                margin: 10mm;
                size: A4 portrait;
            }
        }
    </style>
</head>
<body>

<!-- شريط أزرار الطباعة في المتصفح -->
<div class="no-print container max-w-800 my-3 text-center">
    <button onclick="window.print()" class="btn btn-mz-primary px-4 me-2">
        <i class="bi bi-printer"></i> طباعة الفاتورة الآن
    </button>
    <button onclick="window.close()" class="btn btn-mz-outline px-3">
        إغلاق النافذة
    </button>
</div>

<div class="invoice-box">
    <!-- ترويسة الفاتورة -->
    <div class="row align-items-center mb-3 pb-3 border-bottom">
        <div class="col-7">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="fs-4 text-primary"><i class="bi bi-intersect"></i></span>
                <h4 class="fw-bold mb-0 text-dark">شركة ميزان فلو للتجارة المحدودة</h4>
            </div>
            <div class="text-muted small">نظام إدارة الموارد والمخزون والمبيعات الموحد</div>
            <div class="mt-2 small">
                <div><strong>الرقم الضريبي للمنشأة:</strong> <span class="font-monospace">310123456700003</span></div>
                <div><strong>السجل التجاري:</strong> <span class="font-monospace">1010998877</span></div>
                <div><strong>المستودع / الفرع المصرف:</strong> {{ $sale->warehouse->name }}</div>
            </div>
        </div>
        <div class="col-5 text-end">
            <div class="d-inline-block text-center">
                <div class="qr-placeholder mb-1">
                    <i class="bi bi-qr-code fs-2 d-block text-secondary mb-1"></i>
                    [رمز الاستجابة ZATCA]
                </div>
                <div class="badge bg-light text-dark border">فاتورة ضريبية مبسطة</div>
            </div>
        </div>
    </div>

    <!-- معلومات الفاتورة والعميل -->
    <div class="row mb-3 pb-2 border-bottom small">
        <div class="col-6">
            <div class="mb-1"><strong>رقم الفاتورة:</strong> <span class="font-monospace fw-bold text-primary">{{ $sale->invoice_number }}</span></div>
            <div class="mb-1"><strong>تاريخ ووقت الإصدار:</strong> <span>{{ $sale->invoice_date->format('Y-m-d H:i') }}</span></div>
            <div><strong>الكاشير / الموظف:</strong> <span>{{ $sale->user->name }}</span></div>
        </div>
        <div class="col-6 text-end">
            @if($sale->customer)
                <div class="mb-1"><strong>اسم العميل:</strong> <span class="fw-semibold">{{ $sale->customer->name }}</span></div>
                @if($sale->customer->tax_number)
                <div class="mb-1"><strong>الرقم الضريبي للعميل:</strong> <span class="font-monospace">{{ $sale->customer->tax_number }}</span></div>
                @endif
                <div><strong>هاتف العميل:</strong> <span class="font-monospace">{{ $sale->customer->phone ?? '—' }}</span></div>
            @else
                <div class="mb-1"><strong>نوع العميل:</strong> <span>عميل نقدي عابر</span></div>
            @endif
            <div class="mt-1"><strong>طريقة السداد:</strong> <span class="badge bg-light text-dark border">{{ $sale->paymentTypeLabel() }}</span></div>
        </div>
    </div>

    <!-- جدول البنود -->
    <table class="table table-bordered table-invoice mb-3">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 45%;">البيان / اسم الصنف</th>
                <th style="width: 12%;" class="text-center">الكمية</th>
                <th style="width: 13%;" class="text-end">سعر الوحدة</th>
                <th style="width: 10%;" class="text-center">الضريبة</th>
                <th style="width: 15%;" class="text-end">الإجمالي الصافي</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $idx => $item)
            <tr>
                <td class="text-center font-monospace">{{ $idx + 1 }}</td>
                <td>
                    <div class="fw-bold">{{ $item->product->name }}</div>
                    <div class="text-muted small font-monospace">SKU: {{ $item->product->sku }}</div>
                </td>
                <td class="text-center font-monospace">
                    {{ number_format($item->quantity, 3) }}
                    <span class="small text-muted">{{ $item->product->unit?->name }}</span>
                </td>
                <td class="text-end font-monospace">{{ number_format($item->unit_price, 2) }} ر.س</td>
                <td class="text-center font-monospace">{{ $item->tax_percent }}%</td>
                <td class="text-end font-monospace fw-bold">{{ number_format($item->total_price, 2) }} ر.س</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- الملخص المالي -->
    <div class="row align-items-end mb-4">
        <div class="col-6 small">
            @if($sale->notes)
            <div class="p-2 bg-light rounded border mb-2">
                <strong>ملاحظات:</strong> {{ $sale->notes }}
            </div>
            @endif
            <div class="text-muted">
                * الأسعار الموضحة بالريال السعودي وتشمل ضريبة القيمة المضافة بنسبة 15%.<br>
                * البضاعة المباعة ترد وتستبدل خلال 7 أيام من تاريخ الفاتورة بشرط سلامة المنتج والغلاف الأصلي.
            </div>
        </div>
        <div class="col-6">
            <div class="border rounded p-2 bg-light">
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">المجموع قبل الضريبة:</span>
                    <span class="font-monospace fw-semibold">{{ number_format($sale->subtotal, 2) }} ر.س</span>
                </div>
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">ضريبة القيمة المضافة (15%):</span>
                    <span class="font-monospace fw-semibold">{{ number_format($sale->tax_amount, 2) }} ر.س</span>
                </div>
                @if($sale->discount_amount > 0)
                <div class="d-flex justify-content-between mb-1 small text-danger">
                    <span>خصم إضافي:</span>
                    <span class="font-monospace">- {{ number_format($sale->discount_amount, 2) }} ر.س</span>
                </div>
                @endif
                <div class="d-flex justify-content-between py-1 border-top border-bottom fs-6 fw-bold">
                    <span>المجموع الإجمالي المطلوب:</span>
                    <span class="font-monospace text-primary">{{ number_format($sale->total_amount, 2) }} ر.س</span>
                </div>
                <div class="d-flex justify-content-between mt-1 small">
                    <span>المبلغ المدفوع:</span>
                    <span class="font-monospace fw-bold text-success">{{ number_format($sale->paid_amount, 2) }} ر.س</span>
                </div>
                @if($sale->remainingAmount() > 0)
                <div class="d-flex justify-content-between mt-1 small text-danger fw-bold">
                    <span>المتبقي في الذمة:</span>
                    <span class="font-monospace">{{ number_format($sale->remainingAmount(), 2) }} ر.س</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- التذييل -->
    <div class="text-center pt-3 border-top text-muted small">
        شكراً لتعاملكم معنا | صُدرت آلياً عبر نظام MizanFlow لإدارة المبيعات والمخزون
    </div>
</div>

</body>
</html>
