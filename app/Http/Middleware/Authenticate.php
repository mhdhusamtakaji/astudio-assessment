<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
         // If it’s not a JSON (API) request, we can still redirect to a web login if needed.
    // But for an API request, just return a 401 JSON error.
    if (! $request->expectsJson()) {
        // return route('login'); // or some other web route
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    abort(response()->json(['message' => 'Unauthorized'], 401));
    }
}
