@extends('layouts.app')

@section('title', 'إنشاء تحويل مخزني جديد')

@section('content')
<x-page-header 
    title="إنشاء تحويل مخزني جديد" 
    subtitle="نقل بضاعة بين المستودعات مع الحفاظ على اتساق التكاليف"
    :breadcrumbs="[
        ['label' => 'المخزون والتحويلات'],
        ['label' => 'أوامر التحويل', 'url' => route('transfers.index')],
        ['label' => 'إنشاء أمر تحويل']
    ]">
    <x-slot:actions>
        <a href="{{ route('transfers.index') }}" class="btn btn-mz-outline">
            <i class="bi bi-arrow-right"></i>
            <span>العودة للقائمة</span>
        </a>
    </x-slot:actions>
</x-page-header>

<div class="mz-card">
    <div class="mz-card-header">
        <i class="bi bi-arrow-left-right text-primary me-1"></i>
        <span>بيانات التحويل والمستودعات</span>
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

        <form method="POST" action="{{ route('transfers.store') }}" id="transferForm">
            @csrf

            <div class="row g-3 mb-4">
                <div class="col-md-5">
                    <label for="from_warehouse_id" class="form-label">المستودع المصدر (خصم الكمية) <span class="text-danger">*</span></label>
                    <select class="form-select @error('from_warehouse_id') is-invalid @enderror" id="from_warehouse_id" name="from_warehouse_id" required>
                        <option value="">— اختر المستودع المصدر —</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ old('from_warehouse_id', $defaultWarehouseId) == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }} ({{ $warehouse->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 text-center d-flex align-items-center justify-content-center pt-4">
                    <i class="bi bi-arrow-left fs-3 text-primary d-none d-md-block"></i>
                    <i class="bi bi-arrow-down fs-3 text-primary d-md-none"></i>
                </div>
                <div class="col-md-5">
                    <label for="to_warehouse_id" class="form-label">المستودع الوجهة (استلام الكمية) <span class="text-danger">*</span></label>
                    <select class="form-select @error('to_warehouse_id') is-invalid @enderror" id="to_warehouse_id" name="to_warehouse_id" required>
                        <option value="">— اختر المستودع الوجهة —</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ old('to_warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }} ({{ $warehouse->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- جدول بنود الأصناف المنقولة -->
            <div class="card border mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold"><i class="bi bi-box-seam me-1"></i> الأصناف المراد تحويلها</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
                        <i class="bi bi-plus-circle me-1"></i> إضافة صنف آخر
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 align-middle" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 65%;">المنتج <span class="text-danger">*</span></th>
                                    <th style="width: 25%;">الكمية المحولة <span class="text-danger">*</span></th>
                                    <th style="width: 10%;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <!-- Dynamic rows -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label for="notes" class="form-label">ملاحظات وسبب التحويل</label>
                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="مثال: تغذية فرع الشمال بمناسبة العروض...">{{ old('notes') }}</textarea>
            </div>

            <div class="mb-4 form-check bg-light p-3 rounded border">
                <input type="checkbox" class="form-check-input ms-2 me-0" id="direct_transfer" name="direct_transfer" value="1" {{ old('direct_transfer', true) ? 'checked' : '' }}>
                <label class="form-check-label fw-bold" for="direct_transfer">
                    تحويل فوري مباشر (خصم من المصدر وإيداع في الوجهة فوراً ضمن معاملة واحدة)
                </label>
                <div class="form-text text-muted ps-4">إذا أزيل التحديد، سيتم إنشاء أمر تحويل بوضع "مسودة" يتطلب الشحن ثم الاستلام اللاحق من أمين المستودع الهدف.</div>
            </div>

            <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <a href="{{ route('transfers.index') }}" class="btn btn-mz-outline">إلغاء</a>
                <button type="submit" class="btn btn-mz-primary">
                    <i class="bi bi-arrow-left-right"></i>
                    <span>تنفيذ التحويل المخزني</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const productsData = @json($products);
    const tbody = document.getElementById('itemsBody');
    let rowIndex = 0;

    function addRow(productId = '', qty = 1) {
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
                <input type="number" step="0.001" min="0.001" name="items[${rowIndex}][quantity]" value="${qty}" class="form-control font-monospace item-qty" required placeholder="أدخل الكمية">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="حذف السطر"><i class="bi bi-trash"></i></button>
            </td>
        `;

        tbody.appendChild(tr);
        rowIndex++;
    }

    document.getElementById('addRowBtn').addEventListener('click', () => addRow());

    tbody.addEventListener('click', function (e) {
        if (e.target.closest('.remove-row-btn')) {
            const tr = e.target.closest('tr');
            if (tbody.children.length > 1) {
                tr.remove();
            } else {
                alert('يجب أن يحتوي التحويل على صنف واحد على الأقل.');
            }
        }
    });

    // Initialize with 1 row
    addRow();
});
</script>
@endsection
