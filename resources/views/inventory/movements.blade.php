@extends('layouts.app')

@section('title', 'سجل حركات المخزون (دفتر الأستاذ)')

@section('content')
<x-page-header 
    title="سجل حركات المخزون ودفتر الأستاذ" 
    subtitle="دفتر الأستاذ المخزني العام — سجل تدقيق زمني غير قابل للمحو أو التعديل لكافة الواردات والمبيعات والتحويلات"
    :breadcrumbs="['دفتر الأستاذ المخزني' => null]"
>
    <x-slot:actions>
        <a href="{{ route('inventory.balances') }}" class="btn btn-mz-outline">
            <i class="bi bi-stack"></i>
            <span>أرصدة المستودعات (WAC)</span>
        </a>
    </x-slot:actions>
</x-page-header>

<div class="mz-card mb-3">
    <div class="mz-card-body p-2 px-3">
        <form method="GET" action="{{ route('inventory.movements') }}">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted border-start-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="اسم المنتج، SKU، أو المستودع...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">— جميع المستودعات —</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="movement_type" class="form-select form-select-sm">
                        <option value="">— جميع أنواع الحركات —</option>
                        <option value="purchase" {{ request('movement_type') === 'purchase' ? 'selected' : '' }}>فاتورة مشتريات (وارد)</option>
                        <option value="purchase_return" {{ request('movement_type') === 'purchase_return' ? 'selected' : '' }}>مردود مشتريات (صادر)</option>
                        <option value="transfer_in" {{ request('movement_type') === 'transfer_in' ? 'selected' : '' }}>تحويل مستودعي وارد</option>
                        <option value="transfer_out" {{ request('movement_type') === 'transfer_out' ? 'selected' : '' }}>تحويل مستودعي صادر</option>
                        <option value="sale" {{ request('movement_type') === 'sale' ? 'selected' : '' }}>فاتورة مبيعات (صادر)</option>
                        <option value="sale_return" {{ request('movement_type') === 'sale_return' ? 'selected' : '' }}>مردود مبيعات (وارد)</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-mz-primary flex-fill">تصفية السجل</button>
                    @if(request()->anyFilled(['search', 'warehouse_id', 'movement_type']))
                        <a href="{{ route('inventory.movements') }}" class="btn btn-sm btn-mz-outline" title="إعادة تعيين"><i class="bi bi-x-lg"></i></a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<div class="table-dense-container">
    <table class="table-dense">
        <thead>
            <tr>
                <th>رقم القيد</th>
                <th>التوقيت</th>
                <th>المستودع</th>
                <th>المنتج</th>
                <th class="text-center">نوع الحركة</th>
                <th class="text-start">الكمية</th>
                <th class="text-center">الرصيد قبل</th>
                <th class="text-center">الرصيد بعد</th>
                <th class="text-start">تكلفة الوحدة (WAC)</th>
                <th class="text-start">إجمالي القيمة</th>
                <th>المرجع</th>
                <th>المستخدم</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movements as $mov)
            <tr>
                <td><span class="code-tag">#{{ $mov->id }}</span></td>
                <td class="small text-muted num-cell">{{ $mov->created_at->format('Y-m-d H:i') }}</td>
                <td class="fw-semibold text-dark">{{ $mov->warehouse->name }}</td>
                <td>
                    <span class="fw-semibold text-dark">{{ $mov->product->name }}</span>
                    <span class="text-muted small d-block num-cell" style="font-size: 0.725rem;">SKU: {{ $mov->product->sku }}</span>
                </td>
                <td class="text-center">
                    @if($mov->movement_type === 'purchase')
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.725rem;">شراء (وارد)</span>
                    @elseif($mov->movement_type === 'transfer_in')
                        <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.725rem;">تحويل وارد</span>
                    @elseif($mov->movement_type === 'transfer_out')
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.725rem;">تحويل صادر</span>
                    @elseif($mov->movement_type === 'sale')
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.725rem;">بيع (صادر)</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.725rem;">{{ $mov->movement_type }}</span>
                    @endif
                </td>
                <td class="text-start num-cell fw-bold {{ $mov->quantity > 0 ? 'text-success' : 'text-danger' }}">
                    {{ $mov->quantity > 0 ? '+' : '' }}{{ number_format($mov->quantity, 3) }}
                </td>
                <td class="text-center num-cell text-muted">{{ number_format($mov->before_quantity, 3) }}</td>
                <td class="text-center num-cell fw-bold">{{ number_format($mov->after_quantity, 3) }}</td>
                <td class="text-start num-cell">{{ number_format($mov->unit_cost, 4) }} <span class="currency-symbol">ر.س</span></td>
                <td class="text-start num-cell fw-bold text-dark">{{ number_format($mov->total_cost, 2) }} <span class="currency-symbol">ر.س</span></td>
                <td>
                    @if($mov->reference instanceof App\Models\Purchase)
                        <a href="{{ route('purchases.show', $mov->reference_id) }}" class="code-tag text-decoration-none">
                            {{ $mov->reference->invoice_number }}
                        </a>
                    @elseif($mov->reference instanceof App\Models\StockTransfer)
                        <a href="{{ route('transfers.show', $mov->reference_id) }}" class="code-tag text-decoration-none">
                            {{ $mov->reference->transfer_number }}
                        </a>
                    @else
                        <span class="code-tag">#{{ $mov->reference_id }}</span>
                    @endif
                </td>
                <td class="small">{{ $mov->user->name }}</td>
            </tr>
            @empty
            <x-table-empty 
                icon="bi-journal-x" 
                title="لا توجد قيود مسجلة في سجل حركات المخزون حتى الآن" 
                description="يتم قيد الحركات المخزنية تلقائياً وبشكل غير قابل للإلغاء مع كل عملية توريد أو بيع أو تحويل." 
                :colspan="12" 
            />
            @endforelse
        </tbody>
    </table>
</div>

@if($movements->hasPages())
<div class="mt-3">
    {{ $movements->links('pagination::bootstrap-5') }}
</div>
@endif
@endsection
