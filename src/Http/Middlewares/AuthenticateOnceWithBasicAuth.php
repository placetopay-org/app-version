<?php

declare(strict_types=1);

namespace PlacetoPay\AppVersion\Http\Middlewares;

class AuthenticateOnceWithBasicAuth
{
    public function handle($request, \Closure $next)
    {
        $user = config('app-version.username');
        $password = config('app-version.password');

        if ($request->getUser() !== $user || $request->getPassword() !== $password) {
            return response('Unauthorized.', 401, ['WWW-Authenticate' => 'Basic']);
        }

        return $next($request);
    }
}
