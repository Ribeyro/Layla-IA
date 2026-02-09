<?php

namespace App\Http\Api\Controllers;

use App\Models\Reminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReminderController
{
    public function index(Request $request): JsonResponse
    {
        $query = Reminder::where('user_id', $request->user()->id);

        if ($request->has('sent')) {
            $query->where('sent', $request->boolean('sent'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $reminders = $query->orderBy('reminder_datetime', 'asc')->get();

        return response()->json([
            'success' => true,
            'reminders' => $reminders,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'task_id' => ['nullable', 'exists:tasks,id'],
            'reminder_datetime' => ['required', 'date', 'after_or_equal:now'],
            'message' => ['required', 'string'],
            'type' => ['required', 'in:voice,notification,both'],
        ]);

        $reminder = Reminder::create([
            'user_id' => $request->user()->id,
            'task_id' => $request->task_id,
            'reminder_datetime' => $request->reminder_datetime,
            'message' => $request->message,
            'type' => $request->type,
            'sent' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recordatorio creado exitosamente',
            'reminder' => $reminder,
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $reminder = Reminder::where('user_id', $request->user()->id)
            ->with('task')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'reminder' => $reminder,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $reminder = Reminder::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'task_id' => ['nullable', 'exists:tasks,id'],
            'reminder_datetime' => ['sometimes', 'date'],
            'message' => ['sometimes', 'string'],
            'type' => ['sometimes', 'in:voice,notification,both'],
        ]);

        $reminder->update($request->only([
            'task_id',
            'reminder_datetime',
            'message',
            'type',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Recordatorio actualizado exitosamente',
            'reminder' => $reminder->fresh(),
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $reminder = Reminder::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $reminder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recordatorio eliminado exitosamente',
        ]);
    }

    public function markAsSent(Request $request, $id): JsonResponse
    {
        $reminder = Reminder::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $reminder->update([
            'sent' => true,
            'sent_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recordatorio marcado como enviado',
            'reminder' => $reminder->fresh(),
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $reminders = Reminder::where('user_id', $request->user()->id)
            ->where('sent', false)
            ->where('reminder_datetime', '>', now())
            ->orderBy('reminder_datetime', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'reminders' => $reminders,
        ]);
    }

    public function upcoming(Request $request): JsonResponse
    {
        $reminders = Reminder::where('user_id', $request->user()->id)
            ->where('sent', false)
            ->where('reminder_datetime', '<=', now()->addHours(24))
            ->where('reminder_datetime', '>', now())
            ->orderBy('reminder_datetime', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'reminders' => $reminders,
        ]);
    }
}
