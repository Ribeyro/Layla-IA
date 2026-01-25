<?php

namespace App\Http\Api\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Task;
use App\Models\DailyStatistic;
use App\Models\Recommendation;
use App\Services\AIFunctionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenAI\Laravel\Facades\OpenAI;
use Carbon\Carbon;

class ChatController
{
    protected $aiFunctionService;

    public function __construct(AIFunctionService $aiFunctionService)
    {
        $this->aiFunctionService = $aiFunctionService;
    }
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'exists:conversations,id'],
            'by_voice' => ['sometimes', 'boolean'],
        ]);

        DB::beginTransaction();

        try {
            $user = $request->user()->load('avatar');

            $conversation = $this->getOrCreateConversation($request, $user->id);

            $context = $this->getUserContext($user);

            $systemPrompt = $this->buildSystemPrompt($context);

            $conversationHistory = $this->getConversationHistory($conversation->id);

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ...$conversationHistory,
                ['role' => 'user', 'content' => $request->message],
            ];

            // Primera llamada a OpenAI con funciones disponibles
            $result = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => $messages,
                'functions' => $this->aiFunctionService->getFunctionDefinitions(),
                'function_call' => 'auto',
                'max_tokens' => 800,
                'temperature' => 0.7,
            ]);

            $responseMessage = $result->choices[0]->message;

            // Verificar si la IA quiere llamar a una función
            if (isset($responseMessage->functionCall)) {
                $functionName = $responseMessage->functionCall->name;
                $functionArgs = json_decode($responseMessage->functionCall->arguments, true);

                // Ejecutar la función
                $functionResult = $this->aiFunctionService->executeFunction(
                    $user->id,
                    $functionName,
                    $functionArgs
                );

                // Agregar la llamada a función y su resultado al historial de mensajes
                $messages[] = [
                    'role' => 'assistant',
                    'content' => null,
                    'function_call' => [
                        'name' => $functionName,
                        'arguments' => json_encode($functionArgs),
                    ],
                ];

                $messages[] = [
                    'role' => 'function',
                    'name' => $functionName,
                    'content' => json_encode($functionResult),
                ];

                // Segunda llamada a OpenAI para generar respuesta natural basada en el resultado
                $finalResult = OpenAI::chat()->create([
                    'model' => 'gpt-4o-mini',
                    'messages' => $messages,
                    'max_tokens' => 800,
                    'temperature' => 0.7,
                ]);

                $aiResponse = $finalResult->choices[0]->message->content;

                // Guardar info sobre la función ejecutada en el mensaje
                $functionExecutedInfo = " [Función ejecutada: {$functionName}]";
            } else {
                // No se llamó a ninguna función, usar la respuesta directa
                $aiResponse = $responseMessage->content;
                $functionExecutedInfo = '';
            }

            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $request->message,
                'timestamp' => now(),
                'sentiment' => 'neutral',
                'by_voice' => $request->by_voice ?? false,
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $aiResponse,
                'timestamp' => now(),
                'sentiment' => 'neutral',
                'by_voice' => false,
            ]);

            $conversation->increment('message_count', 2);

            $this->updateDailyStatistics($user->id);

            DB::commit();

            $response = [
                'success' => true,
                'response' => $aiResponse,
                'conversation_id' => $conversation->id,
            ];

            // Agregar info de función ejecutada si existe
            if (isset($functionName)) {
                $response['function_executed'] = [
                    'name' => $functionName,
                    'result' => $functionResult,
                ];
            }

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el mensaje: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getOrCreateConversation(Request $request, int $userId): Conversation
    {
        if ($request->has('conversation_id')) {
            return Conversation::where('user_id', $userId)
                ->where('id', $request->conversation_id)
                ->firstOrFail();
        }

        $activeConversation = Conversation::where('user_id', $userId)
            ->where('active', true)
            ->orderBy('start_date', 'desc')
            ->first();

        if ($activeConversation) {
            return $activeConversation;
        }

        return Conversation::create([
            'user_id' => $userId,
            'start_date' => now(),
            'type' => 'general',
            'active' => true,
            'message_count' => 0,
        ]);
    }

    private function getUserContext($user): array
    {
        $upcomingTasks = Task::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('due_date', '>=', now())
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get(['title', 'subject', 'due_date', 'priority', 'status']);

        $recentStats = DailyStatistic::where('user_id', $user->id)
            ->where('date', '>=', now()->subDays(7))
            ->orderBy('date', 'desc')
            ->get();

        $unviewedRecommendations = Recommendation::where('user_id', $user->id)
            ->where('viewed', false)
            ->orderBy('priority', 'desc')
            ->limit(3)
            ->get(['content', 'type', 'priority']);

        return [
            'user' => [
                'name' => $user->name,
                'last_name' => $user->last_name,
                'university' => $user->university,
                'career' => $user->career,
                'cycle' => $user->cycle,
            ],
            'avatar' => $user->avatar ? [
                'emotional_state' => $user->avatar->emotional_state,
                'happiness_level' => $user->avatar->happiness_level,
                'streak_days' => $user->avatar->streak_days,
            ] : null,
            'upcoming_tasks' => $upcomingTasks,
            'recent_statistics' => [
                'avg_completion' => round($recentStats->avg('completion_percentage'), 2),
                'avg_stress' => round($recentStats->avg('stress_level'), 2),
                'avg_motivation' => round($recentStats->avg('motivation_level'), 2),
            ],
            'recommendations' => $unviewedRecommendations,
        ];
    }

    private function buildSystemPrompt(array $context): string
    {
        $userName = $context['user']['name'] ?? 'estudiante';
        $lastName = $context['user']['last_name'] ?? '';
        $fullName = trim($userName . ' ' . $lastName);
        $emotionalState = $context['avatar']['emotional_state'] ?? 'neutral';
        $happinessLevel = $context['avatar']['happiness_level'] ?? 50;
        $streakDays = $context['avatar']['streak_days'] ?? 0;

        $prompt = "Eres Layla, una asistente de IA empática y motivacional para estudiantes universitarios.\n\n";

        $prompt .= "IMPORTANTE: SIEMPRE debes dirigirte al estudiante por su Primer nombre. El estudiante se llama {$userName}.\n";
        $prompt .= "SIEMPRE saluda al estudiante usando su Primer nombre en la primera interacción.\n";
        $prompt .= "NUNCA digas que no tienes acceso a su información - TÚ SÍ TIENES TODA SU INFORMACIÓN.\n\n";

        $prompt .= "=== INFORMACIÓN COMPLETA DEL ESTUDIANTE ===\n\n";
        $prompt .= "DATOS PERSONALES:\n";
        $prompt .= "- Nombre completo: {$fullName}\n";
        $prompt .= "- Primer nombre: {$userName}\n";

        if (!empty($context['user']['university'])) {
            $prompt .= "- Universidad: {$context['user']['university']}\n";
        }

        if (!empty($context['user']['career'])) {
            $prompt .= "- Carrera: {$context['user']['career']}";
            if (!empty($context['user']['cycle'])) {
                $prompt .= ", Ciclo {$context['user']['cycle']}";
            }
            $prompt .= "\n";
        }

        $prompt .= "\nESTADO EMOCIONAL:\n";
        $prompt .= "- Estado actual: {$emotionalState}\n";
        $prompt .= "- Nivel de felicidad: {$happinessLevel}/100\n";
        $prompt .= "- Racha de cumplimiento: {$streakDays} días consecutivos\n";

        if (!empty($context['upcoming_tasks']) && count($context['upcoming_tasks']) > 0) {
            $prompt .= "\nTAREAS PENDIENTES (tienes acceso a esta información):\n";
            foreach ($context['upcoming_tasks'] as $task) {
                $dueDate = Carbon::parse($task->due_date)->format('d/m/Y H:i');
                $prompt .= "- {$task->title}";
                if ($task->subject) {
                    $prompt .= " ({$task->subject})";
                }
                $prompt .= " - Vence: {$dueDate} - Prioridad: {$task->priority} - Estado: {$task->status}\n";
            }
        } else {
            $prompt .= "\nTAREAS PENDIENTES:\n";
            $prompt .= "- El estudiante no tiene tareas pendientes registradas actualmente\n";
        }

        if (!empty($context['recent_statistics']['avg_completion'])) {
            $prompt .= "\nESTADÍSTICAS DE LA ÚLTIMA SEMANA:\n";
            $prompt .= "- Promedio de cumplimiento de tareas: {$context['recent_statistics']['avg_completion']}%\n";
            $prompt .= "- Nivel promedio de estrés: {$context['recent_statistics']['avg_stress']}/10\n";
            $prompt .= "- Nivel promedio de motivación: {$context['recent_statistics']['avg_motivation']}/10\n";
        }

        if (!empty($context['recommendations']) && count($context['recommendations']) > 0) {
            $prompt .= "\nRECOMENDACIONES NO VISTAS:\n";
            foreach ($context['recommendations'] as $rec) {
                $prompt .= "- [{$rec->type}] {$rec->content} (Prioridad: {$rec->priority})\n";
            }
        }

        $prompt .= "\n=== INSTRUCCIONES ESPECÍFICAS ===\n\n";
        $prompt .= "1. SIEMPRE usa el nombre del estudiante ({$userName}) al responder\n";
        $prompt .= "2. Demuestra que conoces su información (universidad, carrera, tareas, etc.)\n";
        $prompt .= "3. Personaliza tus respuestas según su estado emocional actual: {$emotionalState}\n";
        $prompt .= "4. Si te preguntan cómo están, responde basándote en:\n";
        $prompt .= "   - Su nivel de felicidad ({$happinessLevel}/100)\n";
        $prompt .= "   - Su estado emocional ({$emotionalState})\n";
        $prompt .= "   - Sus tareas pendientes\n";
        $prompt .= "   - Sus estadísticas recientes\n";
        $prompt .= "5. Sé empático, motivacional y cercano\n";
        $prompt .= "6. Ofrece ayuda específica con sus tareas si las tienen\n\n";

        if ($emotionalState === 'sad') {
            $prompt .= "⚠️ ALERTA: {$userName} está pasando por un momento difícil (nivel de felicidad: {$happinessLevel}/100).\n";
            $prompt .= "Sé EXTRA empático, motivacional y ofrece apoyo emocional genuino. Reconoce sus dificultades pero anímalo.\n\n";
        } elseif ($emotionalState === 'happy') {
            $prompt .= "✅ ESTADO POSITIVO: {$userName} está en buen estado (nivel de felicidad: {$happinessLevel}/100).\n";
            $prompt .= "Celebra sus logros, felicítalo por su racha de {$streakDays} días y motívalo a mantener el ritmo.\n\n";
        } else {
            $prompt .= "ℹ️ ESTADO NEUTRAL: {$userName} está en un estado estable (nivel de felicidad: {$happinessLevel}/100).\n";
            $prompt .= "Mantén un tono positivo y ofrece apoyo para mejorar su productividad.\n\n";
        }

        $prompt .= "Responde en español, de forma natural, concisa (máximo 3-4 párrafos) y siempre personalizando con su información.";

        return $prompt;
    }

    private function getConversationHistory(int $conversationId): array
    {
        $messages = Message::where('conversation_id', $conversationId)
            ->orderBy('timestamp', 'asc')
            ->limit(10)
            ->get(['role', 'content']);

        return $messages->map(function ($message) {
            return [
                'role' => $message->role,
                'content' => $message->content,
            ];
        })->toArray();
    }

    private function updateDailyStatistics(int $userId): void
    {
        $today = now()->toDateString();

        DailyStatistic::where('user_id', $userId)
            ->where('date', $today)
            ->increment('ai_interactions');
    }

    public function getContext(Request $request): JsonResponse
    {
        $user = $request->user()->load('avatar');
        $context = $this->getUserContext($user);
        $systemPrompt = $this->buildSystemPrompt($context);

        return response()->json([
            'success' => true,
            'context' => $context,
            'system_prompt' => $systemPrompt,
        ]);
    }
}

