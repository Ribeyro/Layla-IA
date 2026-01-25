<?php

namespace App\Http\Api\Controllers;

use App\Models\Habit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HabitController
{
    public function index(Request $request): JsonResponse
    {
        $query = Habit::where('user_id', $request->user()->id);

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($request->has('frequency')) {
            $query->where('frequency', $request->frequency);
        }

        $habits = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'habits' => $habits,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'preferred_time' => ['nullable', 'date_format:H:i:s'],
        ]);

        $habit = Habit::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
            'frequency' => $request->frequency,
            'preferred_time' => $request->preferred_time,
            'active' => true,
            'current_streak' => 0,
            'max_streak' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Hábito creado exitosamente',
            'habit' => $habit,
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $habit = Habit::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'habit' => $habit,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $habit = Habit::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'name' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'frequency' => ['sometimes', 'in:daily,weekly,monthly'],
            'preferred_time' => ['nullable', 'date_format:H:i:s'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $habit->update($request->only([
            'name',
            'description',
            'frequency',
            'preferred_time',
            'active',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Hábito actualizado exitosamente',
            'habit' => $habit->fresh(),
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $habit = Habit::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $habit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Hábito eliminado exitosamente',
        ]);
    }

    public function completeHabit(Request $request, $id): JsonResponse
    {
        $habit = Habit::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $currentStreak = $habit->current_streak + 1;
        $maxStreak = max($habit->max_streak, $currentStreak);

        $habit->update([
            'current_streak' => $currentStreak,
            'max_streak' => $maxStreak,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Hábito completado. ¡Racha aumentada!',
            'habit' => $habit->fresh(),
        ]);
    }

    public function breakStreak(Request $request, $id): JsonResponse
    {
        $habit = Habit::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $habit->update(['current_streak' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Racha reiniciada',
            'habit' => $habit->fresh(),
        ]);
    }

    public function toggleActive(Request $request, $id): JsonResponse
    {
        $habit = Habit::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $habit->update(['active' => !$habit->active]);

        return response()->json([
            'success' => true,
            'message' => $habit->active ? 'Hábito activado' : 'Hábito desactivado',
            'habit' => $habit->fresh(),
        ]);
    }
}
