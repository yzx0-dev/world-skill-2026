<?php

namespace App\Http\Controllers\Api;

use App\Concerns\RespondsWithApiJson;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use RespondsWithApiJson;

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::where('username', $credentials['username'])->first();

        // Login is username/password because the test project screen requires that form.
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->error('Invalid username or password.', 422);
        }

        if (! $user->is_active) {
            return $this->error('This account is disabled.', 403);
        }

        $token = $user->createToken('competition-api-token', [$user->role])->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => (new UserResource($user))->resolve($request),
            'redirect_to' => match ($user->role) {
                'judge' => '/judge',
                'manager' => '/manager',
                default => '/candidate',
            },
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Sanctum token logout for API clients.
        $request->user()?->currentAccessToken()?->delete();

        return $this->success([
            'message' => 'Logged out.',
        ]);
    }
}
