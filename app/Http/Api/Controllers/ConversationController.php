<?php

namespace App\Http\Api\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController
{
    public function index(Request $request): JsonResponse
    {
        $query = Conversation::where('user_id', $request->user()->id);

        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $conversations = $query->orderBy('start_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['sometimes', 'in:academic,emotional,general'],
        ]);

        $conversation = Conversation::create([
            'user_id' => $request->user()->id,
            'start_date' => now(),
            'type' => $request->type ?? 'general',
            'active' => true,
            'message_count' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversación creada exitosamente',
            'conversation' => $conversation,
        ], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $conversation = Conversation::where('user_id', $request->user()->id)
            ->with('messages')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $conversation = Conversation::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'type' => ['sometimes', 'in:academic,emotional,general'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $conversation->update($request->only(['type', 'active']));

        return response()->json([
            'success' => true,
            'message' => 'Conversación actualizada',
            'conversation' => $conversation->fresh(),
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $conversation = Conversation::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversación eliminada exitosamente',
        ]);
    }

    public function endConversation(Request $request, $id): JsonResponse
    {
        $conversation = Conversation::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $conversation->update([
            'active' => false,
            'end_date' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Conversación finalizada',
            'conversation' => $conversation->fresh(),
        ]);
    }

    public function addMessage(Request $request, $id): JsonResponse
    {
        $conversation = Conversation::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'role' => ['required', 'in:user,assistant'],
            'content' => ['required', 'string'],
            'sentiment' => ['sometimes', 'in:positive,neutral,negative'],
            'by_voice' => ['sometimes', 'boolean'],
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => $request->role,
            'content' => $request->content,
            'timestamp' => now(),
            'sentiment' => $request->sentiment ?? 'neutral',
            'by_voice' => $request->by_voice ?? false,
        ]);

        $conversation->increment('message_count');

        return response()->json([
            'success' => true,
            'message' => 'Mensaje agregado exitosamente',
            'data' => $message,
        ], 201);
    }

    public function getMessages(Request $request, $id): JsonResponse
    {
        $conversation = Conversation::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $messages = Message::where('conversation_id', $conversation->id)
            ->orderBy('timestamp', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    public function activeConversation(Request $request): JsonResponse
    {
        $conversation = Conversation::where('user_id', $request->user()->id)
            ->where('active', true)
            ->with('messages')
            ->orderBy('start_date', 'desc')
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'user_id' => $request->user()->id,
                'start_date' => now(),
                'type' => 'general',
                'active' => true,
                'message_count' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'conversation' => $conversation->load('messages'),
        ]);
    }

    public function recentConversations(Request $request): JsonResponse
    {
        $conversations = Conversation::where('user_id', $request->user()->id)
            ->orderBy('start_date', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }
}
