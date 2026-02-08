<?php

namespace App\Services;

use App\Models\Task;
use App\Models\Reminder;
use App\Models\Habit;
use Carbon\Carbon;

class AIFunctionService
{
    // ==================== TAREAS (TASKS) ====================

    public function createTaskWithReminder(int $userId, array $parameters): array
    {
        try {
            $dueDate = $this->parseDateTime($parameters['due_date']);

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

            $minutesBefore = $parameters['remind_minutes_before'] ?? 120;
            $reminderDatetime = $dueDate->copy()->subMinutes($minutesBefore);

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
                'message' => 'Error al crear la tarea: ' . $e->getMessage(),
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

            if (isset($parameters['priority'])) {
                $query->where('priority', $parameters['priority']);
            }

            // Busqueda por nombre/titulo
            if (isset($parameters['search'])) {
                $query->where('title', 'like', '%' . $parameters['search'] . '%');
            }

            $limit = $parameters['limit'] ?? 10;
            $tasks = $query->orderBy('due_date', 'asc')->limit($limit)->get();

            $taskList = $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'due_date' => $task->due_date ? $task->due_date->format('d/m/Y H:i') : null,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'type' => $task->type,
                    'subject' => $task->subject,
                ];
            })->toArray();

            return [
                'success' => true,
                'tasks' => $taskList,
                'total' => count($taskList),
                'message' => count($taskList) > 0
                    ? "Encontre " . count($taskList) . " tarea(s)"
                    : "No tienes tareas registradas",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al listar tareas: ' . $e->getMessage(),
            ];
        }
    }

    public function getTask(int $userId, array $parameters): array
    {
        try {
            $task = Task::where('user_id', $userId)
                ->where('id', $parameters['task_id'])
                ->first();

            if (!$task) {
                return [
                    'success' => false,
                    'message' => "No encontre la tarea con ID {$parameters['task_id']}",
                ];
            }

            return [
                'success' => true,
                'task' => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'due_date' => $task->due_date ? $task->due_date->format('d/m/Y H:i') : null,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'type' => $task->type,
                    'subject' => $task->subject,
                    'progress_percentage' => $task->progress_percentage,
                ],
                'message' => "Detalles de la tarea '{$task->title}'",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener tarea: ' . $e->getMessage(),
            ];
        }
    }

    public function updateTask(int $userId, array $parameters): array
    {
        try {
            $task = Task::where('user_id', $userId)
                ->where('id', $parameters['task_id'])
                ->first();

            if (!$task) {
                return [
                    'success' => false,
                    'message' => "No encontre la tarea con ID {$parameters['task_id']}",
                ];
            }

            $updateData = [];
            $changes = [];

            if (isset($parameters['title'])) {
                $updateData['title'] = $parameters['title'];
                $changes[] = "titulo a '{$parameters['title']}'";
            }

            if (isset($parameters['description'])) {
                $updateData['description'] = $parameters['description'];
                $changes[] = "descripcion actualizada";
            }

            if (isset($parameters['due_date'])) {
                $updateData['due_date'] = $this->parseDateTime($parameters['due_date']);
                $changes[] = "fecha a " . $updateData['due_date']->format('d/m/Y H:i');
            }

            if (isset($parameters['priority'])) {
                $updateData['priority'] = $parameters['priority'];
                $changes[] = "prioridad a '{$parameters['priority']}'";
            }

            if (isset($parameters['status'])) {
                $updateData['status'] = $parameters['status'];
                $changes[] = "estado a '{$parameters['status']}'";
                if ($parameters['status'] === 'completed') {
                    $updateData['completed_at'] = now();
                }
            }

            if (isset($parameters['subject'])) {
                $updateData['subject'] = $parameters['subject'];
                $changes[] = "materia a '{$parameters['subject']}'";
            }

            if (isset($parameters['progress_percentage'])) {
                $updateData['progress_percentage'] = $parameters['progress_percentage'];
                $changes[] = "progreso a {$parameters['progress_percentage']}%";
            }

            if (empty($updateData)) {
                return [
                    'success' => false,
                    'message' => 'No se especificaron campos para actualizar',
                ];
            }

            $task->update($updateData);

            return [
                'success' => true,
                'message' => "Tarea '{$task->title}' actualizada: " . implode(', ', $changes),
                'task_id' => $task->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar tarea: ' . $e->getMessage(),
            ];
        }
    }

    public function deleteTask(int $userId, array $parameters): array
    {
        try {
            $task = Task::where('user_id', $userId)
                ->where('id', $parameters['task_id'])
                ->first();

            if (!$task) {
                return [
                    'success' => false,
                    'message' => "No encontre la tarea con ID {$parameters['task_id']}",
                ];
            }

            $taskTitle = $task->title;

            // Eliminar recordatorios asociados
            Reminder::where('task_id', $task->id)->delete();

            $task->delete();

            return [
                'success' => true,
                'message' => "Tarea '{$taskTitle}' eliminada correctamente junto con sus recordatorios",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar tarea: ' . $e->getMessage(),
            ];
        }
    }

    // ==================== HABITOS (HABITS) ====================

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
                'message' => "Habito '{$habit->name}' creado con frecuencia {$habit->frequency}",
                'habit_id' => $habit->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al crear habito: ' . $e->getMessage(),
            ];
        }
    }

    public function listHabits(int $userId, array $parameters): array
    {
        try {
            $query = Habit::where('user_id', $userId);

            if (isset($parameters['active'])) {
                $query->where('active', $parameters['active']);
            }

            if (isset($parameters['frequency'])) {
                $query->where('frequency', $parameters['frequency']);
            }

            // Busqueda por nombre
            if (isset($parameters['search'])) {
                $query->where('name', 'like', '%' . $parameters['search'] . '%');
            }

            $habits = $query->orderBy('created_at', 'desc')->get();

            $habitList = $habits->map(function ($habit) {
                return [
                    'id' => $habit->id,
                    'name' => $habit->name,
                    'description' => $habit->description,
                    'frequency' => $habit->frequency,
                    'preferred_time' => $habit->preferred_time,
                    'active' => $habit->active,
                    'current_streak' => $habit->current_streak,
                    'max_streak' => $habit->max_streak,
                ];
            })->toArray();

            return [
                'success' => true,
                'habits' => $habitList,
                'total' => count($habitList),
                'message' => count($habitList) > 0
                    ? "Encontre " . count($habitList) . " habito(s)"
                    : "No tienes habitos registrados",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al listar habitos: ' . $e->getMessage(),
            ];
        }
    }

    public function getHabit(int $userId, array $parameters): array
    {
        try {
            $habit = Habit::where('user_id', $userId)
                ->where('id', $parameters['habit_id'])
                ->first();

            if (!$habit) {
                return [
                    'success' => false,
                    'message' => "No encontre el habito con ID {$parameters['habit_id']}",
                ];
            }

            return [
                'success' => true,
                'habit' => [
                    'id' => $habit->id,
                    'name' => $habit->name,
                    'description' => $habit->description,
                    'frequency' => $habit->frequency,
                    'preferred_time' => $habit->preferred_time,
                    'active' => $habit->active,
                    'current_streak' => $habit->current_streak,
                    'max_streak' => $habit->max_streak,
                ],
                'message' => "Detalles del habito '{$habit->name}'",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener habito: ' . $e->getMessage(),
            ];
        }
    }

    public function updateHabit(int $userId, array $parameters): array
    {
        try {
            $habit = Habit::where('user_id', $userId)
                ->where('id', $parameters['habit_id'])
                ->first();

            if (!$habit) {
                return [
                    'success' => false,
                    'message' => "No encontre el habito con ID {$parameters['habit_id']}",
                ];
            }

            $updateData = [];
            $changes = [];

            if (isset($parameters['name'])) {
                $updateData['name'] = $parameters['name'];
                $changes[] = "nombre a '{$parameters['name']}'";
            }

            if (isset($parameters['description'])) {
                $updateData['description'] = $parameters['description'];
                $changes[] = "descripcion actualizada";
            }

            if (isset($parameters['frequency'])) {
                $updateData['frequency'] = $parameters['frequency'];
                $changes[] = "frecuencia a '{$parameters['frequency']}'";
            }

            if (isset($parameters['preferred_time'])) {
                $updateData['preferred_time'] = $parameters['preferred_time'];
                $changes[] = "hora preferida a '{$parameters['preferred_time']}'";
            }

            if (isset($parameters['active'])) {
                $updateData['active'] = $parameters['active'];
                $changes[] = $parameters['active'] ? "activado" : "desactivado";
            }

            if (empty($updateData)) {
                return [
                    'success' => false,
                    'message' => 'No se especificaron campos para actualizar',
                ];
            }

            $habit->update($updateData);

            return [
                'success' => true,
                'message' => "Habito '{$habit->name}' actualizado: " . implode(', ', $changes),
                'habit_id' => $habit->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar habito: ' . $e->getMessage(),
            ];
        }
    }

    public function deleteHabit(int $userId, array $parameters): array
    {
        try {
            $habit = Habit::where('user_id', $userId)
                ->where('id', $parameters['habit_id'])
                ->first();

            if (!$habit) {
                return [
                    'success' => false,
                    'message' => "No encontre el habito con ID {$parameters['habit_id']}",
                ];
            }

            $habitName = $habit->name;
            $habit->delete();

            return [
                'success' => true,
                'message' => "Habito '{$habitName}' eliminado correctamente",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar habito: ' . $e->getMessage(),
            ];
        }
    }

    public function completeHabit(int $userId, array $parameters): array
    {
        try {
            $habit = Habit::where('user_id', $userId)
                ->where('id', $parameters['habit_id'])
                ->first();

            if (!$habit) {
                return [
                    'success' => false,
                    'message' => "No encontre el habito con ID {$parameters['habit_id']}",
                ];
            }

            $habit->current_streak += 1;
            if ($habit->current_streak > $habit->max_streak) {
                $habit->max_streak = $habit->current_streak;
            }
            $habit->save();

            return [
                'success' => true,
                'message' => "Habito '{$habit->name}' completado. Racha actual: {$habit->current_streak} dias",
                'habit_id' => $habit->id,
                'current_streak' => $habit->current_streak,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al completar habito: ' . $e->getMessage(),
            ];
        }
    }

    // ==================== RECORDATORIOS (REMINDERS) ====================

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
                'message' => 'Error al crear recordatorio: ' . $e->getMessage(),
            ];
        }
    }

    public function listReminders(int $userId, array $parameters): array
    {
        try {
            $query = Reminder::where('user_id', $userId);

            if (isset($parameters['sent'])) {
                $query->where('sent', $parameters['sent']);
            }

            if (isset($parameters['pending']) && $parameters['pending']) {
                $query->where('sent', false)->where('reminder_datetime', '>=', now());
            }

            // Busqueda por mensaje
            if (isset($parameters['search'])) {
                $query->where('message', 'like', '%' . $parameters['search'] . '%');
            }

            $reminders = $query->orderBy('reminder_datetime', 'asc')->get();

            $reminderList = $reminders->map(function ($reminder) {
                return [
                    'id' => $reminder->id,
                    'message' => $reminder->message,
                    'reminder_datetime' => $reminder->reminder_datetime->format('d/m/Y H:i'),
                    'type' => $reminder->type,
                    'sent' => $reminder->sent,
                    'task_id' => $reminder->task_id,
                ];
            })->toArray();

            return [
                'success' => true,
                'reminders' => $reminderList,
                'total' => count($reminderList),
                'message' => count($reminderList) > 0
                    ? "Encontre " . count($reminderList) . " recordatorio(s)"
                    : "No tienes recordatorios registrados",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al listar recordatorios: ' . $e->getMessage(),
            ];
        }
    }

    public function getReminder(int $userId, array $parameters): array
    {
        try {
            $reminder = Reminder::where('user_id', $userId)
                ->where('id', $parameters['reminder_id'])
                ->first();

            if (!$reminder) {
                return [
                    'success' => false,
                    'message' => "No encontre el recordatorio con ID {$parameters['reminder_id']}",
                ];
            }

            return [
                'success' => true,
                'reminder' => [
                    'id' => $reminder->id,
                    'message' => $reminder->message,
                    'reminder_datetime' => $reminder->reminder_datetime->format('d/m/Y H:i'),
                    'type' => $reminder->type,
                    'sent' => $reminder->sent,
                    'task_id' => $reminder->task_id,
                ],
                'message' => "Detalles del recordatorio",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener recordatorio: ' . $e->getMessage(),
            ];
        }
    }

    public function updateReminder(int $userId, array $parameters): array
    {
        try {
            $reminder = Reminder::where('user_id', $userId)
                ->where('id', $parameters['reminder_id'])
                ->first();

            if (!$reminder) {
                return [
                    'success' => false,
                    'message' => "No encontre el recordatorio con ID {$parameters['reminder_id']}",
                ];
            }

            $updateData = [];
            $changes = [];

            if (isset($parameters['message'])) {
                $updateData['message'] = $parameters['message'];
                $changes[] = "mensaje actualizado";
            }

            if (isset($parameters['reminder_datetime'])) {
                $updateData['reminder_datetime'] = $this->parseDateTime($parameters['reminder_datetime']);
                $changes[] = "fecha a " . $updateData['reminder_datetime']->format('d/m/Y H:i');
            }

            if (isset($parameters['type'])) {
                $updateData['type'] = $parameters['type'];
                $changes[] = "tipo a '{$parameters['type']}'";
            }

            if (empty($updateData)) {
                return [
                    'success' => false,
                    'message' => 'No se especificaron campos para actualizar',
                ];
            }

            $reminder->update($updateData);

            return [
                'success' => true,
                'message' => "Recordatorio actualizado: " . implode(', ', $changes),
                'reminder_id' => $reminder->id,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar recordatorio: ' . $e->getMessage(),
            ];
        }
    }

    public function deleteReminder(int $userId, array $parameters): array
    {
        try {
            $reminder = Reminder::where('user_id', $userId)
                ->where('id', $parameters['reminder_id'])
                ->first();

            if (!$reminder) {
                return [
                    'success' => false,
                    'message' => "No encontre el recordatorio con ID {$parameters['reminder_id']}",
                ];
            }

            $reminder->delete();

            return [
                'success' => true,
                'message' => "Recordatorio eliminado correctamente",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al eliminar recordatorio: ' . $e->getMessage(),
            ];
        }
    }

    // ==================== UTILIDADES ====================

    private function parseDateTime(?string $dateTimeString): Carbon
    {
        if (!$dateTimeString) {
            return now()->addDay();
        }

        try {
            return Carbon::parse($dateTimeString);
        } catch (\Exception $e) {
            return now()->addDay()->setTime(9, 0);
        }
    }

    // ==================== DEFINICIONES DE FUNCIONES ====================

    public function getFunctionDefinitions(): array
    {
        $currentDate = Carbon::now();
        $todayFormatted = $currentDate->format('Y-m-d');
        $tomorrowFormatted = $currentDate->copy()->addDay()->format('Y-m-d');

        return [
            // === TAREAS ===
            [
                'name' => 'create_task_with_reminder',
                'description' => 'Crea una nueva tarea con recordatorio opcional. Usa cuando el usuario quiera crear, agregar o registrar una tarea, actividad, examen o trabajo.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                            'description' => 'Titulo de la tarea',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Descripcion detallada',
                        ],
                        'due_date' => [
                            'type' => 'string',
                            'description' => "Fecha y hora en formato YYYY-MM-DD HH:mm:ss. Fecha actual: {$todayFormatted}. 'hoy' = {$todayFormatted}, 'manana' = {$tomorrowFormatted}",
                        ],
                        'priority' => [
                            'type' => 'string',
                            'enum' => ['low', 'medium', 'high'],
                            'description' => 'Prioridad: low, medium, high',
                        ],
                        'type' => [
                            'type' => 'string',
                            'enum' => ['task', 'exam', 'work', 'class', 'other'],
                            'description' => 'Tipo de tarea',
                        ],
                        'subject' => [
                            'type' => 'string',
                            'description' => 'Materia o curso',
                        ],
                        'remind_minutes_before' => [
                            'type' => 'integer',
                            'description' => 'Minutos antes para el recordatorio (default: 120)',
                        ],
                    ],
                    'required' => ['title', 'due_date'],
                ],
            ],
            [
                'name' => 'list_tasks',
                'description' => 'Lista las tareas del usuario. IMPORTANTE: Usa esta funcion PRIMERO cuando el usuario quiera eliminar o modificar una tarea sin dar un ID especifico.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => [
                            'type' => 'string',
                            'description' => 'Buscar tareas por nombre/titulo (ej: "reunion", "examen")',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['pending', 'in_progress', 'completed', 'overdue'],
                            'description' => 'Filtrar por estado',
                        ],
                        'priority' => [
                            'type' => 'string',
                            'enum' => ['low', 'medium', 'high'],
                            'description' => 'Filtrar por prioridad',
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Limite de resultados',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_task',
                'description' => 'Obtiene detalles de una tarea especifica por su ID.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'task_id' => [
                            'type' => 'integer',
                            'description' => 'ID de la tarea',
                        ],
                    ],
                    'required' => ['task_id'],
                ],
            ],
            [
                'name' => 'update_task',
                'description' => 'Actualiza una tarea existente. Usa cuando el usuario quiera modificar, cambiar o editar una tarea (titulo, fecha, prioridad, estado, etc).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'task_id' => [
                            'type' => 'integer',
                            'description' => 'ID de la tarea a actualizar',
                        ],
                        'title' => [
                            'type' => 'string',
                            'description' => 'Nuevo titulo',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Nueva descripcion',
                        ],
                        'due_date' => [
                            'type' => 'string',
                            'description' => "Nueva fecha en formato YYYY-MM-DD HH:mm:ss. Fecha actual: {$todayFormatted}",
                        ],
                        'priority' => [
                            'type' => 'string',
                            'enum' => ['low', 'medium', 'high'],
                            'description' => 'Nueva prioridad',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => ['pending', 'in_progress', 'completed', 'overdue'],
                            'description' => 'Nuevo estado',
                        ],
                        'subject' => [
                            'type' => 'string',
                            'description' => 'Nueva materia',
                        ],
                        'progress_percentage' => [
                            'type' => 'integer',
                            'description' => 'Porcentaje de progreso (0-100)',
                        ],
                    ],
                    'required' => ['task_id'],
                ],
            ],
            [
                'name' => 'delete_task',
                'description' => 'Elimina una tarea. Usa cuando el usuario quiera borrar, eliminar o quitar una tarea.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'task_id' => [
                            'type' => 'integer',
                            'description' => 'ID de la tarea a eliminar',
                        ],
                    ],
                    'required' => ['task_id'],
                ],
            ],

            // === HABITOS ===
            [
                'name' => 'create_habit',
                'description' => 'Crea un nuevo habito. Usa cuando el usuario quiera crear, agregar o establecer un habito o rutina.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'description' => 'Nombre del habito',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Descripcion del habito',
                        ],
                        'frequency' => [
                            'type' => 'string',
                            'enum' => ['daily', 'weekly', 'monthly'],
                            'description' => 'Frecuencia: daily, weekly, monthly',
                        ],
                        'preferred_time' => [
                            'type' => 'string',
                            'description' => 'Hora preferida en formato HH:mm:ss',
                        ],
                    ],
                    'required' => ['name'],
                ],
            ],
            [
                'name' => 'list_habits',
                'description' => 'Lista los habitos del usuario. IMPORTANTE: Usa esta funcion PRIMERO cuando el usuario quiera eliminar o modificar un habito sin dar un ID especifico.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => [
                            'type' => 'string',
                            'description' => 'Buscar habitos por nombre (ej: "ejercicio", "estudiar")',
                        ],
                        'active' => [
                            'type' => 'boolean',
                            'description' => 'Filtrar por activos (true) o inactivos (false)',
                        ],
                        'frequency' => [
                            'type' => 'string',
                            'enum' => ['daily', 'weekly', 'monthly'],
                            'description' => 'Filtrar por frecuencia',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_habit',
                'description' => 'Obtiene detalles de un habito especifico por su ID.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'habit_id' => [
                            'type' => 'integer',
                            'description' => 'ID del habito',
                        ],
                    ],
                    'required' => ['habit_id'],
                ],
            ],
            [
                'name' => 'update_habit',
                'description' => 'Actualiza un habito existente. Usa cuando el usuario quiera modificar, cambiar o editar un habito.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'habit_id' => [
                            'type' => 'integer',
                            'description' => 'ID del habito a actualizar',
                        ],
                        'name' => [
                            'type' => 'string',
                            'description' => 'Nuevo nombre',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Nueva descripcion',
                        ],
                        'frequency' => [
                            'type' => 'string',
                            'enum' => ['daily', 'weekly', 'monthly'],
                            'description' => 'Nueva frecuencia',
                        ],
                        'preferred_time' => [
                            'type' => 'string',
                            'description' => 'Nueva hora preferida',
                        ],
                        'active' => [
                            'type' => 'boolean',
                            'description' => 'Activar (true) o desactivar (false)',
                        ],
                    ],
                    'required' => ['habit_id'],
                ],
            ],
            [
                'name' => 'delete_habit',
                'description' => 'Elimina un habito. Usa cuando el usuario quiera borrar, eliminar o quitar un habito.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'habit_id' => [
                            'type' => 'integer',
                            'description' => 'ID del habito a eliminar',
                        ],
                    ],
                    'required' => ['habit_id'],
                ],
            ],
            [
                'name' => 'complete_habit',
                'description' => 'Marca un habito como completado hoy. Usa cuando el usuario diga que completo o cumplio su habito.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'habit_id' => [
                            'type' => 'integer',
                            'description' => 'ID del habito completado',
                        ],
                    ],
                    'required' => ['habit_id'],
                ],
            ],

            // === RECORDATORIOS ===
            [
                'name' => 'create_reminder',
                'description' => 'Crea un recordatorio simple. Usa cuando el usuario quiera que le recuerdes algo.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'message' => [
                            'type' => 'string',
                            'description' => 'Mensaje del recordatorio',
                        ],
                        'reminder_datetime' => [
                            'type' => 'string',
                            'description' => "Fecha y hora en formato YYYY-MM-DD HH:mm:ss. Fecha actual: {$todayFormatted}",
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
                'name' => 'list_reminders',
                'description' => 'Lista los recordatorios del usuario. IMPORTANTE: Usa esta funcion PRIMERO cuando el usuario quiera eliminar o modificar un recordatorio sin dar un ID especifico.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'search' => [
                            'type' => 'string',
                            'description' => 'Buscar recordatorios por mensaje (ej: "llamar", "comprar")',
                        ],
                        'pending' => [
                            'type' => 'boolean',
                            'description' => 'Solo mostrar recordatorios pendientes (no enviados)',
                        ],
                        'sent' => [
                            'type' => 'boolean',
                            'description' => 'Filtrar por enviados (true) o no enviados (false)',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_reminder',
                'description' => 'Obtiene detalles de un recordatorio especifico por su ID.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'reminder_id' => [
                            'type' => 'integer',
                            'description' => 'ID del recordatorio',
                        ],
                    ],
                    'required' => ['reminder_id'],
                ],
            ],
            [
                'name' => 'update_reminder',
                'description' => 'Actualiza un recordatorio existente. Usa cuando el usuario quiera modificar o cambiar un recordatorio.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'reminder_id' => [
                            'type' => 'integer',
                            'description' => 'ID del recordatorio a actualizar',
                        ],
                        'message' => [
                            'type' => 'string',
                            'description' => 'Nuevo mensaje',
                        ],
                        'reminder_datetime' => [
                            'type' => 'string',
                            'description' => "Nueva fecha en formato YYYY-MM-DD HH:mm:ss. Fecha actual: {$todayFormatted}",
                        ],
                        'type' => [
                            'type' => 'string',
                            'enum' => ['voice', 'notification', 'both'],
                            'description' => 'Nuevo tipo',
                        ],
                    ],
                    'required' => ['reminder_id'],
                ],
            ],
            [
                'name' => 'delete_reminder',
                'description' => 'Elimina un recordatorio. Usa cuando el usuario quiera borrar o eliminar un recordatorio.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'reminder_id' => [
                            'type' => 'integer',
                            'description' => 'ID del recordatorio a eliminar',
                        ],
                    ],
                    'required' => ['reminder_id'],
                ],
            ],
        ];
    }

    // ==================== EJECUTOR DE FUNCIONES ====================

    public function executeFunction(int $userId, string $functionName, array $arguments): array
    {
        return match ($functionName) {
            // Tareas
            'create_task_with_reminder' => $this->createTaskWithReminder($userId, $arguments),
            'list_tasks' => $this->listTasks($userId, $arguments),
            'get_task' => $this->getTask($userId, $arguments),
            'update_task' => $this->updateTask($userId, $arguments),
            'delete_task' => $this->deleteTask($userId, $arguments),

            // Habitos
            'create_habit' => $this->createHabit($userId, $arguments),
            'list_habits' => $this->listHabits($userId, $arguments),
            'get_habit' => $this->getHabit($userId, $arguments),
            'update_habit' => $this->updateHabit($userId, $arguments),
            'delete_habit' => $this->deleteHabit($userId, $arguments),
            'complete_habit' => $this->completeHabit($userId, $arguments),

            // Recordatorios
            'create_reminder' => $this->createReminder($userId, $arguments),
            'list_reminders' => $this->listReminders($userId, $arguments),
            'get_reminder' => $this->getReminder($userId, $arguments),
            'update_reminder' => $this->updateReminder($userId, $arguments),
            'delete_reminder' => $this->deleteReminder($userId, $arguments),

            default => [
                'success' => false,
                'message' => "Funcion '{$functionName}' no reconocida",
            ],
        };
    }
}
