<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AuditLog::class);

        $query = AuditLog::with('user')->latest('id');

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->filled('event')) {
            $query->event($request->input('event'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->dateRange($request->input('date_from'), $request->input('date_to'));
        }

        $logs = $query->paginate(25)->withQueryString();
        $users = User::orderBy('name')->get();

        $events = [
            'sale.completed' => 'اعتماد وصرف مبيعات',
            'sale.voided' => 'إلغاء فاتورة مبيعات',
            'purchase.approved' => 'اعتماد وتوريد مشتريات',
            'transfer.dispatched' => 'شحن تحويل مخزني',
            'transfer.received' => 'استلام تحويل مخزني',
            'auth.login' => 'تسجيل دخول',
            'auth.logout' => 'تسجيل خروج',
            'user.created' => 'إنشاء مستخدم',
            'user.updated' => 'تعديل مستخدم',
        ];

        return view('audit.index', compact('logs', 'users', 'events'));
    }
}
