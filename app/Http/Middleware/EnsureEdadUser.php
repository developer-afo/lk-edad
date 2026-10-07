<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEdadUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = session('user_id');
        $user = $userId ? User::query()->find($userId) : null;

        if (! $user) {
            session()->forget('user_id');

            return redirect()->route('login');
        }

        $request->attributes->set('edad_user', $user);
        view()->share('edadUser', $user);

        return $next($request);
    }
}
