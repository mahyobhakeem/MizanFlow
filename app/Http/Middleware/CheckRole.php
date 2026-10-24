<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! empty($roles) && ! in_array($request->user()->role, $roles, true)) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة أو تنفيذ هذه العملية.');
        }

        return $next($request);
    }
}
