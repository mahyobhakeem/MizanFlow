<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'MizanFlow') }} - @yield('title', 'لوحة التحكم')</title>

    <!-- Local Bundled Assets (Vite) - Zero CDN dependency -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar -->
        @include('layouts.partials.sidebar')

        <!-- Mobile Sidebar Backdrop -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- Main Workspace Area -->
        <div class="app-main">
            <!-- Navbar -->
            <header class="app-navbar">
                <!-- Right Side: Sidebar Toggle (mobile) + Active Warehouse Context -->
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <button class="navbar-action-btn d-lg-none" id="sidebarToggle" type="button" aria-label="تبديل القائمة">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    
                    <!-- Contextual Warehouse Link -->
                    <a href="{{ route('inventory.balances') }}" class="warehouse-context-pill" title="عرض أرصدة وحركات المستودع النشط">
                        <i class="bi bi-building"></i>
                        <span class="text-truncate" style="max-width: 170px;">{{ auth()->user()->warehouse?->name ?? 'المستودع الرئيسي (المركزي)' }}</span>
                        <span class="warehouse-hint d-none d-xxl-inline">الأرصدة</span>
                    </a>
                </div>

                <!-- Center: Command Search Trigger (Desktop >= 1200px) -->
                <div class="d-none d-xl-flex align-items-center flex-grow-1 mx-2 justify-content-center" style="min-width: 0; max-width: 360px;">
                    <button type="button" class="navbar-search-btn" id="commandPaletteTrigger" title="البحث السريع (Ctrl + K)">
                        <span class="d-flex align-items-center gap-2 text-truncate me-2">
                            <i class="bi bi-search text-muted flex-shrink-0"></i>
                            <span class="text-truncate d-none d-xxl-inline">بحث سريع، شاشات، أصناف، أو عمليات...</span>
                            <span class="text-truncate d-inline d-xxl-none">بحث سريع...</span>
                        </span>
                        <kbd class="d-none d-sm-inline-block">Ctrl + K</kbd>
                    </button>
                </div>

                <!-- Left Side: Quick Actions, Search (mobile), Alerts, User Menu -->
                <div class="d-flex align-items-center gap-1 gap-sm-2 flex-shrink-0">
                    <!-- Search Trigger on screens < 1200px -->
                    <button class="navbar-action-btn d-xl-none" id="mobileSearchTrigger" type="button" title="بحث سريع (Ctrl + K)" aria-label="بحث سريع">
                        <i class="bi bi-search"></i>
                    </button>

                    <!-- Direct POS Button (Fast Sales Shortcut on large screens >= 1400px) -->
                    <a href="{{ route('sales.create') }}" class="navbar-pos-btn d-none d-xxl-inline-flex" title="فتح نقطة البيع المباشرة (POS)">
                        <i class="bi bi-upc-scan"></i>
                        <span>نقطة البيع</span>
                    </a>

                    <!-- Quick Actions Dropdown ("+ عملية جديدة") -->
                    <div class="dropdown">
                        <button class="navbar-quick-create-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="إجراء سريع جديد">
                            <i class="bi bi-plus-lg"></i>
                            <span class="d-none d-sm-inline">عملية جديدة</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-2 py-1" style="min-width: 220px; border-radius: var(--mz-radius-lg);">
                            <li class="dropdown-header py-1 text-muted small fw-medium">عمليات سريعة فورية</li>
                            <li>
                                <a href="{{ route('sales.create') }}" class="dropdown-item d-flex align-items-center justify-content-between py-2">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="bi bi-upc-scan text-success fs-6"></i>
                                        <span>نقطة البيع (POS)</span>
                                    </span>
                                    <span class="badge bg-success-subtle text-success" style="font-size: 0.65rem;">فوري</span>
                                </a>
                            </li>
                            @if(auth()->user()->hasRole(['admin', 'manager']))
                            <li>
                                <a href="{{ route('purchases.create') }}" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                    <i class="bi bi-cart-plus text-primary fs-6"></i>
                                    <span>فاتورة شراء وتوريد</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('products.create') }}" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                    <i class="bi bi-tag text-secondary fs-6"></i>
                                    <span>إضافة منتج / صنف</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('transfers.create') }}" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                    <i class="bi bi-arrow-left-right text-info fs-6"></i>
                                    <span>تحويل بين المستودعات</span>
                                </a>
                            </li>
                            @endif
                            <li>
                                <a href="{{ route('customers.create') }}" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                    <i class="bi bi-person-plus text-secondary fs-6"></i>
                                    <span>تسجيل عميل جديد</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Notifications Indicator -->
                    @php
                        $alertSummary = app(\App\Services\Alert\AlertService::class)->getSummary();
                        $alertCount = $alertSummary['total_count'];
                    @endphp
                    <a href="{{ route('alerts.index') }}" class="navbar-action-btn position-relative" title="مركز التنبيهات ({{ $alertCount }} تنبيه)" aria-label="مركز التنبيهات">
                        <i class="bi bi-bell"></i>
                        @if($alertCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill {{ $alertSummary['critical_count'] > 0 ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 0.65rem; padding: 0.2em 0.45em;">
                                {{ $alertCount }}
                            </span>
                        @endif
                    </a>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <button class="user-menu-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="قائمة المستخدم">
                            <div class="user-avatar">
                                {{ mb_substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <span class="fw-medium small text-dark d-none d-md-inline text-truncate" style="max-width: 100px;" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</span>
                            <i class="bi bi-chevron-down text-muted" style="font-size: 0.65rem;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border mt-2 py-1" style="min-width: 220px; border-radius: var(--mz-radius-lg);">
                            <li class="dropdown-header py-2">
                                <div class="fw-bold text-dark">{{ auth()->user()->name }}</div>
                                <small class="text-muted font-monospace">{{ auth()->user()->email }}</small>
                                <div class="mt-1">
                                    <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;">{{ auth()->user()->role_label }}</span>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a href="{{ route('dashboard') }}" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                    <i class="bi bi-speedometer2 text-muted"></i>
                                    <span>لوحة المؤشرات التشغيلية</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('alerts.index') }}" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                    <i class="bi bi-bell text-muted"></i>
                                    <span>مركز التنبيهات</span>
                                </a>
                            </li>
                            @if(auth()->user()->hasRole('admin'))
                            <li>
                                <a href="{{ route('audit-logs.index') }}" class="dropdown-item d-flex align-items-center gap-2 py-2">
                                    <i class="bi bi-shield-check text-muted"></i>
                                    <span>سجل الرقابة والأمان</span>
                                </a>
                            </li>
                            @endif
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger d-flex align-items-center gap-2 py-2">
                                        <i class="bi bi-box-arrow-left"></i>
                                        <span>تسجيل الخروج من المنظومة</span>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="app-content">
                <!-- Floating Toast Notifications (Non-disruptive, Global Standard) -->
                @if (session('success') || session('error') || session('warning') || session('info'))
                    <div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 2050; margin-top: 1rem;">
                        @if (session('success'))
                            <div class="toast show align-items-center text-bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" style="border-radius: var(--mz-radius-pill); min-width: 320px;">
                                <div class="d-flex align-items-center py-1 px-3">
                                    <i class="bi bi-check-circle-fill fs-5 me-2 ms-1"></i>
                                    <div class="toast-body flex-grow-1 py-2 px-1 text-white fw-medium" style="font-size: 0.85rem;">
                                        {{ session('success') }}
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="إغلاق"></button>
                                </div>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="toast show align-items-center text-bg-danger border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" style="border-radius: var(--mz-radius-pill); min-width: 320px;">
                                <div class="d-flex align-items-center py-1 px-3">
                                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2 ms-1"></i>
                                    <div class="toast-body flex-grow-1 py-2 px-1 text-white fw-medium" style="font-size: 0.85rem;">
                                        {{ session('error') }}
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="إغلاق"></button>
                                </div>
                            </div>
                        @endif

                        @if (session('warning'))
                            <div class="toast show align-items-center text-bg-warning text-dark border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" style="border-radius: var(--mz-radius-pill); min-width: 320px;">
                                <div class="d-flex align-items-center py-1 px-3">
                                    <i class="bi bi-exclamation-circle-fill fs-5 me-2 ms-1"></i>
                                    <div class="toast-body flex-grow-1 py-2 px-1 fw-medium" style="font-size: 0.85rem;">
                                        {{ session('warning') }}
                                    </div>
                                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="إغلاق"></button>
                                </div>
                            </div>
                        @endif

                        @if (session('info'))
                            <div class="toast show align-items-center text-bg-primary border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" style="border-radius: var(--mz-radius-pill); min-width: 320px;">
                                <div class="d-flex align-items-center py-1 px-3">
                                    <i class="bi bi-info-circle-fill fs-5 me-2 ms-1"></i>
                                    <div class="toast-body flex-grow-1 py-2 px-1 text-white fw-medium" style="font-size: 0.85rem;">
                                        {{ session('info') }}
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="إغلاق"></button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="py-3 px-4 text-muted border-top bg-white small d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <span>نظام <strong>ميزان فلو (MizanFlow)</strong> &copy; {{ date('Y') }} — منصة إدارة الأعمال والتجارة الموحدة.</span>
                </div>
                <div class="d-flex align-items-center gap-3 text-secondary" style="font-size: 0.775rem;">
                    <span>الإصدار 1.0 LTS</span>
                    <span class="text-muted">•</span>
                    <span>WAC Moving Average Engine</span>
                    <span class="text-muted">•</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">نظام متصل</span>
                </div>
            </footer>
        </div>
    </div>

    <!-- Command Palette (Ctrl + K) Dialog -->
    <div class="command-palette-backdrop" id="commandPalette" role="dialog" aria-modal="true" aria-label="لوحة الأوامر السريعة">
        <div class="command-palette-dialog">
            <div class="command-palette-header">
                <i class="bi bi-search text-primary fs-5"></i>
                <input type="text" class="command-palette-input" id="commandPaletteInput" placeholder="اكتب للبحث عن شاشة، أمر، أو صنف..." autocomplete="off">
                <kbd class="badge bg-light text-muted border">ESC للإغلاق</kbd>
            </div>
            <div class="command-palette-list" id="commandPaletteList">
                <div class="command-palette-group" data-group="main">
                    <div class="command-palette-group-title">الشاشات والعمليات الرئيسية</div>
                    <a href="{{ route('dashboard') }}" class="command-palette-item">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-speedometer2 text-primary"></i> <span>لوحة المؤشرات التشغيلية</span></span>
                        <small class="text-muted">الرئيسية</small>
                    </a>
                    <a href="{{ route('sales.create') }}" class="command-palette-item">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-upc-scan text-success"></i> <span>نقطة البيع (POS) — فاتورة جديدة</span></span>
                        <span class="badge bg-success-subtle text-success">بيع فوري</span>
                    </a>
                    <a href="{{ route('sales.index') }}" class="command-palette-item">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-receipt text-primary"></i> <span>فواتير المبيعات</span></span>
                        <small class="text-muted">المبيعات</small>
                    </a>
                    @if(auth()->user()->hasRole(['admin', 'manager']))
                    <a href="{{ route('purchases.create') }}" class="command-palette-item">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-cart-plus text-warning"></i> <span>فاتورة شراء وتوريد جديدة</span></span>
                        <span class="badge bg-warning-subtle text-warning">توريد WAC</span>
                    </a>
                    <a href="{{ route('purchases.index') }}" class="command-palette-item">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-truck text-muted"></i> <span>فواتير المشتريات والموردين</span></span>
                        <small class="text-muted">المشتريات</small>
                    </a>
                    @endif
                </div>

                <div class="command-palette-group" data-group="inventory">
                    <div class="command-palette-group-title">المخازن والمستودعات</div>
                    <a href="{{ route('products.index') }}" class="command-palette-item">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-tags text-primary"></i> <span>كتالوج المنتجات والأصناف</span></span>
                        <small class="text-muted">المنتجات</small>
                    </a>
                    <a href="{{ route('inventory.balances') }}" class="command-palette-item">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-boxes text-info"></i> <span>أرصدة المستودعات وتقييم المخزون</span></span>
                        <small class="text-muted">الأرصدة</small>
                    </a>
                    <a href="{{ route('reports.inventory') }}" class="command-palette-item">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-pie-chart text-success"></i> <span>تقرير تقييم المخزون وحد الطلب</span></span>
                        <small class="text-muted">التقارير</small>
                    </a>
                    <a href="{{ route('inventory.movements') }}" class="command-palette-item">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-clock-history text-muted"></i> <span>سجل حركات دفتر الأستاذ</span></span>
                        <small class="text-muted">الحركات</small>
                    </a>
                </div>

                <div class="command-palette-group" data-group="monitoring">
                    <div class="command-palette-group-title">الرقابة والعملاء</div>
                    <a href="{{ route('customers.index') }}" class="command-palette-item">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-people text-primary"></i> <span>دليل العملاء وسقوف الائتمان</span></span>
                        <small class="text-muted">العملاء</small>
                    </a>
                    <a href="{{ route('alerts.index') }}" class="command-palette-item">
                        <span class="d-flex align-items-center gap-2"><i class="bi bi-bell text-danger"></i> <span>مركز التنبيهات التشغيلية</span></span>
                        <span class="badge bg-danger-subtle text-danger">متابعة فورية</span>
                    </a>
                </div>
            </div>
            <div class="command-palette-footer">
                <span class="d-flex align-items-center gap-2">
                    <i class="bi bi-keyboard"></i>
                    <span>استخدم لوحة المفاتيح للتنقل السريع</span>
                </span>
                <span class="d-flex align-items-center gap-2">
                    <kbd class="badge bg-white text-dark border">↵ للفتح</kbd>
                    <kbd class="badge bg-white text-dark border">ESC للإغلاق</kbd>
                </span>
            </div>
        </div>
    </div>
</body>
</html>
