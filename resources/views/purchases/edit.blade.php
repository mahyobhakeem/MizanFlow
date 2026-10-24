@extends('layouts.app')

@section('title', 'تعديل مسودة فاتورة الشراء: ' . $purchase->invoice_number)

@section('content')
<x-page-header 
    title="تعديل مسودة فاتورة الشراء" 
    subtitle="تعديل بيانات وأصناف الفاتورة المسودة قبل الاعتماد"
    :breadcrumbs="[
        ['label' => 'المشتريات والموردين'],
        ['label' => 'فواتير المشتريات', 'url' => route('purchases.index')],
        ['label' => 'تعديل ' . $purchase->invoice_number]
    ]">
    <x-slot:actions>
        <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-mz-outline">
            <i class="bi bi-arrow-right"></i>
            <span>العودة للفاتورة</span>
        </a>
    </x-slot:actions>
</x-page-header>

<div class="mz-card">
    <div class="mz-card-header">
        <i class="bi bi-pencil-square text-primary me-1"></i>
        <span>تعديل فاتورة رقم: {{ $purchase->invoice_number }}</span>
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

        <form method="POST" action="{{ route('purchases.update', $purchase) }}" id="purchaseForm">
            @csrf
            @method('PUT')

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label for="supplier_id" class="form-label">المورد <span class="text-danger">*</span></label>
                    <select class="form-select @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id" required>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id', $purchase->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }} ({{ $supplier->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="warehouse_id" class="form-label">المستودع المستلم <span class="text-danger">*</span></label>
                    <select class="form-select @error('warehouse_id') is-invalid @enderror" id="warehouse_id" name="warehouse_id" required>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $purchase->warehouse_id) == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }} ({{ $warehouse->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="issue_date" class="form-label">تاريخ الفاتورة <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('issue_date') is-invalid @enderror" id="issue_date" name="issue_date" value="{{ old('issue_date', $purchase->issue_date->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="supplier_invoice_ref" class="form-label">رقم فاتورة المورد</label>
                    <input type="text" class="form-control" id="supplier_invoice_ref" name="supplier_invoice_ref" value="{{ old('supplier_invoice_ref', $purchase->supplier_invoice_ref) }}">
                </div>
            </div>

            <!-- جدول بنود الأصناف -->
            <div class="card border mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold"><i class="bi bi-box-seam me-1"></i> أصناف الفاتورة</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
                        <i class="bi bi-plus-circle me-1"></i> إضافة صنف آخر
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 align-middle" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%;">المنتج <span class="text-danger">*</span></th>
                                    <th style="width: 15%;">الكمية <span class="text-danger">*</span></th>
                                    <th style="width: 20%;">تكلفة الوحدة (ر.س) <span class="text-danger">*</span></th>
                                    <th style="width: 10%;">الضريبة %</th>
                                    <th style="width: 15%;" class="text-end">الإجمالي (ر.س)</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <!-- Pre-populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="notes" class="form-label">ملاحظات الفاتورة</label>
                    <textarea class="form-control" id="notes" name="notes" rows="4">{{ old('notes', $purchase->notes) }}</textarea>
                </div>
                <div class="col-md-6">
                    <div class="bg-light p-3 rounded border">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">المجموع قبل الضريبة:</span>
                            <span class="font-monospace fw-semibold" id="dispSubtotal">0.00 ر.س</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">إجمالي ضريبة القيمة المضافة:</span>
                            <span class="font-monospace fw-semibold" id="dispTax">0.00 ر.س</span>
                        </div>
                        <div class="row g-2 mb-2 align-items-center">
                            <div class="col-6">
                                <label for="discount_amount" class="small text-muted mb-0">خصم إضافي (ر.س):</label>
                            </div>
                            <div class="col-6">
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end font-monospace" id="discount_amount" name="discount_amount" value="{{ old('discount_amount', $purchase->discount_amount) }}">
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-2 fs-5">
                            <span class="fw-bold">الصافي الإجمالي:</span>
                            <span class="font-monospace fw-bold text-primary" id="dispTotal">0.00 ر.س</span>
                        </div>
                        <div class="row g-2 align-items-center">
                            <div class="col-6">
                                <label for="paid_amount" class="small text-muted mb-0">المبلغ المدفوع حالياً:</label>
                            </div>
                            <div class="col-6">
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end font-monospace" id="paid_amount" name="paid_amount" value="{{ old('paid_amount', $purchase->paid_amount) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4 form-check bg-light p-3 rounded border">
                <input type="checkbox" class="form-check-input ms-2 me-0" id="approve_immediately" name="approve_immediately" value="1">
                <label class="form-check-label fw-bold" for="approve_immediately">
                    اعتماد الفاتورة فوراً وإيداع الكميات في المستودع وتحديث متوسط التكلفة (WAC)
                </label>
            </div>

            <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-mz-outline">إلغاء</a>
                <button type="submit" class="btn btn-mz-primary">
                    <i class="bi bi-check-lg"></i>
                    <span>حفظ التعديلات</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const productsData = @json($products);
    const existingItems = @json($purchase->items);
    const tbody = document.getElementById('itemsBody');
    let rowIndex = 0;

    function addRow(productId = '', qty = 1, cost = 0, tax = 15) {
        const tr = document.createElement('tr');
        tr.dataset.index = rowIndex;

        let optionsHtml = '<option value="">— اختر الصنف —</option>';
        productsData.forEach(p => {
            const selected = (p.id == productId) ? 'selected' : '';
            optionsHtml += `<option value="${p.id}" ${selected}>${p.name} (${p.sku})</option>`;
        });

        tr.innerHTML = `
            <td>
                <select name="items[${rowIndex}][product_id]" class="form-select product-select" required>
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <input type="number" step="0.001" min="0.001" name="items[${rowIndex}][quantity]" value="${qty}" class="form-control font-monospace item-qty" required>
            </td>
            <td>
                <input type="number" step="0.0001" min="0" name="items[${rowIndex}][unit_cost]" value="${cost}" class="form-control font-monospace item-cost" required>
            </td>
            <td>
                <select name="items[${rowIndex}][tax_percent]" class="form-select font-monospace item-tax">
                    <option value="15" ${tax == 15 ? 'selected' : ''}>15%</option>
                    <option value="0" ${tax == 0 ? 'selected' : ''}>0%</option>
                </select>
            </td>
            <td class="text-end font-monospace fw-semibold item-total">0.00 ر.س</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="حذف السطر"><i class="bi bi-trash"></i></button>
            </td>
        `;

        tbody.appendChild(tr);
        rowIndex++;
        calculateTotals();
    }

    document.getElementById('addRowBtn').addEventListener('click', () => addRow());

    tbody.addEventListener('click', function (e) {
        if (e.target.closest('.remove-row-btn')) {
            const tr = e.target.closest('tr');
            if (tbody.children.length > 1) {
                tr.remove();
                calculateTotals();
            }
        }
    });

    tbody.addEventListener('input', calculateTotals);
    document.getElementById('discount_amount').addEventListener('input', calculateTotals);

    function calculateTotals() {
        let subtotal = 0;
        let totalTax = 0;

        tbody.querySelectorAll('tr').forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const cost = parseFloat(row.querySelector('.item-cost').value) || 0;
            const taxPct = parseFloat(row.querySelector('.item-tax').value) || 0;

            const lineSub = qty * cost;
            const lineTax = lineSub * (taxPct / 100);

            row.querySelector('.item-total').textContent = lineSub.toFixed(2) + ' ر.س';

            subtotal += lineSub;
            totalTax += lineTax;
        });

        const discount = parseFloat(document.getElementById('discount_amount').value) || 0;
        const total = Math.max(0, subtotal + totalTax - discount);

        document.getElementById('dispSubtotal').textContent = subtotal.toFixed(2) + ' ر.س';
        document.getElementById('dispTax').textContent = totalTax.toFixed(2) + ' ر.س';
        document.getElementById('dispTotal').textContent = total.toFixed(2) + ' ر.س';
    }

    if (existingItems && existingItems.length > 0) {
        existingItems.forEach(item => {
            addRow(item.product_id, item.quantity, item.unit_cost, item.tax_percent);
        });
    } else {
        addRow();
    }
});
</script>
@endsection
