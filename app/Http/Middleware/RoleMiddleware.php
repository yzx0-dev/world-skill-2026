<?php

namespace App\Http\Middleware;

use App\Concerns\RespondsWithApiJson;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    use RespondsWithApiJson;

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Business rule: each API group is limited to its own competition role.
        if (! $user || ! in_array($user->role, $roles, true)) {
            return $this->error('You are not allowed to access this resource.', 403);
        }

        return $next($request);
    }
}
