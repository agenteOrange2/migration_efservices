<?php

namespace App\Http\Responses;

use App\Services\AuthenticationService;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 201);
        }

        $user = $request->user();

        if (!$user) {
            return redirect('/');
        }

        $authService = app(AuthenticationService::class);
        $redirectUrl = $authService->determineRedirect($user);

        return redirect()->intended($redirectUrl);
    }
}
