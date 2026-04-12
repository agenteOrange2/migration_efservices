<?php

namespace App\Http\Responses;

use App\Services\AuthenticationService;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $authService = app(AuthenticationService::class);
        $redirectUrl = $authService->determineRedirect($user);

        return redirect($redirectUrl);
    }
}
