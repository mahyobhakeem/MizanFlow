@props(['route', 'placeholder' => 'بحث بالاسم أو الرمز...', 'extraFilters' => null])

<div class="mz-card mb-3">
    <div class="mz-card-body p-2 px-3">
        <form method="GET" action="{{ $route }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-transparent text-muted border-start-0 ps-3">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           class="form-control border-end-0" 
                           placeholder="{{ $placeholder }}" 
                           value="{{ request('search') }}">
                </div>
            </div>

            @if (isset($extraFilters))
                {{ $extraFilters }}
            @endif

            <div class="col-6 col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">جميع الحالات</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>نشط فقط</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>معطل فقط</option>
                </select>
            </div>

            <div class="col-6 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-mz-primary">
                    <i class="bi bi-funnel"></i>
                    <span>تصفية النتائج</span>
                </button>
                @if(request()->hasAny(['search', 'status', 'category_id', 'unit_id', 'role', 'customer_id', 'supplier_id', 'warehouse_id', 'type']))
                    <a href="{{ $route }}" class="btn btn-sm btn-mz-outline" title="إلغاء التصفية وإعادة الضبط">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        <span class="d-none d-sm-inline">إعادة ضبط</span>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>
