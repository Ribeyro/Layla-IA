<?php

namespace App\Http\Api\Controllers;

use App\Models\Task;
use App\Models\TaskHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController
{
    public function index(Request $request): JsonResponse
    {
        $query = Task::where('user_id', $request->user()->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $tasks = $query->orderBy('due_date', 'asc')->get();

        return response()->json([
            'success' => true,
            'tasks' => $tasks,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:task,exam,work,class,other'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date'],
            'priority' => ['required', 'in:low,medium,high'],
            'subject' => ['nullable', 'string', 'max:150'],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:1'],
        ]);

        $task = Task::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'due_date' => $request->due_date,
            'priority' => $request->priority,
            'status' => 'pending',
            'progress_percentage' => 0,
            'subject' => $request->subject,
            'estimated_time_minutes' => $request->estimated_time_minutes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tarea creada exitosamente',
            'task' => $task,
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $task = Task::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'task' => $task,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $task = Task::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'in:task,exam,work,class,other'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['sometimes', 'date'],
            'priority' => ['sometimes', 'in:low,medium,high'],
            'status' => ['sometimes', 'in:pending,in_progress,completed,overdue'],
            'progress_percentage' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'subject' => ['nullable', 'string', 'max:150'],
            'estimated_time_minutes' => ['nullable', 'integer', 'min:1'],
        ]);

        DB::beginTransaction();

        try {
            $oldStatus = $task->status;

            $task->update($request->only([
                'title',
                'description',
                'type',
                'start_date',
                'due_date',
                'priority',
                'status',
                'progress_percentage',
                'subject',
                'estimated_time_minutes',
            ]));

            if ($request->has('status') && $request->status === 'completed' && $oldStatus !== 'completed') {
                $task->update(['completed_at' => now()]);
            }

            if ($request->has('status') && $oldStatus !== $request->status) {
                TaskHistory::create([
                    'task_id' => $task->id,
                    'previous_status' => $oldStatus,
                    'new_status' => $request->status,
                    'changed_at' => now(),
                ]);

                $this->updateAvatarState($request->user()->id);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tarea actualizada exitosamente',
                'task' => $task->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar tarea: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $task = Task::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tarea eliminada exitosamente',
        ]);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $task = Task::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,overdue'],
        ]);

        DB::beginTransaction();

        try {
            $oldStatus = $task->status;

            $task->update([
                'status' => $request->status,
                'completed_at' => $request->status === 'completed' ? now() : null,
            ]);

            TaskHistory::create([
                'task_id' => $task->id,
                'previous_status' => $oldStatus,
                'new_status' => $request->status,
                'changed_at' => now(),
            ]);

            $this->updateAvatarState($request->user()->id);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estado de tarea actualizado',
                'task' => $task->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar estado: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function updateAvatarState($userId): void
    {
        $totalTasks = Task::where('user_id', $userId)
            ->whereIn('status', ['pending', 'in_progress', 'completed'])
            ->count();

        if ($totalTasks === 0) {
            return;
        }

        $completedTasks = Task::where('user_id', $userId)
            ->where('status', 'completed')
            ->count();

        $completionPercentage = ($completedTasks / $totalTasks) * 100;
        $happinessLevel = min(100, max(0, $completionPercentage));

        $emotionalState = 'neutral';
        if ($happinessLevel >= 70) {
            $emotionalState = 'happy';
        } elseif ($happinessLevel < 40) {
            $emotionalState = 'sad';
        }

        $avatar = \App\Models\Avatar::where('user_id', $userId)->first();
        if ($avatar) {
            $avatar->update([
                'happiness_level' => $happinessLevel,
                'emotional_state' => $emotionalState,
                'last_state_update' => now(),
            ]);
        }
    }

    public function upcoming(Request $request): JsonResponse
    {
        $tasks = Task::where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('due_date', '>=', now())
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'tasks' => $tasks,
        ]);
    }

    public function overdue(Request $request): JsonResponse
    {
        $tasks = Task::where('user_id', $request->user()->id)
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->orderBy('due_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'tasks' => $tasks,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $summary = [
            'pending' => Task::where('user_id', $userId)->where('status', 'pending')->count(),
            'in_progress' => Task::where('user_id', $userId)->where('status', 'in_progress')->count(),
            'completed' => Task::where('user_id', $userId)->where('status', 'completed')->count(),
            'overdue' => Task::where('user_id', $userId)
                ->where('status', '!=', 'completed')
                ->where('due_date', '<', now())
                ->count(),
            'total' => Task::where('user_id', $userId)->count(),
        ];

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }
}
