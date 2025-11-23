<?php

namespace App\Http\Api\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class ChatController
{
    /**
     * Procesar mensaje del chat con OpenAI
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $result = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Eres Layla, una asistente virtual amigable y útil. Responde de manera concisa y clara en español. Ayudas a los usuarios con sus tareas diarias, recordatorios y consultas generales.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $request->message,
                    ],
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);

            $response = $result->choices[0]->message->content;

            return response()->json([
                'success' => true,
                'response' => $response,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el mensaje: ' . $e->getMessage(),
            ], 500);
        }
    }
}
