<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ميزان فلو') }} — المنظومة السحابية للمشتريات والمخازن</title>

    <!-- Local Bundled Assets (Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body style="background-color: var(--mz-bg-app); min-height: 100vh; display: flex; flex-direction: column;">

    <!-- Top App Navigation (Google Cloud Style) -->
    <header class="app-navbar" style="position: sticky; top: 0; z-index: 1000; box-shadow: var(--mz-shadow-card);">
        <div class="container d-flex align-items-center justify-content-between py-2">
            <a href="{{ url('/') }}" class="d-flex align-items-center gap-2 text-decoration-none text-dark">
                <div class="sidebar-brand-mark" style="width: 36px; height: 36px; font-size: 1.25rem;">
                    <i class="bi bi-intersect"></i>
                </div>
                <div>
                    <span class="fw-bold fs-5 text-dark">ميزان فلو</span>
                    <span class="text-primary small fw-semibold ms-1">| Cloud ERP</span>
                </div>
            </a>

            <div class="d-flex align-items-center gap-2">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-mz-primary">
                            <i class="bi bi-speedometer2"></i>
                            <span>لوحة التحكم</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-mz-primary">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span>دخول إلى المنظومة</span>
                        </a>
                    @endauth
                @endif
            </div>
        </div>
    </header>

    <!-- Hero Section (Global Enterprise Supply Chain) -->
    <main class="flex-grow-1">
        <section class="py-5" style="background: radial-gradient(circle at top right, rgba(11, 87, 208, 0.05) 0%, transparent 60%);">
            <div class="container py-lg-4 text-center">
                <!-- Status Chip -->
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 mb-4 rounded-pill border bg-white shadow-xs" style="font-size: 0.815rem;">
                    <span class="system-status-dot"></span>
                    <span class="fw-medium text-secondary">المنظومة السحابية الرسمية لإدارة المشتريات والمخازن متعددة الفروع</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;">v1.0 LTS</span>
                </div>

                <!-- Main Headline -->
                <h1 class="display-5 fw-bold text-dark mb-3" style="max-width: 860px; margin: 0 auto; line-height: 1.35; letter-spacing: -0.02em;">
                    إدارة متكاملة للمخزون، المشتريات، والفوترة اللحظية <span class="text-primary">بدقة مؤسسية فائقة</span>
                </h1>

                <!-- Subtitle -->
                <p class="lead text-muted mb-4 mx-auto" style="max-width: 720px; font-size: 1.05rem; line-height: 1.8;">
                    منصة سحابية متطورة تدمج نقاط البيع السريعة، أوامر الشراء والتوريد، وتتبع أرصدة المخازن عبر خوارزمية المتوسط المرجح المتحرك (WAC) مع حماية تشاؤمية لمنع البيع بالسالب.
                </p>

                <!-- Actions -->
                <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap mb-5">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-mz-primary px-4 py-2 fs-6">
                            <i class="bi bi-speedometer2 fs-5"></i>
                            <span>الانتقال لمساحة العمل</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-mz-primary px-4 py-2 fs-6">
                            <i class="bi bi-box-arrow-in-right fs-5"></i>
                            <span>تسجيل الدخول للمنظومة</span>
                        </a>
                    @endauth
                    <a href="#features" class="btn btn-mz-outline px-4 py-2 fs-6">
                        <i class="bi bi-grid-3x3-gap"></i>
                        <span>استعراض قدرات النظام</span>
                    </a>
                </div>

                <!-- Metric Highlights Row -->
                <div class="row g-3 justify-content-center text-start mt-2">
                    <div class="col-12 col-md-4">
                        <div class="mz-card p-3 h-100 bg-white">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="stat-icon-wrapper" style="background-color: var(--mz-primary-subtle); color: var(--mz-primary);">
                                    <i class="bi bi-cpu fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">محرك تكلفة لحظي (WAC)</h6>
                                    <small class="text-muted">حساب تكلفة المخزون بدقة 4 خانات عشرية</small>
                                </div>
                            </div>
                            <p class="small text-secondary mb-0">تحديث فوري لمتوسط التكلفة المرجح مع كل اعتماد فاتورة شراء لمنع التفاوت المالي.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="mz-card p-3 h-100 bg-white">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="stat-icon-wrapper" style="background-color: var(--mz-success-bg); color: var(--mz-success);">
                                    <i class="bi bi-shield-lock fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">حماية تشاؤمية ضد العجز</h6>
                                    <small class="text-muted">Pessimistic DB Locks</small>
                                </div>
                            </div>
                            <p class="small text-secondary mb-0">قفل السجلات على مستوى قاعدة البيانات لمنع البيع بالسالب تحت أي ظرف تشغيلي.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="mz-card p-3 h-100 bg-white">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="stat-icon-wrapper" style="background-color: var(--mz-warning-bg); color: var(--mz-warning);">
                                    <i class="bi bi-buildings fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">تعدد المستودعات والفروع</h6>
                                    <small class="text-muted">سلسلة توريد متكاملة</small>
                                </div>
                            </div>
                            <p class="small text-secondary mb-0">إدارة المخزون عبر المستودعات الرئيسية والفرعية مع تحويلات داخلية موثقة ومعتمدة.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Core Capabilities Section -->
        <section id="features" class="py-5 bg-white border-top border-bottom">
            <div class="container py-4">
                <div class="text-center mb-5">
                    <span class="text-primary fw-bold small text-uppercase" style="letter-spacing: 0.05em;">الهندسة التشغيلية والمعمارية</span>
                    <h2 class="fw-bold text-dark mt-1">وحدات المنظومة المتكاملة</h2>
                    <p class="text-muted mx-auto small" style="max-width: 600px;">بُنيت ميزان فلو لخدمة الشركات الكبرى والمؤسسات التجارية التي تتطلب دقة محاسبية واستجابة سريعة.</p>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="p-3 border rounded-3 h-100 bg-light-subtle">
                            <i class="bi bi-cart-check fs-2 text-primary mb-2 d-block"></i>
                            <h5 class="fw-bold text-dark fs-6 mb-2">نقطة البيع السريعة (POS)</h5>
                            <p class="small text-muted mb-0">دعم مسدس الباركود، خصومات الأصناف، والتحقق الفوري من سقف ائتمان العملاء مع إصدار الفواتير الفورية.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="p-3 border rounded-3 h-100 bg-light-subtle">
                            <i class="bi bi-truck fs-2 text-primary mb-2 d-block"></i>
                            <h5 class="fw-bold text-dark fs-6 mb-2">المشتريات والتوريد</h5>
                            <p class="small text-muted mb-0">إدارة فواتير المشتريات، متابعة دفعات الموردين، وإعادة تقييم المخزون التاريخي تلقائياً.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="p-3 border rounded-3 h-100 bg-light-subtle">
                            <i class="bi bi-boxes fs-2 text-primary mb-2 d-block"></i>
                            <h5 class="fw-bold text-dark fs-6 mb-2">دفتر الأستاذ المخزني</h5>
                            <p class="small text-muted mb-0">سجل حركات مخزنية تاريخي غير قابل للمحو (Immutable Ledger) يوثق كل إضافة، صرف، أو تحويل.</p>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="p-3 border rounded-3 h-100 bg-light-subtle">
                            <i class="bi bi-graph-up-arrow fs-2 text-primary mb-2 d-block"></i>
                            <h5 class="fw-bold text-dark fs-6 mb-2">التقارير وهوامش الأرباح</h5>
                            <p class="small text-muted mb-0">تحليلات الأرباح الفعلية المستندة إلى تكلفة WAC الحقيقية، كشوفات حساب العملاء، وتقييم الأرصدة.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Clean Enterprise Footer -->
    <footer class="py-4 bg-white border-top">
        <div class="container d-flex flex-wrap align-items-center justify-content-between gap-3 text-muted small">
            <div class="d-flex align-items-center gap-2">
                <span class="sidebar-brand-mark" style="width: 26px; height: 26px; font-size: 0.9rem;">
                    <i class="bi bi-intersect"></i>
                </span>
                <span>منظومة <strong>ميزان فلو (MizanFlow Cloud ERP)</strong> &copy; {{ date('Y') }} — جميع الحقوق محفوظة.</span>
            </div>
            <div class="d-flex align-items-center gap-3 text-secondary" style="font-size: 0.775rem;">
                <span>الإصدار 1.0 LTS</span>
                <span>•</span>
                <span>معايير أمان سحابية مؤسسية</span>
                <span>•</span>
                <span>دعم كامل للغة العربية والـ RTL</span>
            </div>
        </div>
    </footer>

</body>
</html>
