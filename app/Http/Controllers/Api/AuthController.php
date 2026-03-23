<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    /**
     * Login user dan dapatkan token
     */
    #[OA\Post(
        path: '/auth/login',
        summary: 'Login user',
        description: 'Login dengan email dan password untuk mendapatkan access token',
        operationId: 'login',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                    new OA\Property(property: 'device_name', type: 'string', example: 'flutter_app', description: 'Nama device untuk identifikasi token'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Login berhasil'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'token', type: 'string', example: '1|abcdef123456...'),
                                new OA\Property(
                                    property: 'user',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'Admin'),
                                        new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
                                        new OA\Property(property: 'role', type: 'string', example: 'admin'),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Login gagal',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Email atau password salah'),
                    ]
                )
            ),
        ]
    )]
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Email atau password salah', 401);
        }

        $token = $user->createToken($request->device_name ?? 'api_token')->plainTextToken;

        $user->update(['last_login_at' => now()]);

        return $this->success([
            'token' => $token,
            'user'  => $this->formatUser($user),
        ], 'Login berhasil');
    }

    /**
     * Register user baru
     */
    #[OA\Post(
        path: '/auth/register',
        summary: 'Register user baru',
        description: 'Daftarkan user baru ke sistem',
        operationId: 'register',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Registrasi berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Registrasi berhasil'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'token', type: 'string'),
                                new OA\Property(property: 'user', type: 'object'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'kasir',
        ]);

        $token = $user->createToken('api_token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user'  => $this->formatUser($user),
        ], 'Registrasi berhasil', 201);
    }

    /**
     * Logout user
     */
    #[OA\Post(
        path: '/auth/logout',
        summary: 'Logout user',
        description: 'Logout dan hapus token saat ini',
        operationId: 'logout',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout berhasil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Logout berhasil'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logout berhasil');
    }

    /**
     * Get current user profile
     */
    #[OA\Get(
        path: '/auth/me',
        summary: 'Get current user',
        description: 'Dapatkan data user yang sedang login',
        operationId: 'me',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Admin'),
                                new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
                                new OA\Property(property: 'role', type: 'string', example: 'admin'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function me(Request $request)
    {
        return $this->success($this->formatUser($request->user()));
    }

    private function formatUser($user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->role,
            'permissions' => $user->isAdmin() ? null : ($user->permissions ?? []),
        ];
    }

    /**
     * Update user profile
     */
    #[OA\Put(
        path: '/auth/profile',
        summary: 'Update profile user',
        description: 'Update nama dan email user yang sedang login',
        operationId: 'updateProfile',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'John Updated'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.updated@example.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Profile berhasil diupdate'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($request->only(['name', 'email']));

        return $this->success($this->formatUser($user), 'Profile berhasil diupdate');
    }

    /**
     * Change password
     */
    #[OA\Put(
        path: '/auth/password',
        summary: 'Ganti password',
        description: 'Ganti password user yang sedang login',
        operationId: 'changePassword',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', example: 'oldpassword'),
                    new OA\Property(property: 'password', type: 'string', example: 'newpassword123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', example: 'newpassword123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password berhasil diganti'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Password saat ini salah', 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->success(null, 'Password berhasil diganti');
    }
}
