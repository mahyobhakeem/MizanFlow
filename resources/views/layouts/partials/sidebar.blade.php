<!-- Sidebar Component - MizanFlow Enterprise Shell -->
<aside class="app-sidebar" id="appSidebar">
    <!-- Brand Header -->
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
        <div class="sidebar-brand-mark">
            <i class="bi bi-intersect"></i>
        </div>
        <div class="sidebar-brand-info">
            <div class="sidebar-brand-title">
                <span>ميزان فلو</span>
                <small>MizanFlow</small>
            </div>
            <div class="sidebar-brand-subtitle">منظومة المشتريات والمخازن</div>
        </div>
    </a>

    <!-- Navigation Menu (Collapsible Accordion) -->
    <nav class="sidebar-menu" id="appSidebarMenu" aria-label="القائمة الجانبية الرئيسية">
        
        <!-- 1. الرئيسية (Direct Link) -->
        <div class="sidebar-nav-item">
            <a href="{{ route('dashboard') }}" 
               class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span>لوحة المؤشرات التشغيلية</span>
            </a>
        </div>

        <div class="sidebar-section-divider">العمليات التجارية والفوترة</div>

        <!-- 2. المبيعات ونقاط البيع (Sales & POS) -->
        @php
            $isSalesActive = request()->routeIs('sales.*') || request()->routeIs('customers.*');
        @endphp
        <div class="sidebar-nav-group">
            <button class="sidebar-group-toggle {{ $isSalesActive ? 'active-parent' : '' }}" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#menuSales" 
                    aria-expanded="{{ $isSalesActive ? 'true' : 'false' }}" 
                    aria-controls="menuSales">
                <span class="group-title">
                    <i class="bi bi-cart-check"></i>
                    <span>المبيعات والعملاء</span>
                </span>
                <i class="bi bi-chevron-down group-chevron"></i>
            </button>
            <div class="collapse {{ $isSalesActive ? 'show' : '' }}" id="menuSales">
                <div class="sidebar-sub-menu">
                    <a href="{{ route('sales.create') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('sales.create') ? 'active' : '' }}">
                        <i class="bi bi-upc-scan"></i>
                        <span>نقطة البيع (POS)</span>
                    </a>
                    <a href="{{ route('sales.index') }}" 
                       class="sidebar-sub-link {{ (request()->routeIs('sales.*') && !request()->routeIs('sales.create')) ? 'active' : '' }}">
                        <i class="bi bi-receipt-cutoff"></i>
                        <span>فواتير المبيعات</span>
                    </a>
                    <a href="{{ route('customers.index') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                        <i class="bi bi-people"></i>
                        <span>دليل العملاء والذمم</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 3. المشتريات والموردين (Procurement) - Admin & Manager only -->
        @if(auth()->user()->hasRole(['admin', 'manager']))
        @php
            $isPurchasesActive = request()->routeIs('purchases.*') || request()->routeIs('suppliers.*');
        @endphp
        <div class="sidebar-nav-group">
            <button class="sidebar-group-toggle {{ $isPurchasesActive ? 'active-parent' : '' }}" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#menuPurchases" 
                    aria-expanded="{{ $isPurchasesActive ? 'true' : 'false' }}" 
                    aria-controls="menuPurchases">
                <span class="group-title">
                    <i class="bi bi-truck"></i>
                    <span>المشتريات والموردين</span>
                </span>
                <i class="bi bi-chevron-down group-chevron"></i>
            </button>
            <div class="collapse {{ $isPurchasesActive ? 'show' : '' }}" id="menuPurchases">
                <div class="sidebar-sub-menu">
                    <a href="{{ route('purchases.index') }}" 
                       class="sidebar-sub-link {{ (request()->routeIs('purchases.*') && !request()->routeIs('purchases.create')) ? 'active' : '' }}">
                        <i class="bi bi-card-checklist"></i>
                        <span>فواتير المشتريات</span>
                    </a>
                    <a href="{{ route('purchases.create') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('purchases.create') ? 'active' : '' }}">
                        <i class="bi bi-plus-circle"></i>
                        <span>فاتورة شراء وتوريد</span>
                    </a>
                    <a href="{{ route('suppliers.index') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                        <i class="bi bi-building"></i>
                        <span>دليل الموردين</span>
                    </a>
                </div>
            </div>
        </div>
        @endif

        <div class="sidebar-section-divider">إدارة المخزون والمستودعات</div>

        <!-- 4. المنتجات والمستودعات والمخزون (Inventory & Products) -->
        @php
            $isInventoryActive = request()->routeIs('products.*') 
                || request()->routeIs('categories.*') 
                || request()->routeIs('units.*') 
                || request()->routeIs('warehouses.*') 
                || request()->routeIs('transfers.*') 
                || request()->routeIs('inventory.*');
        @endphp
        <div class="sidebar-nav-group">
            <button class="sidebar-group-toggle {{ $isInventoryActive ? 'active-parent' : '' }}" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#menuInventory" 
                    aria-expanded="{{ $isInventoryActive ? 'true' : 'false' }}" 
                    aria-controls="menuInventory">
                <span class="group-title">
                    <i class="bi bi-boxes"></i>
                    <span>المخزون والمنتجات</span>
                </span>
                <i class="bi bi-chevron-down group-chevron"></i>
            </button>
            <div class="collapse {{ $isInventoryActive ? 'show' : '' }}" id="menuInventory">
                <div class="sidebar-sub-menu">
                    <a href="{{ route('products.index') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                        <i class="bi bi-tags"></i>
                        <span>كتالوج المنتجات والأصناف</span>
                    </a>
                    <a href="{{ route('categories.index') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                        <i class="bi bi-folder2-open"></i>
                        <span>تصنيفات الأصناف</span>
                    </a>
                    <a href="{{ route('units.index') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('units.*') ? 'active' : '' }}">
                        <i class="bi bi-rulers"></i>
                        <span>وحدات القياس</span>
                    </a>
                    <a href="{{ route('warehouses.index') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}">
                        <i class="bi bi-geo-alt"></i>
                        <span>المستودعات والفروع</span>
                    </a>

                    @if(auth()->user()->hasRole(['admin', 'manager']))
                    <a href="{{ route('transfers.index') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('transfers.*') ? 'active' : '' }}">
                        <i class="bi bi-arrow-left-right"></i>
                        <span>تحويلات المخزون</span>
                    </a>
                    <a href="{{ route('inventory.balances') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('inventory.balances') ? 'active' : '' }}">
                        <i class="bi bi-stack"></i>
                        <span>أرصدة المستودعات (WAC)</span>
                    </a>
                    <a href="{{ route('inventory.movements') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('inventory.movements') ? 'active' : '' }}">
                        <i class="bi bi-clock-history"></i>
                        <span>سجل حركات دفتر الأستاذ</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- 5. التقارير والتحليلات (Reports) - Manager & Admin only -->
        @can('viewReports', App\Models\Sale::class)
        @php
            $isReportsActive = request()->routeIs('reports.*');
        @endphp
        <div class="sidebar-section-divider">التحليلات والمراقبة</div>

        <div class="sidebar-nav-group">
            <button class="sidebar-group-toggle {{ $isReportsActive ? 'active-parent' : '' }}" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#menuReports" 
                    aria-expanded="{{ $isReportsActive ? 'true' : 'false' }}" 
                    aria-controls="menuReports">
                <span class="group-title">
                    <i class="bi bi-graph-up"></i>
                    <span>التقارير التحليلية</span>
                </span>
                <i class="bi bi-chevron-down group-chevron"></i>
            </button>
            <div class="collapse {{ $isReportsActive ? 'show' : '' }}" id="menuReports">
                <div class="sidebar-sub-menu">
                    <a href="{{ route('reports.sales') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('reports.sales') ? 'active' : '' }}">
                        <i class="bi bi-currency-dollar"></i>
                        <span>المبيعات وهوامش الأرباح</span>
                    </a>
                    <a href="{{ route('reports.inventory') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('reports.inventory') ? 'active' : '' }}">
                        <i class="bi bi-pie-chart"></i>
                        <span>تقييم وحالة المخزون</span>
                    </a>
                    <a href="{{ route('reports.movements') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('reports.movements') ? 'active' : '' }}">
                        <i class="bi bi-journal-text"></i>
                        <span>كشف حركة صنف</span>
                    </a>
                    <a href="{{ route('reports.customers') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('reports.customers') ? 'active' : '' }}">
                        <i class="bi bi-person-lines-fill"></i>
                        <span>مديونيات العملاء وسقف الائتمان</span>
                    </a>
                </div>
            </div>
        </div>
        @endcan

        <!-- 6. الرقابة والتنبيهات (Monitoring & Alerts) -->
        @php
            $isMonitoringActive = request()->routeIs('alerts.*') || request()->routeIs('audit-logs.*');
        @endphp
        <div class="sidebar-nav-group">
            <button class="sidebar-group-toggle {{ $isMonitoringActive ? 'active-parent' : '' }}" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#menuMonitoring" 
                    aria-expanded="{{ $isMonitoringActive ? 'true' : 'false' }}" 
                    aria-controls="menuMonitoring">
                <span class="group-title">
                    <i class="bi bi-shield-check"></i>
                    <span>الرقابة والامتثال</span>
                </span>
                <i class="bi bi-chevron-down group-chevron"></i>
            </button>
            <div class="collapse {{ $isMonitoringActive ? 'show' : '' }}" id="menuMonitoring">
                <div class="sidebar-sub-menu">
                    <a href="{{ route('alerts.index') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('alerts.*') ? 'active' : '' }}">
                        <i class="bi bi-bell"></i>
                        <span>مركز التنبيهات التشغيلية</span>
                    </a>
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('audit-logs.index') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
                        <i class="bi bi-shield-lock"></i>
                        <span>سجل التدقيق والأمان</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- 7. إدارة النظام والمستخدمين (Administration) - Admin only -->
        @if(auth()->user()->isAdmin())
        @php
            $isAdminActive = request()->routeIs('users.*');
        @endphp
        <div class="sidebar-nav-group">
            <button class="sidebar-group-toggle {{ $isAdminActive ? 'active-parent' : '' }}" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#menuAdmin" 
                    aria-expanded="{{ $isAdminActive ? 'true' : 'false' }}" 
                    aria-controls="menuAdmin">
                <span class="group-title">
                    <i class="bi bi-gear-wide-connected"></i>
                    <span>إدارة النظام</span>
                </span>
                <i class="bi bi-chevron-down group-chevron"></i>
            </button>
            <div class="collapse {{ $isAdminActive ? 'show' : '' }}" id="menuAdmin">
                <div class="sidebar-sub-menu">
                    <a href="{{ route('users.index') }}" 
                       class="sidebar-sub-link {{ (request()->routeIs('users.*') && !request()->routeIs('users.create')) ? 'active' : '' }}">
                        <i class="bi bi-person-gear"></i>
                        <span>المستخدمين والصلاحيات</span>
                    </a>
                    <a href="{{ route('users.create') }}" 
                       class="sidebar-sub-link {{ request()->routeIs('users.create') ? 'active' : '' }}">
                        <i class="bi bi-person-plus"></i>
                        <span>إضافة مستخدم جديد</span>
                    </a>
                </div>
            </div>
        </div>
        @endif

    </nav>

    <!-- Sidebar Footer with User Profile -->
    <div class="sidebar-footer">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2 text-truncate me-2">
                <div class="user-avatar" style="width: 30px; height: 30px; font-size: 0.75rem;">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="text-truncate">
                    <div class="text-dark fw-bold text-truncate" style="font-size: 0.8rem;" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</div>
                    <small class="text-muted d-block text-truncate" style="font-size: 0.7rem;">{{ auth()->user()->role_label }}</small>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="m-0 flex-shrink-0">
                @csrf
                <button type="submit" class="navbar-action-btn text-muted p-1" title="تسجيل الخروج" aria-label="تسجيل الخروج">
                    <i class="bi bi-box-arrow-left fs-5"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
