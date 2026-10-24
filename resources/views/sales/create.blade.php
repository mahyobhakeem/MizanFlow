@extends('layouts.app')

@section('title', 'إنشاء فاتورة مبيعات / نقطة بيع')

@section('content')
<x-page-header 
    title="نقطة البيع وفاتورة مبيعات جديدة" 
    subtitle="تسجيل عمليات البيع، الفوترة الفورية المباشرة (POS)، أو حفظ مسودات العروض"
    :breadcrumbs="[
        ['label' => 'المبيعات والعملاء'],
        ['label' => 'فواتير المبيعات', 'url' => route('sales.index')],
        ['label' => 'نقطة بيع وفاتورة جديدة']
    ]">
    <x-slot:actions>
        <a href="{{ route('sales.index') }}" class="btn btn-mz-outline">
            <i class="bi bi-arrow-right"></i>
            <span>العودة للفواتير</span>
        </a>
    </x-slot:actions>
</x-page-header>

<div class="mz-card">
    <div class="mz-card-body p-3 p-md-4">
        <form method="POST" action="{{ route('sales.store') }}" id="saleForm">
            @csrf

            <!-- بيانات رأس الفاتورة الأساسية -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="warehouse_id" class="form-label">
                        <i class="bi bi-building text-primary me-1"></i>
                        المستودع المصرف منه <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('warehouse_id') is-invalid @enderror" id="warehouse_id" name="warehouse_id" required>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ old('warehouse_id', $defaultWarehouseId) == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('warehouse_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="customer_id" class="form-label">
                        <i class="bi bi-person-check text-primary me-1"></i>
                        العميل (نقدي أو آجل)
                    </label>
                    <select class="form-select @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id">
                        <option value="">— عميل نقدي عابر —</option>
                        @foreach($customers as $cust)
                            <option value="{{ $cust->id }}" 
                                    data-balance="{{ $cust->balance }}" 
                                    data-limit="{{ $cust->credit_limit }}"
                                    {{ old('customer_id') == $cust->id ? 'selected' : '' }}>
                                {{ $cust->name }} ({{ $cust->code }})
                            </option>
                        @endforeach
                    </select>
                    <div id="customerCreditNotice" class="form-text mt-1 text-primary small d-none"></div>
                    @error('customer_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="invoice_date" class="form-label">
                        <i class="bi bi-calendar-event text-primary me-1"></i>
                        تاريخ ووقت الفاتورة <span class="text-danger">*</span>
                    </label>
                    <input type="datetime-local" class="form-control @error('invoice_date') is-invalid @enderror" id="invoice_date" name="invoice_date" value="{{ old('invoice_date', date('Y-m-d\TH:i')) }}" required>
                    @error('invoice_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <label for="payment_type" class="form-label">
                        <i class="bi bi-credit-card text-primary me-1"></i>
                        طريقة الدفع <span class="text-danger">*</span>
                    </label>
                    <select class="form-select @error('payment_type') is-invalid @enderror" id="payment_type" name="payment_type" required>
                        <option value="cash" {{ old('payment_type', 'cash') === 'cash' ? 'selected' : '' }}>نقدي (Cash)</option>
                        <option value="card" {{ old('payment_type') === 'card' ? 'selected' : '' }}>شبكة / بطاقة (Card)</option>
                        <option value="credit" {{ old('payment_type') === 'credit' ? 'selected' : '' }}>آجل (Credit)</option>
                        <option value="split" {{ old('payment_type') === 'split' ? 'selected' : '' }}>دفع مجزأ (Split)</option>
                    </select>
                    @error('payment_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- جدول بنود الأصناف مع شريط البحث السريع والباركود المتقدم -->
            <div class="card border mb-4" style="border-radius: var(--mz-radius-lg); overflow: visible;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-bold text-dark"><i class="bi bi-box-seam text-primary me-1"></i> أصناف فاتورة المبيعات</span>
                        <span class="badge bg-light text-secondary border font-monospace" id="itemsCountBadge">1 صنف</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
                        <i class="bi bi-plus-circle me-1"></i> إضافة سطر جديد
                    </button>
                </div>

                <!-- شريط البحث السريع والباركود المباشر للكاشير (POS Quick Bar) -->
                <div class="p-3 border-bottom" style="background-color: var(--mz-surface-subtle); overflow: visible;">
                    <div class="row g-2 align-items-center">
                        <div class="col-lg-7 col-md-8 pos-search-wrapper">
                            <label for="posProductSearch" class="form-label d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold text-dark small">
                                    <i class="bi bi-upc-scan text-primary me-1"></i> البحث السريع والإضافة الفورية للصنف بالفاتورة:
                                </span>
                                <span class="text-muted small" style="font-size: 0.75rem;">اكتب اسم المنتج أو الرمز أو امسح الباركود مباشرة</span>
                            </label>

                            <div class="pos-search-input-group">
                                <div class="pos-icon-well">
                                    <i class="bi bi-search"></i>
                                </div>
                                <input type="text" 
                                       id="posProductSearch" 
                                       placeholder="امسح الباركود أو اكتب اسم الصنف (مثل: شاشة، كابل...) أو الرمز... [اضغط Enter للإضافة]" 
                                       autocomplete="off"
                                       aria-label="البحث عن منتج">
                                <button type="button" id="posClearSearchBtn" class="pos-search-clear-btn d-none" title="مسح حقل البحث">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-mz-primary px-3 rounded-pill" id="posAddProductBtn">
                                    <i class="bi bi-plus-lg me-1"></i> إضافة للبند
                                </button>
                            </div>
                            
                            <!-- قائمة النتائج الفورية المتطابقة (Floating Results Dropdown) -->
                            <div id="posSearchResults" class="pos-search-dropdown-menu d-none">
                            </div>
                        </div>
                        
                        <div class="col-lg-5 col-md-4 d-flex justify-content-md-end align-items-center gap-2 pt-md-3 text-muted small flex-wrap">
                            <span class="badge bg-white text-secondary border py-1 px-2">
                                <i class="bi bi-upc text-primary me-1"></i> ماسح الباركود نشط
                            </span>
                            <span class="badge bg-white text-secondary border py-1 px-2" title="اضغط F2 للتركيز الفوري على البحث">
                                <kbd class="mz-kbd">F2</kbd> بحث سريع
                            </span>
                            <span class="badge bg-white text-secondary border py-1 px-2">
                                <kbd class="mz-kbd">&darr;&uarr;</kbd> للتنقل و <kbd class="mz-kbd">Enter</kbd> للإضافة
                            </span>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0" style="overflow: visible;">
                    <div class="table-responsive" style="overflow: visible; min-height: 260px;">
                        <table class="table table-bordered mb-0 align-middle table-dense" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 38%;">المنتج / الصنف <span class="text-danger">*</span></th>
                                    <th style="width: 14%;">الكمية والوحدة <span class="text-danger">*</span></th>
                                    <th style="width: 14%;">سعر الوحدة (ر.س) <span class="text-danger">*</span></th>
                                    <th style="width: 11%;">خصم البند (ر.س)</th>
                                    <th style="width: 8%;">الضريبة %</th>
                                    <th style="width: 12%;" class="text-end">الإجمالي الصافي</th>
                                    <th style="width: 3%;" class="text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                <!-- Dynamic Rows Generated via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- الملخص المالي والملاحظات -->
            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <label for="notes" class="form-label">
                        <i class="bi bi-chat-left-text text-muted me-1"></i>
                        ملاحظات الفاتورة وشروط البيع
                    </label>
                    <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="أي شروط خاصة، أرقام تتبع، أو ملاحظات للعميل...">{{ old('notes') }}</textarea>
                </div>
                <div class="col-lg-6">
                    <div class="bg-light p-3 rounded-3 border">
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">المجموع قبل الضريبة:</span>
                            <span class="font-monospace fw-semibold" id="dispSubtotal">0.00 ر.س</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted">ضريبة القيمة المضافة (15%):</span>
                            <span class="font-monospace fw-semibold" id="dispTax">0.00 ر.س</span>
                        </div>
                        <div class="row g-2 mb-2 align-items-center">
                            <div class="col-6">
                                <label for="discount_amount" class="small text-muted mb-0">خصم إضافي على الفاتورة (ر.س):</label>
                            </div>
                            <div class="col-6">
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end font-monospace" id="discount_amount" name="discount_amount" value="{{ old('discount_amount', 0) }}">
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-2 fs-5">
                            <span class="fw-bold text-dark">الصافي الإجمالي المستحق:</span>
                            <span class="font-monospace fw-bold text-primary" id="dispTotal">0.00 ر.س</span>
                        </div>
                        <div class="row g-2 align-items-center" id="paidAmountRow">
                            <div class="col-6">
                                <label for="paid_amount" class="small text-muted mb-0" id="paidAmountLabel">المبلغ المدفوع (المحصل):</label>
                            </div>
                            <div class="col-6">
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end font-monospace" id="paid_amount" name="paid_amount" value="{{ old('paid_amount', 0) }}">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-2 pt-2 border-top small" id="balanceRow">
                            <span class="text-muted">المبلغ المتبقي (آجل على العميل):</span>
                            <span class="font-monospace fw-bold text-danger" id="dispRemaining">0.00 ر.س</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- أزرار الإجراءات -->
            <div class="d-flex flex-wrap justify-content-end gap-2 pt-3 border-top">
                <button type="submit" name="complete_immediately" value="0" class="btn btn-outline-secondary px-3">
                    <i class="bi bi-file-earmark me-1"></i> حفظ كمسودة فقط (بدون صرف مخزون)
                </button>
                <button type="submit" name="complete_immediately" value="1" class="btn btn-mz-primary px-4">
                    <i class="bi bi-check-circle-fill me-1"></i> اعتماد وصرف المخزون فوراً (POS)
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    .table-flash-highlight {
        background-color: var(--mz-surface-active) !important;
        transition: background-color 0.6s ease;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const productsData = @json($products);
    const itemsBody = document.getElementById('itemsBody');
    const addRowBtn = document.getElementById('addRowBtn');
    const warehouseSelect = document.getElementById('warehouse_id');
    const customerSelect = document.getElementById('customer_id');
    const paymentTypeSelect = document.getElementById('payment_type');
    const paidAmountInput = document.getElementById('paid_amount');
    const discountInput = document.getElementById('discount_amount');
    const customerNotice = document.getElementById('customerCreditNotice');
    const itemsCountBadge = document.getElementById('itemsCountBadge');

    // POS Fast Search Elements
    const posSearchInput = document.getElementById('posProductSearch');
    const posClearSearchBtn = document.getElementById('posClearSearchBtn');
    const posResultsDiv = document.getElementById('posSearchResults');
    const posAddBtn = document.getElementById('posAddProductBtn');

    let rowIndex = 0;

    function getProductStock(productId, warehouseId) {
        const product = productsData.find(p => p.id == productId);
        if (!product || !product.warehouse_stocks) return 0;
        const stockRow = product.warehouse_stocks.find(ws => ws.warehouse_id == warehouseId);
        return stockRow ? parseFloat(stockRow.quantity) : 0;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function updateItemsCount() {
        if (!itemsCountBadge) return;
        const count = itemsBody.querySelectorAll('tr').length;
        itemsCountBadge.textContent = `${count} ${count === 1 ? 'صنف' : 'أصناف'}`;
    }

    // =========================================================================
    // Dynamic Table Row with Enterprise Combobox Component
    // =========================================================================
    function createRow(data = null) {
        const tr = document.createElement('tr');
        const currentIndex = rowIndex++;
        const currentWhId = warehouseSelect.value;

        let selectedProduct = null;
        if (data && data.product_id) {
            selectedProduct = productsData.find(p => p.id == data.product_id);
        }

        const initialSearchVal = selectedProduct ? `${selectedProduct.name} (${selectedProduct.sku})` : '';

        tr.innerHTML = `
            <td>
                <!-- Enterprise Combobox Product Picker -->
                <div class="row-combobox" data-row="${currentIndex}">
                    <input type="hidden" 
                           name="items[${currentIndex}][product_id]" 
                           class="product-id-input" 
                           value="${data ? data.product_id : ''}" 
                           required>
                    
                    <div class="row-combobox-control ${selectedProduct ? 'has-selection' : ''}">
                        <span class="text-muted pe-1 ps-1" style="font-size: 0.85rem;">
                            <i class="bi bi-box-seam text-primary"></i>
                        </span>
                        <input type="text" 
                               class="row-combobox-input" 
                               placeholder="اختر أو ابحث عن صنف بالاسم أو SKU..." 
                               autocomplete="off"
                               value="${escapeHtml(initialSearchVal)}">
                        <button type="button" 
                                class="row-combobox-action-btn btn-clear-product ${selectedProduct ? '' : 'd-none'}" 
                                title="إلغاء التحديد وتغيير الصنف"
                                tabindex="-1">
                            <i class="bi bi-x-circle-fill text-muted"></i>
                        </button>
                        <button type="button" 
                                class="row-combobox-action-btn btn-toggle-dropdown" 
                                title="عرض كافة الأصناف"
                                tabindex="-1">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                    </div>

                    <!-- Floating Dropdown Menu -->
                    <div class="row-combobox-dropdown d-none"></div>

                    <!-- Stock Status Tag -->
                    <div class="mt-1 d-flex align-items-center gap-1 stock-info"></div>
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.001" min="0.001" name="items[${currentIndex}][quantity]" class="form-control qty-input font-monospace" value="${data ? data.quantity : 1}" required>
                    <span class="input-group-text unit-label text-muted">${data ? (data.unit ?? 'وحدة') : 'وحدة'}</span>
                </div>
            </td>
            <td>
                <input type="number" step="0.0001" min="0" name="items[${currentIndex}][unit_price]" class="form-control form-control-sm price-input font-monospace text-end" value="${data ? data.unit_price : 0.00}" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="items[${currentIndex}][discount_amount]" class="form-control form-control-sm discount-input font-monospace text-end" value="${data ? data.discount_amount : 0.00}">
            </td>
            <td>
                <input type="number" step="0.01" min="0" max="100" name="items[${currentIndex}][tax_percent]" class="form-control form-control-sm tax-input font-monospace text-center" value="${data ? data.tax_percent : 15.00}">
            </td>
            <td class="text-end font-monospace fw-semibold line-total">
                0.00 ر.س
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="حذف البند">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        itemsBody.appendChild(tr);
        updateItemsCount();

        // Bind row elements
        const productIdInput = tr.querySelector('.product-id-input');
        const comboboxInput = tr.querySelector('.row-combobox-input');
        const comboboxControl = tr.querySelector('.row-combobox-control');
        const comboboxDropdown = tr.querySelector('.row-combobox-dropdown');
        const clearProductBtn = tr.querySelector('.btn-clear-product');
        const toggleDropdownBtn = tr.querySelector('.btn-toggle-dropdown');
        const stockInfoDiv = tr.querySelector('.stock-info');
        const qtyInp = tr.querySelector('.qty-input');
        const priceInp = tr.querySelector('.price-input');
        const discInp = tr.querySelector('.discount-input');
        const taxInp = tr.querySelector('.tax-input');
        const unitLabel = tr.querySelector('.unit-label');
        const removeBtn = tr.querySelector('.remove-row-btn');

        function renderRowDropdown(query = '') {
            const q = query.trim().toLowerCase();
            const currentWhId = warehouseSelect.value;
            
            let matches = productsData;
            if (q.length > 0) {
                matches = productsData.filter(p => {
                    const name = (p.name || '').toLowerCase();
                    const sku = (p.sku || '').toLowerCase();
                    const barcode = (p.barcode || '').toLowerCase();
                    return name.includes(q) || sku.includes(q) || barcode.includes(q);
                });
            }

            if (matches.length === 0) {
                comboboxDropdown.innerHTML = `
                    <div class="p-3 text-center text-muted small">
                        <i class="bi bi-search me-1"></i> لا توجد أصناف مطابقة لـ "<strong>${escapeHtml(q)}</strong>"
                    </div>
                `;
            } else {
                let html = `
                    <div class="pos-dropdown-header">
                        <span>قائمة الأصناف (${matches.length})</span>
                        <span class="text-muted"><kbd class="mz-kbd">&darr;&uarr;</kbd> للتنقل</span>
                    </div>
                `;
                matches.slice(0, 20).forEach((p, idx) => {
                    const stock = getProductStock(p.id, currentWhId);
                    const stockBadge = stock > 0 
                        ? `<span class="stock-pill stock-pill-success">متوفر: ${stock}</span>`
                        : `<span class="stock-pill stock-pill-danger">غير متوفر (0)</span>`;
                    const isSelected = idx === 0 ? 'active' : '';

                    html += `
                        <div class="row-combobox-item ${isSelected}" data-id="${p.id}">
                            <div class="d-flex align-items-center gap-2 text-truncate" style="max-width: 65%;">
                                <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.7rem;">${escapeHtml(p.sku)}</span>
                                <span class="fw-semibold text-dark text-truncate" style="font-size: 0.835rem;">${escapeHtml(p.name)}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                ${stockBadge}
                                <span class="font-monospace fw-bold text-primary small">${parseFloat(p.default_sale_price || 0).toFixed(2)} ر.س</span>
                            </div>
                        </div>
                    `;
                });
                comboboxDropdown.innerHTML = html;
            }

            // Close other open dropdowns first
            document.querySelectorAll('.row-combobox-dropdown').forEach(dd => {
                if (dd !== comboboxDropdown) dd.classList.add('d-none');
            });
            if (posResultsDiv) posResultsDiv.classList.add('d-none');

            comboboxDropdown.classList.remove('d-none');
        }

        function updateStockBadge(productId) {
            if (!productId) {
                stockInfoDiv.innerHTML = '';
                return;
            }
            const currentWhId = warehouseSelect.value;
            const stock = getProductStock(productId, currentWhId);
            const prod = productsData.find(p => p.id == productId);
            const unit = prod?.unit?.name ?? 'وحدة';

            if (stock > 0) {
                stockInfoDiv.innerHTML = `
                    <span class="stock-pill stock-pill-success">
                        <i class="bi bi-check-circle-fill"></i> المتاح بالمستودع: ${stock} ${unit}
                    </span>
                `;
            } else {
                stockInfoDiv.innerHTML = `
                    <span class="stock-pill stock-pill-danger">
                        <i class="bi bi-exclamation-octagon-fill"></i> المتاح بالمستودع: 0 (غير متوفر)
                    </span>
                `;
            }
        }

        function selectProduct(productId, autoFocusQty = true) {
            const prod = productsData.find(p => p.id == productId);
            if (!prod) return;

            productIdInput.value = prod.id;
            comboboxInput.value = `${prod.name} (${prod.sku})`;
            comboboxControl.classList.add('has-selection');
            clearProductBtn.classList.remove('d-none');
            comboboxDropdown.classList.add('d-none');

            const price = parseFloat(prod.default_sale_price) || 0;
            const unit = prod.unit?.name ?? 'وحدة';

            if (!data) {
                priceInp.value = price.toFixed(4);
            }
            unitLabel.textContent = unit;
            updateStockBadge(prod.id);
            calculateTotals();

            if (autoFocusQty) {
                qtyInp.focus();
                qtyInp.select();
            }
        }

        function clearProductSelection() {
            productIdInput.value = '';
            comboboxInput.value = '';
            comboboxControl.classList.remove('has-selection');
            clearProductBtn.classList.add('d-none');
            stockInfoDiv.innerHTML = '';
            unitLabel.textContent = 'وحدة';
            priceInp.value = '0.00';
            calculateTotals();
            comboboxInput.focus();
            renderRowDropdown('');
        }

        // Combobox input events
        comboboxInput.addEventListener('focus', function () {
            renderRowDropdown(this.value);
        });

        comboboxInput.addEventListener('input', function () {
            if (productIdInput.value && this.value.trim() === '') {
                productIdInput.value = '';
                comboboxControl.classList.remove('has-selection');
                clearProductBtn.classList.add('d-none');
                stockInfoDiv.innerHTML = '';
            }
            renderRowDropdown(this.value);
        });

        comboboxInput.addEventListener('keydown', function (e) {
            const items = comboboxDropdown.querySelectorAll('.row-combobox-item');
            let activeItem = comboboxDropdown.querySelector('.row-combobox-item.active');
            let activeIdx = Array.from(items).indexOf(activeItem);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (comboboxDropdown.classList.contains('d-none')) {
                    renderRowDropdown(this.value);
                    return;
                }
                if (items.length > 0) {
                    if (activeItem) activeItem.classList.remove('active');
                    activeIdx = (activeIdx + 1) % items.length;
                    items[activeIdx].classList.add('active');
                    items[activeIdx].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (items.length > 0) {
                    if (activeItem) activeItem.classList.remove('active');
                    activeIdx = (activeIdx - 1 + items.length) % items.length;
                    items[activeIdx].classList.add('active');
                    items[activeIdx].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'Enter') {
                if (!comboboxDropdown.classList.contains('d-none')) {
                    e.preventDefault();
                    const chosen = activeItem || items[0];
                    if (chosen) {
                        selectProduct(chosen.dataset.id);
                    }
                }
            } else if (e.key === 'Escape') {
                comboboxDropdown.classList.add('d-none');
            }
        });

        // Toggle dropdown button
        toggleDropdownBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (comboboxDropdown.classList.contains('d-none')) {
                comboboxInput.focus();
                renderRowDropdown(comboboxInput.value);
            } else {
                comboboxDropdown.classList.add('d-none');
            }
        });

        // Clear button
        clearProductBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            clearProductSelection();
        });

        // Item click inside dropdown
        comboboxDropdown.addEventListener('click', function (e) {
            const item = e.target.closest('.row-combobox-item');
            if (item) {
                selectProduct(item.dataset.id);
            }
        });

        qtyInp.addEventListener('input', calculateTotals);
        priceInp.addEventListener('input', calculateTotals);
        discInp.addEventListener('input', calculateTotals);
        taxInp.addEventListener('input', calculateTotals);

        removeBtn.addEventListener('click', function () {
            if (itemsBody.querySelectorAll('tr').length > 1) {
                tr.remove();
                updateItemsCount();
                calculateTotals();
            } else {
                alert('يجب الإبقاء على صنف واحد على الأقل في الفاتورة.');
            }
        });

        if (selectedProduct) {
            selectProduct(selectedProduct.id, false);
        }
    }

    // =========================================================================
    // POS Quick Product Search & Auto-Add Mechanism
    // =========================================================================
    function renderPosSearchResults(query) {
        const q = query.trim().toLowerCase();
        if (q.length === 0) {
            posResultsDiv.classList.add('d-none');
            posResultsDiv.innerHTML = '';
            posClearSearchBtn.classList.add('d-none');
            return;
        }

        posClearSearchBtn.classList.remove('d-none');
        const currentWhId = warehouseSelect.value;
        const matches = productsData.filter(p => {
            const name = (p.name || '').toLowerCase();
            const sku = (p.sku || '').toLowerCase();
            const barcode = (p.barcode || '').toLowerCase();
            return name.includes(q) || sku.includes(q) || barcode.includes(q);
        });

        if (matches.length === 0) {
            posResultsDiv.innerHTML = `
                <div class="p-3 text-center text-muted small">
                    <i class="bi bi-search me-1"></i> لا توجد أصناف مطابقة للبحث "<strong>${escapeHtml(q)}</strong>"
                </div>
            `;
        } else {
            let html = `
                <div class="pos-dropdown-header">
                    <span><i class="bi bi-box-seam me-1"></i> نتائج البحث (${matches.length} صنف)</span>
                    <span class="text-muted"><kbd class="mz-kbd">&darr;&uarr;</kbd> للتنقل &bull; <kbd class="mz-kbd">Enter</kbd> للإضافة</span>
                </div>
            `;
            matches.slice(0, 15).forEach((p, idx) => {
                const stock = getProductStock(p.id, currentWhId);
                const stockBadge = stock > 0 
                    ? `<span class="stock-pill stock-pill-success"><i class="bi bi-check-circle"></i> متوفر: ${stock}</span>`
                    : `<span class="stock-pill stock-pill-danger"><i class="bi bi-slash-circle"></i> غير متوفر (0)</span>`;
                const isSelected = idx === 0 ? 'active' : '';
                
                html += `
                    <div class="pos-dropdown-item ${isSelected} pos-result-item" 
                         data-id="${p.id}" 
                         style="cursor: pointer;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="code-tag font-monospace">${escapeHtml(p.sku)}</span>
                            <div>
                                <div class="fw-bold text-dark pos-item-name" style="font-size: 0.885rem;">${escapeHtml(p.name)}</div>
                                ${p.barcode ? `<div class="text-muted small font-monospace" style="font-size: 0.72rem;"><i class="bi bi-upc me-1"></i>${escapeHtml(p.barcode)}</div>` : ''}
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            ${stockBadge}
                            <div class="text-end">
                                <span class="font-monospace fw-bold text-primary" style="font-size: 0.95rem;">${parseFloat(p.default_sale_price || 0).toFixed(2)}</span>
                                <span class="currency-symbol">ر.س</span>
                            </div>
                            <span class="badge bg-light text-secondary border font-monospace ms-1 d-none d-sm-inline" style="font-size: 0.68rem;">↵ إضافة</span>
                        </div>
                    </div>
                `;
            });
            posResultsDiv.innerHTML = html;
        }

        // Close any row dropdowns
        document.querySelectorAll('.row-combobox-dropdown').forEach(dd => dd.classList.add('d-none'));
        posResultsDiv.classList.remove('d-none');
    }

    function addProductToInvoice(productId) {
        const product = productsData.find(p => p.id == productId);
        if (!product) return;

        const rows = Array.from(itemsBody.querySelectorAll('tr'));
        
        // 1. Check if the product already exists in the table -> Increment qty
        let existingRow = null;
        for (const tr of rows) {
            const prodInput = tr.querySelector('.product-id-input');
            if (prodInput && prodInput.value == productId) {
                existingRow = tr;
                break;
            }
        }

        if (existingRow) {
            const qtyInp = existingRow.querySelector('.qty-input');
            const currentQty = parseFloat(qtyInp.value) || 0;
            qtyInp.value = (currentQty + 1).toFixed(currentQty % 1 === 0 ? 0 : 3);
            
            existingRow.classList.add('table-flash-highlight');
            setTimeout(() => existingRow.classList.remove('table-flash-highlight'), 800);
            
            qtyInp.focus();
            qtyInp.select();
            calculateTotals();
        } else {
            // 2. Check if the table has an empty/unselected first row
            let emptyRow = null;
            for (const tr of rows) {
                const prodInput = tr.querySelector('.product-id-input');
                if (prodInput && !prodInput.value) {
                    emptyRow = tr;
                    break;
                }
            }

            if (emptyRow) {
                const prodInput = emptyRow.querySelector('.product-id-input');
                const comboboxInput = emptyRow.querySelector('.row-combobox-input');
                const comboboxControl = emptyRow.querySelector('.row-combobox-control');
                const clearBtn = emptyRow.querySelector('.btn-clear-product');
                const priceInp = emptyRow.querySelector('.price-input');
                const unitLabel = emptyRow.querySelector('.unit-label');
                const stockInfoDiv = emptyRow.querySelector('.stock-info');

                prodInput.value = product.id;
                comboboxInput.value = `${product.name} (${product.sku})`;
                comboboxControl.classList.add('has-selection');
                clearBtn.classList.remove('d-none');

                priceInp.value = parseFloat(product.default_sale_price || 0).toFixed(4);
                unitLabel.textContent = product.unit?.name ?? 'وحدة';

                const currentWhId = warehouseSelect.value;
                const stock = getProductStock(product.id, currentWhId);
                if (stock > 0) {
                    stockInfoDiv.innerHTML = `<span class="stock-pill stock-pill-success"><i class="bi bi-check-circle-fill"></i> المتاح: ${stock}</span>`;
                } else {
                    stockInfoDiv.innerHTML = `<span class="stock-pill stock-pill-danger"><i class="bi bi-exclamation-octagon-fill"></i> غير متوفر (0)</span>`;
                }

                emptyRow.classList.add('table-flash-highlight');
                setTimeout(() => emptyRow.classList.remove('table-flash-highlight'), 800);
                
                const qtyInp = emptyRow.querySelector('.qty-input');
                if (qtyInp) {
                    qtyInp.focus();
                    qtyInp.select();
                }
            } else {
                // 3. Create a brand new row with this product
                createRow({
                    product_id: product.id,
                    quantity: 1,
                    unit_price: parseFloat(product.default_sale_price || 0),
                    unit: product.unit?.name ?? 'وحدة',
                    tax_percent: 15.00,
                    discount_amount: 0
                });

                const allRows = itemsBody.querySelectorAll('tr');
                const lastRow = allRows[allRows.length - 1];
                if (lastRow) {
                    lastRow.classList.add('table-flash-highlight');
                    setTimeout(() => lastRow.classList.remove('table-flash-highlight'), 800);
                    const qtyInp = lastRow.querySelector('.qty-input');
                    if (qtyInp) {
                        qtyInp.focus();
                        qtyInp.select();
                    }
                }
            }
            calculateTotals();
        }

        // Reset search bar and hide results
        posSearchInput.value = '';
        posClearSearchBtn.classList.add('d-none');
        posResultsDiv.classList.add('d-none');
        posResultsDiv.innerHTML = '';
    }

    // POS Search Input Events
    posSearchInput.addEventListener('input', function () {
        renderPosSearchResults(this.value);
    });

    posClearSearchBtn.addEventListener('click', function () {
        posSearchInput.value = '';
        renderPosSearchResults('');
        posSearchInput.focus();
    });

    posSearchInput.addEventListener('keydown', function (e) {
        const items = posResultsDiv.querySelectorAll('.pos-result-item');
        let activeItem = posResultsDiv.querySelector('.pos-result-item.active');
        let activeIdx = Array.from(items).indexOf(activeItem);

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length > 0) {
                if (activeItem) activeItem.classList.remove('active');
                activeIdx = (activeIdx + 1) % items.length;
                items[activeIdx].classList.add('active');
                items[activeIdx].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (items.length > 0) {
                if (activeItem) activeItem.classList.remove('active');
                activeIdx = (activeIdx - 1 + items.length) % items.length;
                items[activeIdx].classList.add('active');
                items[activeIdx].scrollIntoView({ block: 'nearest' });
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (!posResultsDiv.classList.contains('d-none') && items.length > 0) {
                const target = activeItem || items[0];
                if (target) {
                    addProductToInvoice(target.dataset.id);
                }
            } else if (posSearchInput.value.trim().length > 0) {
                const q = posSearchInput.value.trim().toLowerCase();
                const matched = productsData.find(p => 
                    (p.barcode && p.barcode.toLowerCase() === q) ||
                    (p.sku && p.sku.toLowerCase() === q) ||
                    (p.name && p.name.toLowerCase().includes(q))
                );
                if (matched) {
                    addProductToInvoice(matched.id);
                }
            }
        } else if (e.key === 'Escape') {
            posResultsDiv.classList.add('d-none');
        }
    });

    posResultsDiv.addEventListener('click', function (e) {
        const item = e.target.closest('.pos-result-item');
        if (item) {
            addProductToInvoice(item.dataset.id);
        }
    });

    posAddBtn.addEventListener('click', function () {
        const activeItem = posResultsDiv.querySelector('.pos-result-item.active') || 
                           posResultsDiv.querySelector('.pos-result-item');
        if (activeItem) {
            addProductToInvoice(activeItem.dataset.id);
        } else if (posSearchInput.value.trim().length > 0) {
            const q = posSearchInput.value.trim().toLowerCase();
            const matched = productsData.find(p => 
                (p.barcode && p.barcode.toLowerCase() === q) ||
                (p.sku && p.sku.toLowerCase() === q) ||
                (p.name && p.name.toLowerCase().includes(q))
            );
            if (matched) {
                addProductToInvoice(matched.id);
            }
        }
    });

    // Global shortcut F2 to focus POS search bar from anywhere on the screen
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F2') {
            e.preventDefault();
            posSearchInput.focus();
            posSearchInput.select();
        }
    });

    // Close floating menus on outside click
    document.addEventListener('click', function (e) {
        if (!posSearchInput.contains(e.target) && !posResultsDiv.contains(e.target)) {
            posResultsDiv.classList.add('d-none');
        }
        document.querySelectorAll('.row-combobox').forEach(cb => {
            if (!cb.contains(e.target)) {
                const dd = cb.querySelector('.row-combobox-dropdown');
                if (dd) dd.classList.add('d-none');
            }
        });
    });

    function calculateTotals() {
        let subtotal = 0;
        let totalTax = 0;

        const rows = itemsBody.querySelectorAll('tr');
        rows.forEach(tr => {
            const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
            const price = parseFloat(tr.querySelector('.price-input').value) || 0;
            const disc = parseFloat(tr.querySelector('.discount-input').value) || 0;
            const taxPercent = parseFloat(tr.querySelector('.tax-input').value) || 0;

            const lineSub = Math.max(0, (qty * price) - disc);
            const lineTax = lineSub * (taxPercent / 100);

            subtotal += lineSub;
            totalTax += lineTax;

            tr.querySelector('.line-total').textContent = (lineSub + lineTax).toFixed(2) + ' ر.س';
        });

        const orderDiscount = parseFloat(discountInput.value) || 0;
        const grandTotal = Math.max(0, subtotal + totalTax - orderDiscount);

        document.getElementById('dispSubtotal').textContent = subtotal.toFixed(2) + ' ر.س';
        document.getElementById('dispTax').textContent = totalTax.toFixed(2) + ' ر.س';
        document.getElementById('dispTotal').textContent = grandTotal.toFixed(2) + ' ر.س';

        // Payment type logic
        const paymentType = paymentTypeSelect.value;
        if (paymentType === 'cash' || paymentType === 'card') {
            paidAmountInput.value = grandTotal.toFixed(2);
            paidAmountInput.readOnly = true;
            document.getElementById('dispRemaining').textContent = '0.00 ر.س';
        } else if (paymentType === 'credit') {
            paidAmountInput.value = '0.00';
            paidAmountInput.readOnly = true;
            document.getElementById('dispRemaining').textContent = grandTotal.toFixed(2) + ' ر.س';
        } else { // split
            paidAmountInput.readOnly = false;
            const paid = parseFloat(paidAmountInput.value) || 0;
            const remaining = Math.max(0, grandTotal - paid);
            document.getElementById('dispRemaining').textContent = remaining.toFixed(2) + ' ر.س';
        }
    }

    function updateCustomerInfo() {
        const selectedOpt = customerSelect.selectedOptions[0];
        if (selectedOpt && selectedOpt.value) {
            const balance = parseFloat(selectedOpt.dataset.balance) || 0;
            const limit = parseFloat(selectedOpt.dataset.limit) || 0;
            customerNotice.classList.remove('d-none');
            let text = `مديونية العميل الحالية: ${balance.toFixed(2)} ر.س`;
            if (limit > 0) {
                text += ` | سقف الائتمان: ${limit.toFixed(2)} ر.س (المتبقي للائتمان: ${Math.max(0, limit - balance).toFixed(2)} ر.س)`;
            }
            customerNotice.textContent = text;
        } else {
            customerNotice.classList.add('d-none');
            customerNotice.textContent = '';
        }
    }

    // When warehouse changes, update live stock in all rows
    warehouseSelect.addEventListener('change', function () {
        const currentWhId = warehouseSelect.value;
        itemsBody.querySelectorAll('tr').forEach(tr => {
            const prodInput = tr.querySelector('.product-id-input');
            const stockInfoDiv = tr.querySelector('.stock-info');
            if (prodInput && prodInput.value) {
                const stock = getProductStock(prodInput.value, currentWhId);
                const prod = productsData.find(p => p.id == prodInput.value);
                const unit = prod?.unit?.name ?? 'وحدة';
                if (stock > 0) {
                    stockInfoDiv.innerHTML = `<span class="stock-pill stock-pill-success"><i class="bi bi-check-circle-fill"></i> المتاح: ${stock} ${unit}</span>`;
                } else {
                    stockInfoDiv.innerHTML = `<span class="stock-pill stock-pill-danger"><i class="bi bi-exclamation-octagon-fill"></i> المتاح: 0 (غير متوفر)</span>`;
                }
            }
        });
    });

    customerSelect.addEventListener('change', updateCustomerInfo);
    paymentTypeSelect.addEventListener('change', calculateTotals);
    paidAmountInput.addEventListener('input', calculateTotals);
    discountInput.addEventListener('input', calculateTotals);
    addRowBtn.addEventListener('click', () => createRow());

    // Initial load
    updateCustomerInfo();
    createRow();
});
</script>
@endsection
