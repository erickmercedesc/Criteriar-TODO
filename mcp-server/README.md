# SecondBrain MCP Server

El **Servidor MCP (Model Context Protocol)** de SecondBrain permite a agentes de Inteligencia Artificial (como **Antigravity**, **Claude Desktop**, **Cursor**, **Windsurf**, **Cline**) interactuar de forma nativa y bidireccional con tu sistema de tareas, criterios de puntuación, proyectos y estadísticas.

---

## Capacidades y Herramientas Expuestas

| Herramienta | Descripción |
| :--- | :--- |
| `secondbrain_get_top_tasks` | Obtiene la siguiente tarea prioritaria recomendada por el motor matemático de puntuación. |
| `secondbrain_list_tasks` | Lista tareas con filtros avanzados (proyecto, estado de completado, rango de puntos, criterios). |
| `secondbrain_get_task` | Obtiene el detalle completo de una tarea (notas/especificaciones técnicas, criterios y proyecto). |
| `secondbrain_create_task` | Crea una nueva tarea asignando especificaciones técnicas (`notes`), proyecto y criterios. |
| `secondbrain_update_task` | Actualiza título, especificaciones técnicas, proyecto o criterios de una tarea. |
| `secondbrain_complete_task` | Marca una tarea como completada (acredita automáticamente los puntos a las estadísticas diarias). |
| `secondbrain_delete_task` | Elimina una tarea del sistema. |
| `secondbrain_list_projects` | Lista los proyectos configurados con sus puntos base (`base_score`) y criterios específicos. |
| `secondbrain_create_project` | Crea un nuevo proyecto con puntuación base y color identificador. |
| `secondbrain_list_scoring_criteria` | Lista criterios de scoring (Globales o específicos de un proyecto). |
| `secondbrain_create_scoring_criterion` | Crea un criterio de puntuación (Global o de Proyecto, con marcador de complejidad anti-burnout). |
| `secondbrain_get_productivity_stats` | Consulta el resumen de hoy y el historial de 30 días (tiempo de focus, tareas completadas, puntos). |

---

## Requisitos Previos

1. Tener la aplicación SecondBrain ejecutándose (ej. `http://localhost:8000` o `http://todo.test`).
2. Obtener tu **API Token** personal desde tu perfil de usuario en la aplicación web (Menú de Perfil > **API Tokens** > *Generar API Token*).

---

## Configuración en Clientes de IA

### 1. Antigravity IDE

Agrega la siguiente configuración en tu archivo global `~/.gemini/config/mcp_config.json` o en el archivo de proyecto `.agents/mcp_config.json`:

```json
{
  "mcpServers": {
    "secondbrain": {
      "command": "node",
      "args": ["c:/laragon/www/todo/mcp-server/index.js"],
      "env": {
        "SECONDBRAIN_URL": "http://localhost:8000",
        "SECONDBRAIN_API_TOKEN": "TU_API_TOKEN_AQUI"
      }
    }
  }
}
```

---

### 2. Claude Desktop

En tu archivo de configuración de Claude Desktop (`%APPDATA%\Claude\claude_desktop_config.json` en Windows o `~/Library/Application Support/Claude/claude_desktop_config.json` en macOS):

```json
{
  "mcpServers": {
    "secondbrain": {
      "command": "node",
      "args": ["C:\\laragon\\www\\todo\\mcp-server\\index.js"],
      "env": {
        "SECONDBRAIN_URL": "http://localhost:8000",
        "SECONDBRAIN_API_TOKEN": "TU_API_TOKEN_AQUI"
      }
    }
  }
}
```

---

### 3. Cursor IDE

En Cursor (`.cursor/mcp.json` o Settings > Features > MCP):

```json
{
  "mcpServers": {
    "secondbrain": {
      "command": "node",
      "args": ["C:/laragon/www/todo/mcp-server/index.js"],
      "env": {
        "SECONDBRAIN_URL": "http://localhost:8000",
        "SECONDBRAIN_API_TOKEN": "TU_API_TOKEN_AQUI"
      }
    }
  }
}
```

---

### 4. Windsurf / Codeium

En `~/.codeium/windsurf/mcp_config.json`:

```json
{
  "mcpServers": {
    "secondbrain": {
      "command": "node",
      "args": ["C:/laragon/www/todo/mcp-server/index.js"],
      "env": {
        "SECONDBRAIN_URL": "http://localhost:8000",
        "SECONDBRAIN_API_TOKEN": "TU_API_TOKEN_AQUI"
      }
    }
  }
}
```

---

## Prueba de Funcionamiento Manual

Puedes probar el servidor MCP directamente desde la terminal con:

```bash
cd c:\laragon\www\todo\mcp-server
node index.js
```

El servidor quedará a la espera de comandos JSON-RPC 2.0 a través de `stdio`.
