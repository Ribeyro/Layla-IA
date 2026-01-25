<?php

namespace App\Http\Api\Controllers;

use App\Models\DailyStatistic;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DailyStatisticController
{
    public function index(Request $request): JsonResponse
    {
        $query = DailyStatistic::where('user_id', $request->user()->id);

        if ($request->has('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }

        $statistics = $query->orderBy('date', 'desc')->get();

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
        ]);
    }

    public function show(Request $request, $date = null): JsonResponse
    {
        $targetDate = $date ? Carbon::parse($date) : now();

        $statistic = DailyStatistic::where('user_id', $request->user()->id)
            ->where('date', $targetDate->toDateString())
            ->first();

        if (!$statistic) {
            return response()->json([
                'success' => false,
                'error' => 'No hay estadísticas para esta fecha',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'statistic' => $statistic,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date'],
            'stress_level' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'motivation_level' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'study_minutes' => ['sometimes', 'integer', 'min:0'],
            'ai_interactions' => ['sometimes', 'integer', 'min:0'],
        ]);

        $userId = $request->user()->id;
        $date = Carbon::parse($request->date)->toDateString();

        $completedTasks = Task::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereDate('completed_at', $date)
            ->count();

        $pendingTasks = Task::where('user_id', $userId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        $overdueTasks = Task::where('user_id', $userId)
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->count();

        $totalTasks = $completedTasks + $pendingTasks + $overdueTasks;
        $completionPercentage = $totalTasks > 0
            ? ($completedTasks / $totalTasks) * 100
            : 0;

        $statistic = DailyStatistic::updateOrCreate(
            [
                'user_id' => $userId,
                'date' => $date,
            ],
            [
                'completed_tasks' => $completedTasks,
                'pending_tasks' => $pendingTasks,
                'overdue_tasks' => $overdueTasks,
                'completion_percentage' => round($completionPercentage, 2),
                'stress_level' => $request->stress_level ?? 5,
                'motivation_level' => $request->motivation_level ?? 5,
                'study_minutes' => $request->study_minutes ?? 0,
                'ai_interactions' => $request->ai_interactions ?? 0,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Estadística diaria guardada exitosamente',
            'statistic' => $statistic,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $statistic = DailyStatistic::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'stress_level' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'motivation_level' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'study_minutes' => ['sometimes', 'integer', 'min:0'],
            'ai_interactions' => ['sometimes', 'integer', 'min:0'],
        ]);

        $statistic->update($request->only([
            'stress_level',
            'motivation_level',
            'study_minutes',
            'ai_interactions',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Estadística actualizada exitosamente',
            'statistic' => $statistic->fresh(),
        ]);
    }

    public function today(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $today = now()->toDateString();

        $completedTasks = Task::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereDate('completed_at', $today)
            ->count();

        $pendingTasks = Task::where('user_id', $userId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        $overdueTasks = Task::where('user_id', $userId)
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->count();

        $totalTasks = $completedTasks + $pendingTasks + $overdueTasks;
        $completionPercentage = $totalTasks > 0
            ? ($completedTasks / $totalTasks) * 100
            : 0;

        $statistic = DailyStatistic::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        $data = [
            'date' => $today,
            'completed_tasks' => $completedTasks,
            'pending_tasks' => $pendingTasks,
            'overdue_tasks' => $overdueTasks,
            'completion_percentage' => round($completionPercentage, 2),
            'stress_level' => $statistic->stress_level ?? 5,
            'motivation_level' => $statistic->motivation_level ?? 5,
            'study_minutes' => $statistic->study_minutes ?? 0,
            'ai_interactions' => $statistic->ai_interactions ?? 0,
        ];

        return response()->json([
            'success' => true,
            'today_statistics' => $data,
        ]);
    }

    public function weekly(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $statistics = DailyStatistic::where('user_id', $userId)
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->orderBy('date', 'asc')
            ->get();

        $summary = [
            'total_completed_tasks' => $statistics->sum('completed_tasks'),
            'avg_completion_percentage' => round($statistics->avg('completion_percentage'), 2),
            'avg_stress_level' => round($statistics->avg('stress_level'), 2),
            'avg_motivation_level' => round($statistics->avg('motivation_level'), 2),
            'total_study_minutes' => $statistics->sum('study_minutes'),
            'total_ai_interactions' => $statistics->sum('ai_interactions'),
        ];

        return response()->json([
            'success' => true,
            'weekly_statistics' => $statistics,
            'summary' => $summary,
        ]);
    }
}
