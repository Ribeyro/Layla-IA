<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Reminder;
use App\Models\Habit;
use Carbon\Carbon;

class AIFunctionService
{
    public function createTask(int $userId, array $parameters): array
    {
        try {
            $dueDate = $this->parseDateTime($parameters['due_date'] ?? null);

            $task = Task::create([
                'user_id' => $userId,
                'title' => $parameters['title'],
                'description' => $parameters['description'] ?? null,
                'type' => $parameters['type'] ?? 'task',
                'due_date' => $dueDate,
                'priority' => $parameters['priority'] ?? 'medium',
                'subject' => $parameters['subject'] ?? null,
                'status' => 'pending',
                'progress_percentage' => 0,
            ]);

            return [
                'success' => true,
                'message' => "Tarea '{$task->title}' creada exitosamente para el " . $dueDate->format('d/m/Y H:i'),
                'task_id' => $task->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al crear la tarea: ' . $e->getMessage(),
            ];
        }
    }

    public function createReminder(int $userId, array $parameters): array
    {
        try {
            $reminderDatetime = $this->parseDateTime($parameters['reminder_datetime']);

            $reminder = Reminder::create([
                'user_id' => $userId,
                'task_id' => $parameters['task_id'] ?? null,
                'reminder_datetime' => $reminderDatetime,
                'message' => $parameters['message'],
                'type' => $parameters['type'] ?? 'notification',
                'sent' => false,
            ]);

            return [
                'success' => true,
                'message' => "Recordatorio creado para el " . $reminderDatetime->format('d/m/Y H:i'),
                'reminder_id' => $reminder->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al crear el recordatorio: ' . $e->getMessage(),
            ];
        }
    }

    public function createTaskWithReminder(int $userId, array $parameters): array
    {
        try {
            $dueDate = $this->parseDateTime($parameters['due_date']);

            // Crear la tarea
            $task = Task::create([
                'user_id' => $userId,
                'title' => $parameters['title'],
                'description' => $parameters['description'] ?? null,
                'type' => $parameters['type'] ?? 'task',
                'due_date' => $dueDate,
                'priority' => $parameters['priority'] ?? 'medium',
                'subject' => $parameters['subject'] ?? null,
                'status' => 'pending',
                'progress_percentage' => 0,
            ]);

            // Calcular fecha del recordatorio
            $minutesBefore = $parameters['remind_minutes_before'] ?? 120; // 2 horas por defecto
            $reminderDatetime = $dueDate->copy()->subMinutes($minutesBefore);

            // Solo crear recordatorio si la fecha es futura
            if ($reminderDatetime->isFuture()) {
                $reminder = Reminder::create([
                    'user_id' => $userId,
                    'task_id' => $task->id,
                    'reminder_datetime' => $reminderDatetime,
                    'message' => "Recordatorio: {$task->title} - Vence en {$minutesBefore} minutos",
                    'type' => $parameters['reminder_type'] ?? 'notification',
                    'sent' => false,
                ]);

                return [
                    'success' => true,
                    'message' => "Tarea '{$task->title}' creada para el " . $dueDate->format('d/m/Y H:i') .
                                 " con recordatorio " . ($minutesBefore / 60) . " horas antes",
                    'task_id' => $task->id,
                    'reminder_id' => $reminder->id,
                ];
            }

            return [
                'success' => true,
                'message' => "Tarea '{$task->title}' creada para el " . $dueDate->format('d/m/Y H:i'),
                'task_id' => $task->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al crear la tarea con recordatorio: ' . $e->getMessage(),
            ];
        }
    }

    public function listTasks(int $userId, array $parameters): array
    {
        try {
            $query = Task::where('user_id', $userId);

            if (isset($parameters['status'])) {
                $query->where('status', $parameters['status']);
            }

            if (isset($parameters['limit'])) {
                $query->limit($parameters['limit']);
            }

            $tasks = $query->orderBy('due_date', 'asc')->get();

            $taskList = $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'due_date' => $task->due_date->format('d/m/Y H:i'),
                    'priority' => $task->priority,
                    'status' => $task->status,
                ];
            })->toArray();

            return [
                'success' => true,
                'tasks' => $taskList,
                'message' => "Encontré " . count($taskList) . " tarea(s)",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al listar tareas: ' . $e->getMessage(),
            ];
        }
    }

    public function updateTaskStatus(int $userId, array $parameters): array
    {
        try {
            $task = Task::where('user_id', $userId)
                ->where('id', $parameters['task_id'])
                ->firstOrFail();

            $task->update([
                'status' => $parameters['status'],
                'completed_at' => $parameters['status'] === 'completed' ? now() : null,
            ]);

            return [
                'success' => true,
                'message' => "Tarea '{$task->title}' actualizada a estado '{$parameters['status']}'",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar tarea: ' . $e->getMessage(),
            ];
        }
    }

    public function createHabit(int $userId, array $parameters): array
    {
        try {
            $habit = Habit::create([
                'user_id' => $userId,
                'name' => $parameters['name'],
                'description' => $parameters['description'] ?? null,
                'frequency' => $parameters['frequency'] ?? 'daily',
                'preferred_time' => $parameters['preferred_time'] ?? null,
                'active' => true,
                'current_streak' => 0,
                'max_streak' => 0,
            ]);

            return [
                'success' => true,
                'message' => "Hábito '{$habit->name}' creado exitosamente con frecuencia {$habit->frequency}",
                'habit_id' => $habit->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al crear hábito: ' . $e->getMessage(),
            ];
        }
    }

    private function parseDateTime(?string $dateTimeString): Carbon
    {
        if (!$dateTimeString) {
            return now()->addDay();
        }

        try {
            // Intentar parsear la fecha/hora
            return Carbon::parse($dateTimeString);
        } catch (\Exception $e) {
            // Si falla, devolver mañana a las 9 AM
            return now()->addDay()->setTime(9, 0);
        }
    }

    public function getFunctionDefinitions(): array
    {
        return [
            [
                'name' => 'create_task_with_reminder',
                'description' => 'Crea una nueva tarea académica con un recordatorio automático. Usa esta función cuando el usuario mencione que tiene una tarea, examen, trabajo o actividad pendiente y quiera que le recuerden.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                            'description' => 'Título o nombre de la tarea',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Descripción detallada de la tarea',
                        ],
                        'due_date' => [
                            'type' => 'string',
                            'description' => 'Fecha y hora de vencimiento en formato ISO 8601 (YYYY-MM-DD HH:mm:ss). Ejemplo: "2026-01-25 10:00:00"',
                        ],
                        'priority' => [
                            'type' => 'string',
                            'enum' => ['low', 'medium', 'high'],
                            'description' => 'Prioridad de la tarea',
                        ],
                        'type' => [
                            'type' => 'string',
                            'enum' => ['task', 'exam', 'work', 'class', 'other'],
                            'description' => 'Tipo de tarea',
                        ],
                        'subject' => [
                            'type' => 'string',
                            'description' => 'Materia o curso relacionado',
                        ],
                        'remind_minutes_before' => [
                            'type' => 'integer',
                            'description' => 'Minutos antes del vencimiento para enviar el recordatorio. Por defecto 120 minutos (2 horas)',
                        ],
                        'reminder_type' => [
                            'type' => 'string',
                            'enum' => ['voice', 'notification', 'both'],
                            'description' => 'Tipo de recordatorio',
                        ],
                    ],
                    'required' => ['title', 'due_date'],
                ],
            ],
            [
                'name' => 'create_reminder',
                'description' => 'Crea un recordatorio simple sin tarea asociada. Usa esta función cuando el usuario solo quiera un recordatorio para algo específico (llamada, reunión, etc.)',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'message' => [
                            'type' => 'string',
                            'description' => 'Mensaje del recordatorio',
                        ],
                        'reminder_datetime' => [
                            'type' => 'string',
                            'description' => 'Fecha y hora del recordatorio en formato ISO 8601',
                        ],
                        'type' => [
                            'type' => 'string',
                            'enum' => ['voice', 'notification', 'both'],
                            'description' => 'Tipo de recordatorio',
                        ],
                    ],
                    'required' => ['message', 'reminder_datetime'],
                ],
            ],
            [
                'name' => 'list_tasks',
                'description' => 'Lista las tareas del usuario. Usa esta función cuando el usuario pregunte por sus tareas pendientes, completadas o quiera ver su lista de tareas.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => [
                            'type' => 'string',
                            'enum' => ['pending', 'in_progress', 'completed', 'overdue'],
                            'description' => 'Filtrar por estado de la tarea',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Límite de tareas a mostrar',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'update_task_status',
                'description' => 'Actualiza el estado de una tarea existente. Usa esta función cuando el usuario diga que completó, empezó o canceló una tarea.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'task_id' => [
                            'type' => 'integer',
                            'description' => 'ID de la tarea a actualizar',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['pending', 'in_progress', 'completed', 'overdue'],
                            'description' => 'Nuevo estado de la tarea',
                        ],
                    ],
                    'required' => ['task_id', 'status'],
                ],
            ],
            [
                'name' => 'create_habit',
                'description' => 'Crea un nuevo hábito para el usuario. Usa esta función cuando el usuario quiera establecer una rutina o hábito diario/semanal/mensual.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Nombre del hábito',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Descripción del hábito',
                        ],
                        'frequency' => [
                            'type' => 'string',
                            'enum' => ['daily', 'weekly', 'monthly'],
                            'description' => 'Frecuencia del hábito',
                        ],
                        'preferred_time' => [
                            'type' => 'string',
                            'description' => 'Hora preferida en formato HH:mm:ss',
                        ],
                    ],
                    'required' => ['name', 'frequency'],
                ],
            ],
        ];
    }

    public function executeFunction(int $userId, string $functionName, array $arguments): array
    {
        switch ($functionName) {
            case 'create_task_with_reminder':
                return $this->createTaskWithReminder($userId, $arguments);

            case 'create_reminder':
                return $this->createReminder($userId, $arguments);

            case 'list_tasks':
                return $this->listTasks($userId, $arguments);

            case 'update_task_status':
                return $this->updateTaskStatus($userId, $arguments);

            case 'create_habit':
                return $this->createHabit($userId, $arguments);

            default:
                return [
                    'success' => false,
                    'message' => "Función '{$functionName}' no reconocida",
                ];
        }
    }
}
