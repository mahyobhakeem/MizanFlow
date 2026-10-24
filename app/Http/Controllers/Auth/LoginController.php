<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'يرجى إدخال اسم المستخدم أو البريد الإلكتروني.',
            'password.required' => 'يرجى إدخال كلمة المرور.',
        ]);

        $loginInput = $credentials['login'];
        $password = $credentials['password'];
        $remember = $request->boolean('remember');

        // Determine if login is email or username
        $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$fieldType => $loginInput, 'password' => $password], $remember)) {
            throw ValidationException::withMessages([
                'login' => ['بيانات الاعتماد غير متطابقة مع سجلاتنا.'],
            ]);
        }

        $user = Auth::user();

        // Check if user account is active
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => ['هذا الحساب معطل حالياً. يرجى التواصل مع المدير العام.'],
            ]);
        }

        $request->session()->regenerate();

        app(AuditService::class)->record(
            'auth.login',
            $user,
            "تسجيل دخول ناجح للمستخدم [{$user->name}] بالدور [{$user->role_label}]",
            null,
            ['username' => $user->username, 'role' => $user->role],
            $user->id
        );

        return redirect()->intended(route('dashboard'))
            ->with('success', "مرحباً بك مجدداً، {$user->name}!");
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            app(AuditService::class)->record(
                'auth.logout',
                $user,
                "تسجيل خروج للمستخدم [{$user->name}]",
                null,
                null,
                $user->id
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('info', 'تم تسجيل الخروج بنجاح.');
    }
}
