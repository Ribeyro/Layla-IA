<?php

use App\Http\Api\Controllers\LoginController;
use App\Http\Api\Controllers\RegisterController;
use App\Http\Api\Controllers\ChatController;
use App\Http\Api\Controllers\TaskController;
use App\Http\Api\Controllers\AvatarController;
use App\Http\Api\Controllers\ReminderController;
use App\Http\Api\Controllers\HabitController;
use App\Http\Api\Controllers\RecommendationController;
use App\Http\Api\Controllers\DailyStatisticController;
use App\Http\Api\Controllers\VoiceSessionController;
use App\Http\Api\Controllers\ConversationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    // Usuario
    Route::get('/user', function (Request $request) {
        return $request->user()->load('avatar');
    });
    Route::post('/logout', [LoginController::class, 'logout']);

    // Chat con IA (Layla)
    Route::post('/chat', [ChatController::class, 'chat']);
    Route::get('/chat/context', [ChatController::class, 'getContext']); // Ver contexto que recibe la IA

    // Avatar
    Route::get('/avatar', [AvatarController::class, 'show']);
    Route::put('/avatar', [AvatarController::class, 'update']);
    Route::post('/avatar/streak', [AvatarController::class, 'updateStreak']);

    // Tareas
    Route::apiResource('tasks', TaskController::class);
    Route::get('/tasks-summary', [TaskController::class, 'summary']);
    Route::get('/tasks-upcoming', [TaskController::class, 'upcoming']);
    Route::get('/tasks-overdue', [TaskController::class, 'overdue']);
    Route::post('/tasks/{id}/status', [TaskController::class, 'updateStatus']);

    // Recordatorios
    Route::apiResource('reminders', ReminderController::class);
    Route::get('/reminders-pending', [ReminderController::class, 'pending']);
    Route::get('/reminders-upcoming', [ReminderController::class, 'upcoming']);
    Route::post('/reminders/{id}/sent', [ReminderController::class, 'markAsSent']);

    // Hábitos
    Route::apiResource('habits', HabitController::class);
    Route::post('/habits/{id}/complete', [HabitController::class, 'completeHabit']);
    Route::post('/habits/{id}/break-streak', [HabitController::class, 'breakStreak']);
    Route::post('/habits/{id}/toggle-active', [HabitController::class, 'toggleActive']);

    // Recomendaciones
    Route::apiResource('recommendations', RecommendationController::class);
    Route::get('/recommendations-unviewed', [RecommendationController::class, 'unviewed']);
    Route::post('/recommendations/{id}/viewed', [RecommendationController::class, 'markAsViewed']);
    Route::post('/recommendations/{id}/applied', [RecommendationController::class, 'markAsApplied']);

    // Estadísticas Diarias
    Route::apiResource('daily-statistics', DailyStatisticController::class);
    Route::get('/daily-statistics-today', [DailyStatisticController::class, 'today']);
    Route::get('/daily-statistics-weekly', [DailyStatisticController::class, 'weekly']);

    // Sesiones de Voz
    Route::apiResource('voice-sessions', VoiceSessionController::class);
    Route::get('/voice-sessions-recent', [VoiceSessionController::class, 'recent']);
    Route::post('/voice-sessions/{id}/end', [VoiceSessionController::class, 'endSession']);
    Route::post('/voice-sessions/{id}/increment-commands', [VoiceSessionController::class, 'incrementCommands']);

    // Conversaciones
    Route::apiResource('conversations', ConversationController::class);
    Route::get('/conversations-active', [ConversationController::class, 'activeConversation']);
    Route::get('/conversations-recent', [ConversationController::class, 'recentConversations']);
    Route::post('/conversations/{id}/end', [ConversationController::class, 'endConversation']);
    Route::post('/conversations/{id}/messages', [ConversationController::class, 'addMessage']);
    Route::get('/conversations/{id}/messages', [ConversationController::class, 'getMessages']);
});
