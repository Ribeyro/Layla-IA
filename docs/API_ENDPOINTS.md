perfecto ahor vams aintegratos e# API Endpoints - Layla IA

Base URL: `http://tu-dominio.com/api`

## Autenticacion

Todas las rutas protegidas requieren el header:
```
Authorization: Bearer {token}
```

---

## AUTENTICACION

### Registro
```
POST /register
```
**Body:**
```json
{
    "name": "Pedro",
    "email": "pedro@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```
**Respuesta (201):**
```json
{
    "user": {
        "id": 1,
        "name": "Pedro",
        "email": "pedro@example.com",
        "avatar": {...}
    },
    "token": "1|abc123..."
}
```

### Login
```
POST /login
```
**Body:**
```json
{
    "email": "pedro@example.com",
    "password": "password123"
}
```
**Respuesta:**
```json
{
    "user": {...},
    "token": "2|xyz789..."
}
```

### Logout
```
POST /logout
Authorization: Bearer {token}
```
**Respuesta:**
```json
{
    "message": "Logged out successfully."
}
```

### Obtener Usuario Actual
```
GET /user
Authorization: Bearer {token}
```
**Respuesta:**
```json
{
    "id": 1,
    "name": "Pedro",
    "email": "pedro@example.com",
    "avatar": {...}
}
```

---

## CHAT CON IA (LAYLA)

### Enviar Mensaje
```
POST /chat
Authorization: Bearer {token}
```
**Body:**
```json
{
    "message": "Hola, que tareas tengo pendientes?",
    "conversation_id": 1,
    "by_voice": false
}
```
**Respuesta:**
```json
{
    "success": true,
    "response": "Hola Pedro! Tienes 3 tareas pendientes...",
    "conversation_id": 1,
    "function_executed": {
        "name": "list_tasks",
        "result": {...}
    }
}
```

### Ver Contexto de IA
```
GET /chat/context
Authorization: Bearer {token}
```

---

## TAREAS

### Listar Tareas
```
GET /tasks
GET /tasks?status=pending
GET /tasks?priority=high
GET /tasks?type=exam
Authorization: Bearer {token}
```
**Respuesta:**
```json
{
    "success": true,
    "tasks": [
        {
            "id": 1,
            "title": "Examen de Matematicas",
            "description": "Estudiar capitulos 1-5",
            "type": "exam",
            "due_date": "2026-01-30T10:00:00",
            "priority": "high",
            "status": "pending",
            "progress_percentage": 0,
            "subject": "Matematicas"
        }
    ]
}
```

### Crear Tarea
```
POST /tasks
Authorization: Bearer {token}
```
**Body:**
```json
{
    "title": "Examen de Matematicas",
    "description": "Estudiar capitulos 1-5",
    "type": "exam",
    "due_date": "2026-01-30 10:00:00",
    "priority": "high",
    "subject": "Matematicas"
}
```
**Tipos:** `task`, `exam`, `work`, `class`, `other`
**Prioridades:** `low`, `medium`, `high`

### Ver Tarea
```
GET /tasks/{id}
Authorization: Bearer {token}
```

### Actualizar Tarea
```
PUT /tasks/{id}
Authorization: Bearer {token}
```
**Body (todos opcionales):**
```json
{
    "title": "Nuevo titulo",
    "status": "in_progress",
    "priority": "medium",
    "progress_percentage": 50
}
```
**Estados:** `pending`, `in_progress`, `completed`, `overdue`

### Eliminar Tarea
```
DELETE /tasks/{id}
Authorization: Bearer {token}
```

### Actualizar Solo Estado
```
POST /tasks/{id}/status
Authorization: Bearer {token}
```
**Body:**
```json
{
    "status": "completed"
}
```

### Resumen de Tareas
```
GET /tasks-summary
Authorization: Bearer {token}
```
**Respuesta:**
```json
{
    "success": true,
    "summary": {
        "pending": 5,
        "in_progress": 2,
        "completed": 10,
        "overdue": 1,
        "total": 18
    }
}
```

### Tareas Proximas
```
GET /tasks-upcoming
Authorization: Bearer {token}
```

### Tareas Vencidas
```
GET /tasks-overdue
Authorization: Bearer {token}
```

---

## HABITOS

### Listar Habitos
```
GET /habits
GET /habits?active=true
GET /habits?frequency=daily
Authorization: Bearer {token}
```
**Respuesta:**
```json
{
    "success": true,
    "habits": [
        {
            "id": 1,
            "name": "Estudiar",
            "description": "Estudiar 2 horas diarias",
            "frequency": "daily",
            "preferred_time": "07:00:00",
            "active": true,
            "current_streak": 5,
            "max_streak": 10
        }
    ]
}
```

### Crear Habito
```
POST /habits
Authorization: Bearer {token}
```
**Body:**
```json
{
    "name": "Estudiar",
    "description": "Estudiar 2 horas diarias",
    "frequency": "daily",
    "preferred_time": "07:00:00"
}
```
**Frecuencias:** `daily`, `weekly`, `monthly`

### Ver Habito
```
GET /habits/{id}
Authorization: Bearer {token}
```

### Actualizar Habito
```
PUT /habits/{id}
Authorization: Bearer {token}
```
**Body:**
```json
{
    "name": "Estudiar mas",
    "frequency": "daily",
    "active": true
}
```

### Eliminar Habito
```
DELETE /habits/{id}
Authorization: Bearer {token}
```

### Completar Habito (Aumentar Racha)
```
POST /habits/{id}/complete
Authorization: Bearer {token}
```

### Romper Racha
```
POST /habits/{id}/break-streak
Authorization: Bearer {token}
```

### Activar/Desactivar Habito
```
POST /habits/{id}/toggle-active
Authorization: Bearer {token}
```

---

## RECORDATORIOS

### Listar Recordatorios
```
GET /reminders
GET /reminders?sent=false
GET /reminders?type=notification
Authorization: Bearer {token}
```

### Crear Recordatorio
```
POST /reminders
Authorization: Bearer {token}
```
**Body:**
```json
{
    "message": "Llamar a mama",
    "reminder_datetime": "2026-01-26 15:00:00",
    "type": "notification",
    "task_id": null
}
```
**Tipos:** `voice`, `notification`, `both`

### Ver Recordatorio
```
GET /reminders/{id}
Authorization: Bearer {token}
```

### Actualizar Recordatorio
```
PUT /reminders/{id}
Authorization: Bearer {token}
```

### Eliminar Recordatorio
```
DELETE /reminders/{id}
Authorization: Bearer {token}
```

### Marcar como Enviado
```
POST /reminders/{id}/sent
Authorization: Bearer {token}
```

### Recordatorios Pendientes
```
GET /reminders-pending
Authorization: Bearer {token}
```

### Recordatorios Proximos (24h)
```
GET /reminders-upcoming
Authorization: Bearer {token}
```

---

## AVATAR

### Ver Avatar
```
GET /avatar
Authorization: Bearer {token}
```
**Respuesta:**
```json
{
    "success": true,
    "avatar": {
        "id": 1,
        "user_id": 1,
        "emotional_state": "happy",
        "happiness_level": 75,
        "streak_days": 5,
        "motivational_message": "Vas muy bien!"
    }
}
```

### Actualizar Avatar
```
PUT /avatar
Authorization: Bearer {token}
```
**Body:**
```json
{
    "motivational_message": "Sigue adelante!",
    "streak_days": 10
}
```

### Actualizar Racha
```
POST /avatar/streak
Authorization: Bearer {token}
```
**Body:**
```json
{
    "action": "increment"
}
```
**Acciones:** `increment`, `reset`

---

## CONVERSACIONES

### Listar Conversaciones
```
GET /conversations
GET /conversations?active=true
GET /conversations?type=academic
Authorization: Bearer {token}
```

### Crear Conversacion
```
POST /conversations
Authorization: Bearer {token}
```
**Body:**
```json
{
    "type": "general"
}
```
**Tipos:** `academic`, `emotional`, `general`

### Ver Conversacion con Mensajes
```
GET /conversations/{id}
Authorization: Bearer {token}
```

### Finalizar Conversacion
```
POST /conversations/{id}/end
Authorization: Bearer {token}
```

### Agregar Mensaje a Conversacion
```
POST /conversations/{id}/messages
Authorization: Bearer {token}
```
**Body:**
```json
{
    "role": "user",
    "content": "Hola!",
    "sentiment": "positive",
    "by_voice": false
}
```

### Ver Mensajes de Conversacion
```
GET /conversations/{id}/messages
Authorization: Bearer {token}
```

### Conversacion Activa
```
GET /conversations-active
Authorization: Bearer {token}
```

### Conversaciones Recientes
```
GET /conversations-recent
Authorization: Bearer {token}
```

---

## RECOMENDACIONES

### Listar Recomendaciones
```
GET /recommendations
Authorization: Bearer {token}
```

### Crear Recomendacion
```
POST /recommendations
Authorization: Bearer {token}
```

### Ver Recomendacion
```
GET /recommendations/{id}
Authorization: Bearer {token}
```

### Recomendaciones No Vistas
```
GET /recommendations-unviewed
Authorization: Bearer {token}
```

### Marcar como Vista
```
POST /recommendations/{id}/viewed
Authorization: Bearer {token}
```

### Marcar como Aplicada
```
POST /recommendations/{id}/applied
Authorization: Bearer {token}
```

---

## ESTADISTICAS DIARIAS

### Listar Estadisticas
```
GET /daily-statistics
Authorization: Bearer {token}
```

### Estadisticas de Hoy
```
GET /daily-statistics-today
Authorization: Bearer {token}
```

### Estadisticas Semanales
```
GET /daily-statistics-weekly
Authorization: Bearer {token}
```

---

## SESIONES DE VOZ

### Listar Sesiones
```
GET /voice-sessions
Authorization: Bearer {token}
```

### Crear Sesion
```
POST /voice-sessions
Authorization: Bearer {token}
```

### Sesiones Recientes
```
GET /voice-sessions-recent
Authorization: Bearer {token}
```

### Finalizar Sesion
```
POST /voice-sessions/{id}/end
Authorization: Bearer {token}
```

### Incrementar Comandos
```
POST /voice-sessions/{id}/increment-commands
Authorization: Bearer {token}
```

---

## Codigos de Respuesta

| Codigo | Descripcion |
|--------|-------------|
| 200 | OK |
| 201 | Creado |
| 400 | Bad Request |
| 401 | No autorizado |
| 404 | No encontrado |
| 422 | Error de validacion |
| 500 | Error del servidor |

## Ejemplo de Error
```json
{
    "success": false,
    "error": "Mensaje de error"
}
```

## Ejemplo de Error de Validacion
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."]
    }
}
```
