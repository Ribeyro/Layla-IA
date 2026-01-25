<?php

namespace App\Http\Api\Controllers;

use App\Models\Recommendation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationController
{
    public function index(Request $request): JsonResponse
    {
        $query = Recommendation::where('user_id', $request->user()->id);

        if ($request->has('viewed')) {
            $query->where('viewed', $request->boolean('viewed'));
        }

        if ($request->has('applied')) {
            $query->where('applied', $request->boolean('applied'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $recommendations = $query->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'recommendations' => $recommendations,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'content' => ['required', 'string'],
            'type' => ['required', 'in:study,organization,wellbeing,academic'],
            'priority' => ['required', 'in:low,medium,high'],
        ]);

        $recommendation = Recommendation::create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
            'type' => $request->type,
            'priority' => $request->priority,
            'viewed' => false,
            'applied' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recomendación creada exitosamente',
            'recommendation' => $recommendation,
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $recommendation = Recommendation::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if (!$recommendation->viewed) {
            $recommendation->update([
                'viewed' => true,
                'viewed_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'recommendation' => $recommendation->fresh(),
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $recommendation = Recommendation::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'content' => ['sometimes', 'string'],
            'type' => ['sometimes', 'in:study,organization,wellbeing,academic'],
            'priority' => ['sometimes', 'in:low,medium,high'],
            'applied' => ['sometimes', 'boolean'],
        ]);

        $recommendation->update($request->only([
            'content',
            'type',
            'priority',
            'applied',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Recomendación actualizada exitosamente',
            'recommendation' => $recommendation->fresh(),
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $recommendation = Recommendation::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $recommendation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recomendación eliminada exitosamente',
        ]);
    }

    public function markAsViewed(Request $request, $id): JsonResponse
    {
        $recommendation = Recommendation::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $recommendation->update([
            'viewed' => true,
            'viewed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recomendación marcada como vista',
            'recommendation' => $recommendation->fresh(),
        ]);
    }

    public function markAsApplied(Request $request, $id): JsonResponse
    {
        $recommendation = Recommendation::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $recommendation->update(['applied' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Recomendación marcada como aplicada',
            'recommendation' => $recommendation->fresh(),
        ]);
    }

    public function unviewed(Request $request): JsonResponse
    {
        $recommendations = Recommendation::where('user_id', $request->user()->id)
            ->where('viewed', false)
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'recommendations' => $recommendations,
        ]);
    }
}
