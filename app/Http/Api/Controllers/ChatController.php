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

                $aiResponse = $this->cleanMarkdownFormat($finalResult->choices[0]->message->content);

                // Guardar info sobre la función ejecutada en el mensaje
                $functionExecutedInfo = " [Función ejecutada: {$functionName}]";
            } else {
                // No se llamó a ninguna función, usar la respuesta directa
                $aiResponse = $this->cleanMarkdownFormat($responseMessage->content);
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
        $emotionalState = $context['avatar']['emotional_state'] ?? 'neutral';
        $happinessLevel = $context['avatar']['happiness_level'] ?? 50;
        $streakDays = $context['avatar']['streak_days'] ?? 0;

        $prompt = "Eres Layla, una asistente de IA empática y motivacional para estudiantes universitarios.\n\n";

        // Fecha y hora actual para contexto temporal
        $currentDate = Carbon::now();
        $prompt .= "=== FECHA Y HORA ACTUAL ===\n";
        $prompt .= "- Fecha actual: " . $currentDate->format('d/m/Y') . "\n";
        $prompt .= "- Hora actual: " . $currentDate->format('H:i') . "\n";
        $prompt .= "- Día de la semana: " . $currentDate->locale('es')->dayName . "\n";
        $prompt .= "- Año actual: " . $currentDate->year . "\n\n";
        $prompt .= "IMPORTANTE SOBRE FECHAS: Cuando el usuario mencione 'hoy', 'mañana', 'esta semana', etc., ";
        $prompt .= "SIEMPRE usa la fecha actual ({$currentDate->format('Y-m-d')}) como referencia. ";
        $prompt .= "Por ejemplo, si hoy es {$currentDate->format('d/m/Y')} y el usuario dice 'hoy a las 8 PM', ";
        $prompt .= "la fecha debe ser {$currentDate->format('Y-m-d')} 20:00:00.\n\n";

        $prompt .= "IMPORTANTE: El estudiante se llama {$userName}. SOLO usa este nombre, NUNCA uses apellidos.\n";
        $prompt .= "SIEMPRE dirígete al estudiante SOLO por su nombre: {$userName}.\n";
        $prompt .= "NUNCA digas que no tienes acceso a su información - TÚ SÍ TIENES TODA SU INFORMACIÓN.\n\n";

        $prompt .= "=== INFORMACIÓN DEL ESTUDIANTE ===\n\n";
        $prompt .= "DATOS PERSONALES:\n";
        $prompt .= "- Nombre: {$userName}\n";

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

        $prompt .= "=== FUNCIONES DISPONIBLES (CRUD COMPLETO) ===\n\n";
        $prompt .= "Tienes funciones para gestionar TAREAS, HABITOS y RECORDATORIOS. USA LA FUNCION APROPIADA segun lo que pida el estudiante:\n\n";

        $prompt .= "--- TAREAS ---\n";
        $prompt .= "- create_task_with_reminder: Crear tarea nueva (con o sin recordatorio)\n";
        $prompt .= "  Ejemplos: 'tengo examen manana', 'registra una reunion', 'crea una tarea'\n";
        $prompt .= "- list_tasks: Ver lista de tareas\n";
        $prompt .= "  Ejemplos: 'que tareas tengo', 'muestrame mis pendientes', 'ver mis tareas'\n";
        $prompt .= "- get_task: Ver detalles de una tarea especifica\n";
        $prompt .= "  Ejemplos: 'detalles de la tarea 5', 'info de mi tarea'\n";
        $prompt .= "- update_task: Modificar una tarea (titulo, fecha, prioridad, estado, etc)\n";
        $prompt .= "  Ejemplos: 'cambia la fecha de la tarea 3', 'marca como completada', 'actualiza el titulo'\n";
        $prompt .= "- delete_task: Eliminar una tarea\n";
        $prompt .= "  Ejemplos: 'elimina la tarea 2', 'borra mi tarea de matematicas'\n\n";

        $prompt .= "--- HABITOS ---\n";
        $prompt .= "- create_habit: Crear habito nuevo\n";
        $prompt .= "  Ejemplos: 'quiero crear el habito de estudiar', 'nuevo habito de ejercicio'\n";
        $prompt .= "- list_habits: Ver lista de habitos\n";
        $prompt .= "  Ejemplos: 'que habitos tengo', 'muestrame mis rutinas'\n";
        $prompt .= "- get_habit: Ver detalles de un habito\n";
        $prompt .= "  Ejemplos: 'detalles del habito 1'\n";
        $prompt .= "- update_habit: Modificar un habito\n";
        $prompt .= "  Ejemplos: 'cambia la hora del habito', 'desactiva el habito 2'\n";
        $prompt .= "- delete_habit: Eliminar un habito\n";
        $prompt .= "  Ejemplos: 'elimina el habito 3', 'borra mi habito de lectura'\n";
        $prompt .= "- complete_habit: Marcar habito como completado hoy\n";
        $prompt .= "  Ejemplos: 'complete mi habito de ejercicio', 'ya hice mi rutina'\n\n";

        $prompt .= "--- RECORDATORIOS ---\n";
        $prompt .= "- create_reminder: Crear recordatorio\n";
        $prompt .= "  Ejemplos: 'recuerdame llamar a las 3', 'ponme un recordatorio'\n";
        $prompt .= "- list_reminders: Ver lista de recordatorios\n";
        $prompt .= "  Ejemplos: 'que recordatorios tengo', 'mis alarmas pendientes'\n";
        $prompt .= "- get_reminder: Ver detalles de un recordatorio\n";
        $prompt .= "- update_reminder: Modificar un recordatorio\n";
        $prompt .= "  Ejemplos: 'cambia la hora del recordatorio', 'actualiza el mensaje'\n";
        $prompt .= "- delete_reminder: Eliminar un recordatorio\n";
        $prompt .= "  Ejemplos: 'elimina el recordatorio 1', 'cancela mi alarma'\n\n";

        $prompt .= "=== REGLAS PARA ELIMINAR Y ACTUALIZAR ===\n\n";

        $prompt .= "--- ELIMINACION (requiere confirmacion) ---\n";
        $prompt .= "PASO 1: Si el usuario dice 'elimina/borra [algo]' SIN un ID numerico:\n";
        $prompt .= "   -> Lista los registros para mostrar los IDs disponibles\n";
        $prompt .= "   -> Pregunta: 'Cual quieres eliminar? Dime el ID o escribe: confirmo eliminar [ID]'\n\n";
        $prompt .= "PASO 2: EJECUTA la eliminacion SOLO cuando el usuario:\n";
        $prompt .= "   -> Diga un ID numerico explicito: 'el 5', 'la 7', 'elimina el 3'\n";
        $prompt .= "   -> Use palabras de confirmacion: 'confirmo', 'si elimina', 'dale', 'ok elimina', 'hazlo'\n";
        $prompt .= "   -> Escriba: 'confirmo eliminar [ID]' o 'si, elimina el [ID]'\n\n";
        $prompt .= "IMPORTANTE: Cuando el usuario responda con un ID (ej: '5', 'el 5', 'la tarea 5'), EJECUTA delete_task INMEDIATAMENTE. NO vuelvas a preguntar.\n\n";

        $prompt .= "--- ACTUALIZACION (sin confirmacion) ---\n";
        $prompt .= "Si el usuario dice 'actualiza/cambia/modifica [algo]':\n";
        $prompt .= "   -> Si da un ID: Ejecuta update_task/update_habit/update_reminder directamente\n";
        $prompt .= "   -> Si NO da ID: Lista los registros, cuando responda con ID, ejecuta la actualizacion\n";
        $prompt .= "   -> NO pidas confirmacion para actualizar, solo ejecuta\n\n";

        $prompt .= "--- EJEMPLOS ---\n";
        $prompt .= "Usuario: 'elimina mi reunion' -> Tu: lista tareas, pregunta cual\n";
        $prompt .= "Usuario: 'el 5' -> Tu: EJECUTA delete_task(task_id=5) AHORA\n";
        $prompt .= "Usuario: 'elimina la tarea 3' -> Tu: EJECUTA delete_task(task_id=3) AHORA\n";
        $prompt .= "Usuario: 'actualiza la tarea 2, cambia fecha a manana' -> Tu: EJECUTA update_task AHORA\n\n";

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

    private function cleanMarkdownFormat(string $text): string
    {
        // Eliminar negritas **texto** o __texto__
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text);
        $text = preg_replace('/__(.*?)__/', '$1', $text);

        // Eliminar cursivas *texto* o _texto_
        $text = preg_replace('/\*(.*?)\*/', '$1', $text);
        $text = preg_replace('/_(.*?)_/', '$1', $text);

        // Eliminar encabezados markdown (# ## ### etc.)
        $text = preg_replace('/^#{1,6}\s*/m', '', $text);

        // Eliminar listas con viñetas (- o *)
        $text = preg_replace('/^[\-\*]\s+/m', '', $text);

        // Eliminar código inline `texto`
        $text = preg_replace('/`(.*?)`/', '$1', $text);

        // Eliminar bloques de código ```texto```
        $text = preg_replace('/```[\s\S]*?```/', '', $text);

        // Eliminar enlaces [texto](url) -> texto
        $text = preg_replace('/\[(.*?)\]\(.*?\)/', '$1', $text);

        // Convertir saltos de línea a espacios
        $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);

        // Limpiar múltiples espacios en blanco
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
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

