# Documentacion de Funciones del Chat IA - Layla

Esta documentacion describe todas las funciones disponibles en el chat de IA para gestionar tareas, habitos y recordatorios.

## Endpoint

```
POST /api/chat
```

**Headers:**
```json
{
    "Authorization": "Bearer {token}",
    "Content-Type": "application/json"
}
```

**Body:**
```json
{
    "message": "tu mensaje aqui",
    "conversation_id": 1  // opcional
}
```

---

## TAREAS (Tasks)

### Crear Tarea

**Ejemplos de mensajes:**
```json
{"message": "Tengo un examen de matematicas manana a las 10 AM"}
{"message": "Registra una reunion hoy a las 8 PM y recuerdame 5 minutos antes"}
{"message": "Crea una tarea: entregar proyecto de fisica el viernes a las 11:59 PM"}
{"message": "Tengo que estudiar para el parcial de historia el lunes"}
{"message": "Agrega tarea de alta prioridad: presentacion de ingles manana a las 2 PM"}
```

**Respuesta esperada:**
```json
{
    "success": true,
    "response": "He registrado tu examen de matematicas para manana 26/01/2026 a las 10:00 AM con recordatorio 2 horas antes.",
    "conversation_id": 1,
    "function_executed": {
        "name": "create_task_with_reminder",
        "result": {
            "success": true,
            "message": "Tarea 'Examen de matematicas' creada para el 26/01/2026 10:00 con recordatorio 2 horas antes",
            "task_id": 5,
            "reminder_id": 3
        }
    }
}
```

---

### Listar Tareas

**Ejemplos de mensajes:**
```json
{"message": "Que tareas tengo"}
{"message": "Muestrame mis tareas pendientes"}
{"message": "Ver mis tareas completadas"}
{"message": "Cuales son mis tareas de alta prioridad"}
{"message": "Lista mis proximas actividades"}
```

**Respuesta esperada:**
```json
{
    "success": true,
    "response": "Tienes 3 tareas pendientes: 1) Examen de matematicas - 26/01/2026 10:00, 2) Proyecto de fisica - 31/01/2026 23:59, 3) Presentacion de ingles - 27/01/2026 14:00",
    "conversation_id": 1,
    "function_executed": {
        "name": "list_tasks",
        "result": {
            "success": true,
            "tasks": [
                {"id": 5, "title": "Examen de matematicas", "due_date": "26/01/2026 10:00", "priority": "medium", "status": "pending"},
                {"id": 6, "title": "Proyecto de fisica", "due_date": "31/01/2026 23:59", "priority": "medium", "status": "pending"}
            ],
            "total": 2,
            "message": "Encontre 2 tarea(s)"
        }
    }
}
```

---

### Ver Detalles de Tarea

**Ejemplos de mensajes:**
```json
{"message": "Dame detalles de la tarea 5"}
{"message": "Informacion de mi tarea con ID 3"}
{"message": "Que hay en la tarea numero 7"}
```

**Respuesta esperada:**
```json
{
    "success": true,
    "response": "La tarea 5 es 'Examen de matematicas', programada para el 26/01/2026 a las 10:00, con prioridad media y estado pendiente.",
    "function_executed": {
        "name": "get_task",
        "result": {
            "success": true,
            "task": {
                "id": 5,
                "title": "Examen de matematicas",
                "description": null,
                "due_date": "26/01/2026 10:00",
                "priority": "medium",
                "status": "pending",
                "type": "exam",
                "subject": "Matematicas",
                "progress_percentage": 0
            }
        }
    }
}
```

---

### Actualizar Tarea

**Ejemplos de mensajes:**
```json
{"message": "Cambia la fecha de la tarea 5 para el 28 de enero a las 9 AM"}
{"message": "Marca la tarea 3 como completada"}
{"message": "Actualiza el titulo de la tarea 7 a 'Examen final de calculo'"}
{"message": "Cambia la prioridad de la tarea 2 a alta"}
{"message": "Pon el progreso de la tarea 4 en 50%"}
{"message": "La tarea 6 ya esta en progreso"}
```

**Respuesta esperada:**
```json
{
    "success": true,
    "response": "He actualizado la tarea 'Examen de matematicas': fecha cambiada a 28/01/2026 09:00",
    "function_executed": {
        "name": "update_task",
        "result": {
            "success": true,
            "message": "Tarea 'Examen de matematicas' actualizada: fecha a 28/01/2026 09:00",
            "task_id": 5
        }
    }
}
```

---

### Eliminar Tarea

**Ejemplos de mensajes:**
```json
{"message": "Elimina la tarea 5"}
{"message": "Borra mi tarea de matematicas"}
{"message": "Quita la tarea con ID 3"}
{"message": "Cancela la tarea del examen"}
```

**Respuesta esperada:**
```json
{
    "success": true,
    "response": "He eliminado la tarea 'Examen de matematicas' junto con sus recordatorios asociados.",
    "function_executed": {
        "name": "delete_task",
        "result": {
            "success": true,
            "message": "Tarea 'Examen de matematicas' eliminada correctamente junto con sus recordatorios"
        }
    }
}
```

---

## HABITOS (Habits)

### Crear Habito

**Ejemplos de mensajes:**
```json
{"message": "Quiero crear el habito de estudiar todos los dias a las 7 AM"}
{"message": "Crea un habito de ejercicio diario"}
{"message": "Nuevo habito: leer 30 minutos antes de dormir"}
{"message": "Quiero establecer la rutina de meditar cada manana"}
{"message": "Crea habito semanal de revisar mis notas los domingos"}
```

**Respuesta esperada:**
```json
{
    "success": true,
    "response": "He creado tu habito de estudiar todos los dias a las 7:00 AM. Recuerda que la constancia es clave para formar habitos.",
    "function_executed": {
        "name": "create_habit",
        "result": {
            "success": true,
            "message": "Habito 'Estudiar' creado con frecuencia daily",
            "habit_id": 2
        }
    }
}
```

---

### Listar Habitos

**Ejemplos de mensajes:**
```json
{"message": "Que habitos tengo"}
{"message": "Muestrame mis rutinas"}
{"message": "Ver mis habitos activos"}
{"message": "Cuales son mis habitos diarios"}
```

**Respuesta esperada:**
```json
{
    "success": true,
    "response": "Tienes 2 habitos activos: 1) Estudiar (diario, 7:00 AM, racha: 5 dias), 2) Ejercicio (diario, 6:00 AM, racha: 3 dias)",
    "function_executed": {
        "name": "list_habits",
        "result": {
            "success": true,
            "habits": [
                {"id": 1, "name": "Estudiar", "frequency": "daily", "preferred_time": "07:00:00", "active": true, "current_streak": 5},
                {"id": 2, "name": "Ejercicio", "frequency": "daily", "preferred_time": "06:00:00", "active": true, "current_streak": 3}
            ],
            "total": 2
        }
    }
}
```

---

### Ver Detalles de Habito

**Ejemplos de mensajes:**
```json
{"message": "Dame detalles del habito 1"}
{"message": "Informacion de mi habito de estudiar"}
```

---

### Actualizar Habito

**Ejemplos de mensajes:**
```json
{"message": "Cambia la hora del habito 1 a las 8 AM"}
{"message": "Desactiva el habito 2"}
{"message": "Cambia el nombre del habito 3 a 'Lectura nocturna'"}
{"message": "Pon el habito de ejercicio como semanal"}
{"message": "Activa el habito 4"}
```

**Respuesta esperada:**
```json
{
    "success": true,
    "response": "He actualizado tu habito de estudiar: hora preferida cambiada a 08:00",
    "function_executed": {
        "name": "update_habit",
        "result": {
            "success": true,
            "message": "Habito 'Estudiar' actualizado: hora preferida a '08:00:00'",
            "habit_id": 1
        }
    }
}
```

---

### Eliminar Habito

**Ejemplos de mensajes:**
```json
{"message": "Elimina el habito 2"}
{"message": "Borra mi habito de ejercicio"}
{"message": "Quita el habito numero 3"}
```

**Respuesta esperada:**
```json
{
    "success": true,
    "response": "He eliminado tu habito de ejercicio.",
    "function_executed": {
        "name": "delete_habit",
        "result": {
            "success": true,
            "message": "Habito 'Ejercicio' eliminado correctamente"
        }
    }
}
```

---

### Completar Habito

**Ejemplos de mensajes:**
```json
{"message": "Ya complete mi habito de estudiar"}
{"message": "Hoy hice mi rutina de ejercicio"}
{"message": "Marca el habito 1 como completado"}
{"message": "Cumpli mi habito de lectura"}
```

**Respuesta esperada:**
```json
{
    "success": true,
    "response": "Excelente! Has completado tu habito de estudiar. Tu racha actual es de 6 dias consecutivos. Sigue asi!",
    "function_executed": {
        "name": "complete_habit",
        "result": {
            "success": true,
            "message": "Habito 'Estudiar' completado. Racha actual: 6 dias",
            "habit_id": 1,
            "current_streak": 6
        }
    }
}
```

---

## RECORDATORIOS (Reminders)

### Crear Recordatorio

**Ejemplos de mensajes:**
```json
{"message": "Recuerdame llamar a mama a las 3 PM"}
{"message": "Ponme un recordatorio para comprar leche manana a las 10 AM"}
{"message": "Avisame en 30 minutos que tengo que salir"}
{"message": "Crea un recordatorio: reunion de trabajo a las 5 PM"}
```

**Respuesta esperada:**
```json
{
    "success": true,
    "response": "Te recordare llamar a mama hoy a las 3:00 PM.",
    "function_executed": {
        "name": "create_reminder",
        "result": {
            "success": true,
            "message": "Recordatorio creado para el 25/01/2026 15:00",
            "reminder_id": 4
        }
    }
}
```

---

### Listar Recordatorios

**Ejemplos de mensajes:**
```json
{"message": "Que recordatorios tengo"}
{"message": "Muestrame mis alarmas pendientes"}
{"message": "Ver mis recordatorios"}
{"message": "Cuales son mis proximos avisos"}
```

**Respuesta esperada:**
```json
{
    "success": true,
    "response": "Tienes 2 recordatorios pendientes: 1) Llamar a mama - 25/01/2026 15:00, 2) Comprar leche - 26/01/2026 10:00",
    "function_executed": {
        "name": "list_reminders",
        "result": {
            "success": true,
            "reminders": [
                {"id": 4, "message": "Llamar a mama", "reminder_datetime": "25/01/2026 15:00", "sent": false},
                {"id": 5, "message": "Comprar leche", "reminder_datetime": "26/01/2026 10:00", "sent": false}
            ],
            "total": 2
        }
    }
}
```

---

### Ver Detalles de Recordatorio

**Ejemplos de mensajes:**
```json
{"message": "Dame detalles del recordatorio 4"}
{"message": "Informacion de mi alarma numero 3"}
```

---

### Actualizar Recordatorio

**Ejemplos de mensajes:**
```json
{"message": "Cambia la hora del recordatorio 4 a las 4 PM"}
{"message": "Actualiza el mensaje del recordatorio 3 a 'Reunion importante'"}
{"message": "Cambia la fecha del recordatorio 5 para manana"}
```

**Respuesta esperada:**
```json
{
    "success": true,
    "response": "He actualizado tu recordatorio: hora cambiada a 16:00",
    "function_executed": {
        "name": "update_reminder",
        "result": {
            "success": true,
            "message": "Recordatorio actualizado: fecha a 25/01/2026 16:00",
            "reminder_id": 4
        }
    }
}
```

---

### Eliminar Recordatorio

**Ejemplos de mensajes:**
```json
{"message": "Elimina el recordatorio 4"}
{"message": "Cancela mi alarma de las 3 PM"}
{"message": "Borra el recordatorio numero 5"}
{"message": "Quita el aviso de llamar a mama"}
```

**Respuesta esperada:**
```json
{
    "success": true,
    "response": "He eliminado tu recordatorio.",
    "function_executed": {
        "name": "delete_reminder",
        "result": {
            "success": true,
            "message": "Recordatorio eliminado correctamente"
        }
    }
}
```

---

## Resumen de Funciones

| Modulo | Funcion | Descripcion |
|--------|---------|-------------|
| **Tareas** | create_task_with_reminder | Crear tarea con recordatorio opcional |
| | list_tasks | Listar todas las tareas |
| | get_task | Ver detalles de una tarea |
| | update_task | Actualizar una tarea |
| | delete_task | Eliminar una tarea |
| **Habitos** | create_habit | Crear nuevo habito |
| | list_habits | Listar todos los habitos |
| | get_habit | Ver detalles de un habito |
| | update_habit | Actualizar un habito |
| | delete_habit | Eliminar un habito |
| | complete_habit | Marcar habito como completado |
| **Recordatorios** | create_reminder | Crear recordatorio |
| | list_reminders | Listar todos los recordatorios |
| | get_reminder | Ver detalles de un recordatorio |
| | update_reminder | Actualizar un recordatorio |
| | delete_reminder | Eliminar un recordatorio |

---

## Notas Importantes

1. **IDs**: Cuando necesites modificar o eliminar algo, debes proporcionar el ID. Si no lo conoces, primero usa la funcion de listar para obtenerlo.

2. **Fechas**: Puedes usar lenguaje natural como "hoy", "manana", "el lunes", "a las 3 PM". La IA interpretara la fecha correctamente.

3. **Prioridades**: Las tareas pueden tener prioridad `low`, `medium` o `high`.

4. **Estados de Tareas**: `pending`, `in_progress`, `completed`, `overdue`.

5. **Frecuencia de Habitos**: `daily`, `weekly`, `monthly`.

6. **Tipos de Recordatorio**: `voice`, `notification`, `both`.
