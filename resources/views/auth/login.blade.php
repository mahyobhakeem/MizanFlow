@extends('layouts.guest')

@section('title', 'تسجيل الدخول — ميزان فلو')

@section('content')
<div class="auth-page-wrapper">
    <div class="auth-card">
        <div class="row g-0 flex-column-reverse flex-lg-row">
            
            <!-- Form Column (Clean Focus Canvas - Global Enterprise Standard) -->
            <div class="col-12 col-lg-6 auth-form-panel">
                <div class="mb-4">
                    <!-- Mobile Brand Icon (Visible only on mobile/tablet) -->
                    <div class="d-flex d-lg-none align-items-center gap-2 mb-3">
                        <div class="sidebar-brand-mark" style="width: 38px; height: 38px; font-size: 1.25rem;">
                            <i class="bi bi-intersect"></i>
                        </div>
                        <div>
                            <span class="fw-bold text-dark fs-5">ميزان فلو</span>
                            <span class="text-muted d-block font-monospace" style="font-size: 0.68rem; letter-spacing: 0.08em;">MIZANFLOW ERP</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h4 class="fw-bold text-dark mb-0" style="letter-spacing: -0.01em;">تسجيل الدخول للنظام</h4>
                        <span class="system-status-indicator d-none d-sm-inline-flex">
                            <span class="system-status-dot"></span>
                            <span>الخادم متصل</span>
                        </span>
                    </div>
                    <p class="text-muted small mb-0">أدخل بيانات الاعتماد المؤسسية الخاصة بك للوصول إلى مساحة العمل</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-mz alert-mz-danger py-2 px-3 mb-3" role="alert" style="font-size: 0.825rem;">
                        <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 text-danger"></i>
                        <div class="flex-grow-1">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (session('info'))
                    <div class="alert alert-mz alert-mz-info py-2 px-3 mb-3" role="alert" style="font-size: 0.825rem;">
                        <i class="bi bi-info-circle-fill fs-5 flex-shrink-0 text-info"></i>
                        <div class="flex-grow-1">{{ session('info') }}</div>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-mz alert-mz-success py-2 px-3 mb-3" role="alert" style="font-size: 0.825rem;">
                        <i class="bi bi-check-circle-fill fs-5 flex-shrink-0 text-success"></i>
                        <div class="flex-grow-1">{{ session('success') }}</div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm" novalidate>
                    @csrf

                    <!-- Username / Email Field (Stripe / Google Cloud Luxury Input) -->
                    <div class="auth-field-group">
                        <label for="login" class="auth-field-label">
                            <span>
                                <i class="bi bi-person-badge text-muted me-1"></i>
                                اسم المستخدم أو البريد الإلكتروني
                            </span>
                            <span class="text-danger fw-bold" title="حقل إلزامي">*</span>
                        </label>
                        <div class="auth-input-container @error('login') has-error @enderror" id="loginContainer">
                            <div class="auth-input-icon">
                                <i class="bi bi-person"></i>
                            </div>
                            <input type="text" 
                                   class="auth-input-control" 
                                   id="login" 
                                   name="login" 
                                   value="{{ old('login', 'admin') }}" 
                                   required 
                                   autofocus 
                                   autocomplete="username" 
                                   autocapitalize="none" 
                                   autocorrect="off" 
                                   spellcheck="false"
                                   enterkeyhint="next"
                                   placeholder="اسم المستخدم أو البريد (مثال: admin)">
                            <div class="pe-2 text-muted" id="loginClearBtn" style="display: none; cursor: pointer;">
                                <i class="bi bi-x-circle-fill text-muted" style="font-size: 0.95rem;"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Password Field with Luxury Toggle Interaction -->
                    <div class="auth-field-group">
                        <label for="password" class="auth-field-label">
                            <span>
                                <i class="bi bi-shield-lock text-muted me-1"></i>
                                كلمة المرور السرية
                            </span>
                            <span class="text-danger fw-bold" title="حقل إلزامي">*</span>
                        </label>
                        <div class="auth-input-container @error('password') has-error @enderror" id="passwordContainer">
                            <div class="auth-input-icon">
                                <i class="bi bi-lock"></i>
                            </div>
                            <input type="password" 
                                   class="auth-input-control" 
                                   id="password" 
                                   name="password" 
                                   value="password123" 
                                   required 
                                   autocomplete="current-password"
                                   enterkeyhint="go"
                                   placeholder="••••••••">
                            <button type="button" 
                                    class="auth-input-action-btn" 
                                    id="togglePasswordBtn" 
                                    title="إظهار / إخفاء كلمة المرور"
                                    aria-label="إظهار كلمة المرور"
                                    aria-pressed="false"
                                    tabindex="0">
                                <i class="bi bi-eye" id="togglePasswordIcon" style="font-size: 1.05rem;"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me Option -->
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="form-check m-0 d-flex align-items-center gap-2">
                            <input class="form-check-input m-0" type="checkbox" name="remember" id="remember" checked style="cursor: pointer;">
                            <label class="form-check-label small text-secondary user-select-none" for="remember" style="cursor: pointer;">
                                تذكر جلسة العمل على هذا المتصفح
                            </label>
                        </div>
                        <span class="small text-muted d-none d-sm-inline">
                            <i class="bi bi-lock-fill text-success"></i> 256-bit SSL
                        </span>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-mz-primary w-100 btn-auth-submit" id="submitBtn">
                        <span class="fw-semibold">تسجيل الدخول إلى النظام</span>
                        <i class="bi bi-arrow-left-circle-fill fs-5" id="submitIcon"></i>
                        <span class="spinner-border spinner-border-sm d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
                    </button>
                </form>

                <!-- Quick Demo Credentials Switcher (Global Enterprise Standard) -->
                <div class="demo-accounts-box">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-bold text-dark">
                            <i class="bi bi-lightning-charge-fill text-warning me-1"></i>
                            الحسابات التجريبية السريعة (اختر للتعبئة الفورية):
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" 
                                class="demo-chip active demo-role-btn" 
                                id="demoBtnAdmin"
                                onclick="fillLogin('admin', this)">
                            <div class="demo-chip-avatar" style="background: rgba(11, 87, 208, 0.12); color: var(--mz-primary);">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div class="demo-chip-info">
                                <div class="demo-chip-title">مدير النظام</div>
                                <div class="demo-chip-user">admin</div>
                            </div>
                        </button>

                        <button type="button" 
                                class="demo-chip demo-role-btn" 
                                id="demoBtnManager"
                                onclick="fillLogin('manager', this)">
                            <div class="demo-chip-avatar" style="background: rgba(19, 115, 51, 0.12); color: var(--mz-success);">
                                <i class="bi bi-briefcase"></i>
                            </div>
                            <div class="demo-chip-info">
                                <div class="demo-chip-title">مدير العمليات</div>
                                <div class="demo-chip-user">manager</div>
                            </div>
                        </button>

                        <button type="button" 
                                class="demo-chip demo-role-btn" 
                                id="demoBtnCashier"
                                onclick="fillLogin('cashier', this)">
                            <div class="demo-chip-avatar" style="background: rgba(176, 96, 0, 0.12); color: var(--mz-warning);">
                                <i class="bi bi-cart3"></i>
                            </div>
                            <div class="demo-chip-info">
                                <div class="demo-chip-title">كاشير المبيعات</div>
                                <div class="demo-chip-user">cashier</div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Footer Security Notice -->
                <div class="mt-4 pt-2 text-center text-muted small" style="font-size: 0.75rem;">
                    <span>نظام تجاري محمي ومراقب بسجلات التدقيق الأمني &bull; &copy; {{ date('Y') }} ميزان فلو</span>
                </div>
            </div>

            <!-- Visual Showcase Column (2D Architectural Blueprint & Brand Experience) -->
            <div class="col-12 col-lg-6 auth-showcase-panel">
                <div>
                    <!-- Brand Top Bar -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="sidebar-brand-mark" style="width: 44px; height: 44px; font-size: 1.45rem; background: rgba(255, 255, 255, 0.16); border: 1px solid rgba(255,255,255,0.25); backdrop-filter: blur(8px);">
                                <i class="bi bi-intersect"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0 text-white" style="letter-spacing: -0.02em;">ميزان فلو</h3>
                                <div class="text-white-50 font-monospace" style="letter-spacing: 0.12em; font-size: 0.72rem;">MIZANFLOW CLOUD ERP</div>
                            </div>
                        </div>
                        <span class="badge bg-white bg-opacity-15 text-white font-monospace border border-white border-opacity-25 px-2 py-1" style="font-size: 0.72rem;">
                            v2.5 Enterprise
                        </span>
                    </div>

                    <!-- Platform Brief -->
                    <p class="text-white-50 mb-3" style="line-height: 1.7; font-size: 0.84rem;">
                        المنظومة السحابية المتكاملة لإدارة سلاسل الإمداد، المخازن المتعددة، الفوترة اللحظية (POS)، واحتساب تكاليف WAC بأعلى معايير الدقة المحاسبية.
                    </p>

                    <!-- Expressive 2D Illustration Frame with Floating Badges -->
                    <div class="auth-illustration-wrapper">
                        <!-- Top Floating Pill -->
                        <div class="auth-floating-badge badge-top-end">
                            <i class="bi bi-shield-fill-check text-primary"></i>
                            <span>حماية فورية من البيع بالسالب</span>
                        </div>

                        <!-- 2D High-End Blueprint Illustration -->
                        <img src="{{ asset('images/auth/login-illustration.jpg') }}" 
                             alt="مخطط منظومة ميزان فلو لإدارة المخزون والمشتريات والمبيعات" 
                             class="auth-illustration-img"
                             loading="eager"
                             width="640" 
                             height="480">

                        <!-- Bottom Floating Pill -->
                        <div class="auth-floating-badge badge-bottom-start">
                            <i class="bi bi-calculator-fill text-success"></i>
                            <span>محرك تكلفة WAC المعتمد</span>
                        </div>
                    </div>

                    <!-- Enterprise Pillars Matrix -->
                    <div class="d-flex flex-column gap-2 mt-3">
                        <div class="auth-feature-row">
                            <div class="auth-feature-icon">
                                <i class="bi bi-check2-circle"></i>
                            </div>
                            <div class="small text-white-50">
                                <strong class="text-white d-block" style="font-size: 0.825rem;">دفتر أستاذ تاريخي غير قابل للمحو</strong>
                                أرشفة تدقيقية شاملة لكافة الحركات المالية والمخزنية وسجلات المراجعة
                            </div>
                        </div>

                        <div class="auth-feature-row">
                            <div class="auth-feature-icon">
                                <i class="bi bi-buildings"></i>
                            </div>
                            <div class="small text-white-50">
                                <strong class="text-white d-block" style="font-size: 0.825rem;">مزامنة لحظية للمستودعات المتعددة</strong>
                                رقابة مركزية على التحويلات، الجرد الدوري، وأرصدة الفروع
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Showcase Bottom Status Bar -->
                <div class="pt-3 border-top border-white border-opacity-15 text-white-50 small d-flex justify-content-between align-items-center">
                    <span class="d-flex align-items-center gap-2" style="font-size: 0.75rem;">
                        <span class="system-status-dot" style="background-color: #6dd58c;"></span>
                        <span>السحابة المؤسسية آمنة ومستقرة</span>
                    </span>
                    <span class="text-white-50 font-monospace" style="font-size: 0.72rem;">ISO / SOC-2 Compliant</span>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function fillLogin(username, btnElement) {
        const loginInput = document.getElementById('login');
        const passwordInput = document.getElementById('password');
        
        if (loginInput && passwordInput) {
            loginInput.value = username;
            passwordInput.value = 'password123';
            
            // Visual active state update
            document.querySelectorAll('.demo-role-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            if (btnElement) {
                btnElement.classList.add('active');
            }
            
            // Remove error highlight if any
            document.querySelectorAll('.auth-input-container').forEach(c => c.classList.remove('has-error'));
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('togglePasswordIcon');
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const submitIcon = document.getElementById('submitIcon');
        const submitSpinner = document.getElementById('submitSpinner');

        // Password Show/Hide Toggle with ARIA state
        if (toggleBtn && passwordInput && toggleIcon) {
            toggleBtn.addEventListener('click', function () {
                const isPassword = passwordInput.getAttribute('type') === 'password';
                if (isPassword) {
                    passwordInput.setAttribute('type', 'text');
                    toggleIcon.className = 'bi bi-eye-slash-fill text-primary';
                    toggleBtn.setAttribute('title', 'إخفاء كلمة المرور');
                    toggleBtn.setAttribute('aria-label', 'إخفاء كلمة المرور');
                    toggleBtn.setAttribute('aria-pressed', 'true');
                } else {
                    passwordInput.setAttribute('type', 'password');
                    toggleIcon.className = 'bi bi-eye text-muted';
                    toggleBtn.setAttribute('title', 'إظهار كلمة المرور');
                    toggleBtn.setAttribute('aria-label', 'إظهار كلمة المرور');
                    toggleBtn.setAttribute('aria-pressed', 'false');
                }
                passwordInput.focus();
            });
        }

        // Form Submit Loading State Animation
        if (loginForm && submitBtn && submitIcon && submitSpinner) {
            loginForm.addEventListener('submit', function () {
                if (loginForm.checkValidity()) {
                    submitBtn.disabled = true;
                    submitIcon.classList.add('d-none');
                    submitSpinner.classList.remove('d-none');
                }
            });
        }
    });
</script>
@endsection
