<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function ping(Request $request): JsonResponse
    {
        $tenant = app('currentTenant');
        return response()->json([
            'success' => true,
            'data' => [
                'tenant' => $tenant ? [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ] : null,
            ],
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = $request->input('login');

        $user = User::withoutGlobalScope('tenant')
            ->where(function ($q) use ($loginField) {
                $q->where('email', $loginField)->orWhere('phone', $loginField);
            })
            ->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Kredensial tidak valid.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'code' => 'USER_SUSPENDED',
                'message' => 'Akun dinonaktifkan.',
            ], 403);
        }

        $tenant = $user->tenant;
        if ($tenant && $tenant->isSuspended()) {
            return response()->json([
                'success' => false,
                'code' => 'TENANT_SUSPENDED',
                'message' => 'Tenant dinonaktifkan. Hubungi vendor.',
            ], 402);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('api-token', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role_display' => $user->role_display,
                    'tenant' => $tenant ? [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'domain' => $tenant->domain,
                    ] : null,
                ],
                'token' => $token,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('tenant');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role_display' => $user->role_display,
                'tenant' => $user->tenant ? [
                    'id' => $user->tenant->id,
                    'name' => $user->tenant->name,
                    'domain' => $user->tenant->domain,
                ] : null,
            ],
        ]);
    }

    public function children(Request $request): JsonResponse
    {
        $user = $request->user();
        $children = $user->guardianStudents()->with(['classes' => function ($q) {
            $q->whereHas('schoolYear', fn ($sq) => $sq->where('is_active', true));
        }])->get();

        return response()->json([
            'success' => true,
            'data' => $children,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}
