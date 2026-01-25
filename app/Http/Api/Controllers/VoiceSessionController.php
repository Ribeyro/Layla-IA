<?php

namespace App\Http\Api\Controllers;

use App\Models\VoiceSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VoiceSessionController
{
    public function index(Request $request): JsonResponse
    {
        $query = VoiceSession::where('user_id', $request->user()->id);

        if ($request->has('successful')) {
            $query->where('successful', $request->boolean('successful'));
        }

        if ($request->has('from_date')) {
            $query->where('start_datetime', '>=', $request->from_date);
        }

        $sessions = $query->orderBy('start_datetime', 'desc')->get();

        return response()->json([
            'success' => true,
            'voice_sessions' => $sessions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'transcription' => ['nullable', 'string'],
            'ai_response' => ['nullable', 'string'],
        ]);

        $session = VoiceSession::create([
            'user_id' => $request->user()->id,
            'start_datetime' => now(),
            'transcription' => $request->transcription,
            'ai_response' => $request->ai_response,
            'command_count' => 0,
            'successful' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sesión de voz iniciada',
            'voice_session' => $session,
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $session = VoiceSession::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'voice_session' => $session,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $session = VoiceSession::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'end_datetime' => ['sometimes', 'date'],
            'transcription' => ['sometimes', 'string'],
            'ai_response' => ['sometimes', 'string'],
            'successful' => ['sometimes', 'boolean'],
            'command_count' => ['sometimes', 'integer', 'min:0'],
        ]);

        $updateData = $request->only([
            'end_datetime',
            'transcription',
            'ai_response',
            'successful',
            'command_count',
        ]);

        if ($request->has('end_datetime') && $session->start_datetime) {
            $startTime = Carbon::parse($session->start_datetime);
            $endTime = Carbon::parse($request->end_datetime);
            $updateData['duration_seconds'] = $endTime->diffInSeconds($startTime);
        }

        $session->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Sesión de voz actualizada',
            'voice_session' => $session->fresh(),
        ]);
    }

    public function endSession(Request $request, $id): JsonResponse
    {
        $session = VoiceSession::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $endTime = now();
        $startTime = Carbon::parse($session->start_datetime);
        $durationSeconds = $endTime->diffInSeconds($startTime);

        $session->update([
            'end_datetime' => $endTime,
            'duration_seconds' => $durationSeconds,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sesión de voz finalizada',
            'voice_session' => $session->fresh(),
        ]);
    }

    public function incrementCommands(Request $request, $id): JsonResponse
    {
        $session = VoiceSession::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $session->increment('command_count');

        return response()->json([
            'success' => true,
            'voice_session' => $session->fresh(),
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $sessions = VoiceSession::where('user_id', $request->user()->id)
            ->orderBy('start_datetime', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'voice_sessions' => $sessions,
        ]);
    }
}
