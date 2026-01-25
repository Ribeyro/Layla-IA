<?php

namespace App\Http\Api\Controllers;

use App\Models\Avatar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvatarController
{
    public function show(Request $request): JsonResponse
    {
        $avatar = Avatar::where('user_id', $request->user()->id)->first();

        if (!$avatar) {
            return response()->json([
                'success' => false,
                'error' => 'Avatar no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'avatar' => $avatar,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $avatar = Avatar::where('user_id', $request->user()->id)->first();

        if (!$avatar) {
            return response()->json([
                'success' => false,
                'error' => 'Avatar no encontrado',
            ], 404);
        }

        $request->validate([
            'motivational_message' => ['sometimes', 'string'],
            'streak_days' => ['sometimes', 'integer', 'min:0'],
        ]);

        $avatar->update($request->only(['motivational_message', 'streak_days']));

        return response()->json([
            'success' => true,
            'message' => 'Avatar actualizado exitosamente',
            'avatar' => $avatar->fresh(),
        ]);
    }

    public function updateStreak(Request $request): JsonResponse
    {
        $avatar = Avatar::where('user_id', $request->user()->id)->first();

        if (!$avatar) {
            return response()->json([
                'success' => false,
                'error' => 'Avatar no encontrado',
            ], 404);
        }

        $request->validate([
            'action' => ['required', 'in:increment,reset'],
        ]);

        if ($request->action === 'increment') {
            $avatar->increment('streak_days');
        } else {
            $avatar->update(['streak_days' => 0]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Racha actualizada',
            'avatar' => $avatar->fresh(),
        ]);
    }
}
