<?php

namespace App\Http\Api\Controllers;

use App\Models\User;
use App\Models\Avatar;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class RegisterController
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'birth_date' => ['sometimes', 'date'],
            'university' => ['sometimes', 'string', 'max:200'],
            'career' => ['sometimes', 'string', 'max:200'],
            'cycle' => ['sometimes', 'integer', 'min:1', 'max:12'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => $request->password,
                'birth_date' => $request->birth_date,
                'university' => $request->university,
                'career' => $request->career,
                'cycle' => $request->cycle,
                'active' => true,
            ]);

            Avatar::create([
                'user_id' => $user->id,
                'emotional_state' => 'neutral',
                'happiness_level' => 50,
                'streak_days' => 0,
                'motivational_message' => '¡Bienvenido! Estoy aquí para acompañarte en tu camino académico.',
            ]);

            event(new Registered($user));

            $deviceName = $request->device_name ?? $request->userAgent() ?? 'unknown';
            $token = $user->createToken($deviceName)->plainTextToken;

            DB::commit();

            return response()->json([
                'user' => $user->load('avatar'),
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Error al crear usuario: ' . $e->getMessage(),
            ], 500);
        }
    }
}
