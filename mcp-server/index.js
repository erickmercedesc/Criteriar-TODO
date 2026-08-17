#!/usr/bin/env node

/**
 * SecondBrain MCP Server
 * 
 * Model Context Protocol (MCP) Server for SecondBrain TODO & Scoring System.
 * Connects AI agents (Antigravity, Claude, Cursor, Windsurf) to SecondBrain.
 */

const { Server } = require("@modelcontextprotocol/sdk/server/index.js");
const { StdioServerTransport } = require("@modelcontextprotocol/sdk/server/stdio.js");
const {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} = require("@modelcontextprotocol/sdk/types.js");

const BASE_URL = process.env.SECONDBRAIN_URL || "http://localhost:8000";
const API_TOKEN = process.env.SECONDBRAIN_API_TOKEN || "";

if (!API_TOKEN) {
  console.error("Warning: SECONDBRAIN_API_TOKEN is not set. API requests will fail if authentication is required.");
}

/**
 * Helper to make authenticated HTTP requests to the SecondBrain API.
 */
async function apiRequest(endpoint, options = {}) {
  const url = `${BASE_URL.replace(/\/+$/, '')}/api/${endpoint.replace(/^\/+/, '')}`;
  const headers = {
    "Accept": "application/json",
    "Content-Type": "application/json",
    ...(API_TOKEN ? { "Authorization": `Bearer ${API_TOKEN}` } : {}),
    ...(options.headers || {}),
  };

  try {
    const response = await fetch(url, {
      ...options,
      headers,
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
      const errorMsg = data?.message || `HTTP ${response.status}: ${response.statusText}`;
      throw new Error(`SecondBrain API Error: ${errorMsg}`);
    }

    return data;
  } catch (error) {
    throw new Error(`Failed request to ${url}: ${error.message}`);
  }
}

// Initialize MCP Server
const server = new Server(
  {
    name: "secondbrain-mcp",
    version: "1.0.0",
  },
  {
    capabilities: {
      tools: {},
    },
  }
);

// Define Tools
server.setRequestHandler(ListToolsRequestSchema, async () => {
  return {
    tools: [
      {
        name: "secondbrain_get_top_tasks",
        description: "Get the highest priority tasks recommended by SecondBrain's scoring engine right now. Tasks are ranked by combined score (project base_score + criteria points).",
        inputSchema: {
          type: "object",
          properties: {
            project_id: {
              type: "number",
              description: "Optional project ID to filter priority tasks strictly for a specific project.",
            },
          },
        },
      },
      {
        name: "secondbrain_list_tasks",
        description: "List tasks from SecondBrain with flexible filters for project, completion status, score range, and criteria tags.",
        inputSchema: {
          type: "object",
          properties: {
            project_id: {
              type: "number",
              description: "Filter by project ID.",
            },
            completed: {
              type: "boolean",
              description: "Filter completed tasks (true) or active pending tasks (false). Defaults to false (active).",
            },
            min_score: {
              type: "number",
              description: "Minimum total score threshold.",
            },
            max_score: {
              type: "number",
              description: "Maximum total score threshold.",
            },
            criteria_ids: {
              type: "string",
              description: "Comma-separated criteria IDs to filter tasks that have any of these criteria.",
            },
          },
        },
      },
      {
        name: "secondbrain_get_task",
        description: "Get full details of a specific task, including technical specs/notes, assigned criteria, project, and total score.",
        inputSchema: {
          type: "object",
          properties: {
            task_id: {
              type: "number",
              description: "The ID of the task to retrieve.",
            },
          },
          required: ["task_id"],
        },
      },
      {
        name: "secondbrain_create_task",
        description: "Create a new task in SecondBrain. Notes should contain technical specifications or acceptance criteria. Criteria add points to prioritize the task.",
        inputSchema: {
          type: "object",
          properties: {
            title: {
              type: "string",
              description: "The title or action name of the task.",
            },
            notes: {
              type: "string",
              description: "Technical specifications, context, links, or implementation details for the task.",
            },
            project_id: {
              type: "number",
              description: "Optional project ID to assign this task to.",
            },
            criteria_ids: {
              type: "array",
              items: { type: "number" },
              description: "Array of scoring criterion IDs to attach to this task.",
            },
          },
          required: ["title"],
        },
      },
      {
        name: "secondbrain_update_task",
        description: "Update an existing task's title, technical notes/specs, project, or scoring criteria.",
        inputSchema: {
          type: "object",
          properties: {
            task_id: {
              type: "number",
              description: "The ID of the task to update.",
            },
            title: {
              type: "string",
              description: "Updated title of the task.",
            },
            notes: {
              type: "string",
              description: "Updated technical notes or specifications.",
            },
            project_id: {
              type: "number",
              description: "Updated project ID (or null for Global).",
            },
            criteria_ids: {
              type: "array",
              items: { type: "number" },
              description: "Updated array of criterion IDs.",
            },
          },
          required: ["task_id"],
        },
      },
      {
        name: "secondbrain_complete_task",
        description: "Mark a task as completed or uncompleted. Completing a task automatically credits all points (project base score + criteria points) to daily statistics.",
        inputSchema: {
          type: "object",
          properties: {
            task_id: {
              type: "number",
              description: "The ID of the task.",
            },
            is_completed: {
              type: "boolean",
              description: "True to complete the task (default), false to mark as pending.",
              default: true,
            },
          },
          required: ["task_id"],
        },
      },
      {
        name: "secondbrain_delete_task",
        description: "Delete a task from SecondBrain.",
        inputSchema: {
          type: "object",
          properties: {
            task_id: {
              type: "number",
              description: "The ID of the task to delete.",
            },
          },
          required: ["task_id"],
        },
      },
      {
        name: "secondbrain_list_projects",
        description: "List all projects in SecondBrain with their base score and specific criteria.",
        inputSchema: {
          type: "object",
          properties: {},
        },
      },
      {
        name: "secondbrain_create_project",
        description: "Create a new project in SecondBrain with a base score and identifying color.",
        inputSchema: {
          type: "object",
          properties: {
            name: {
              type: "string",
              description: "Name of the project.",
            },
            color: {
              type: "string",
              description: "Hex color (e.g. '#6C63FF', '#22C55E', '#F59E0B', '#38BDF8').",
            },
            base_score: {
              type: "number",
              description: "Base score points automatically inherited by all tasks in this project (e.g. 30).",
            },
          },
          required: ["name"],
        },
      },
      {
        name: "secondbrain_list_scoring_criteria",
        description: "List scoring criteria. Can filter by project_id or list global criteria.",
        inputSchema: {
          type: "object",
          properties: {
            project_id: {
              type: "number",
              description: "Optional project ID to get criteria specific to that project.",
            },
            global: {
              type: "boolean",
              description: "Set to true to only get global criteria.",
            },
          },
        },
      },
      {
        name: "secondbrain_create_scoring_criterion",
        description: "Create a new scoring criterion in SecondBrain. Can be global (project_id null) or project-specific.",
        inputSchema: {
          type: "object",
          properties: {
            name: {
              type: "string",
              description: "Name of the criterion (e.g. 'Stripe Integration', 'Urgente').",
            },
            points: {
              type: "number",
              description: "Points assigned to this criterion (-100 to 100).",
            },
            color: {
              type: "string",
              description: "Hex color code (e.g. '#22C55E', '#EF4444').",
            },
            project_id: {
              type: "number",
              description: "Optional project ID. If omitted, the criterion is Global.",
            },
            is_complex_marker: {
              type: "boolean",
              description: "Set to true to mark tasks with this criterion as complex (triggers anti-burnout check in Pomodoro).",
            },
          },
          required: ["name", "points", "color"],
        },
      },
      {
        name: "secondbrain_get_productivity_stats",
        description: "Get today's productivity summary and 30-day historical statistics (focus time in Pomodoros, tasks completed, points earned).",
        inputSchema: {
          type: "object",
          properties: {},
        },
      },
    ],
  };
});

// Handle Tool Calls
server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;

  try {
    switch (name) {
      case "secondbrain_get_top_tasks": {
        const query = args?.project_id ? `?project_id=${args.project_id}` : "";
        const result = await apiRequest(`tasks/top${query}`);
        return {
          content: [
            {
              type: "text",
              text: JSON.stringify(result.data, null, 2),
            },
          ],
        };
      }

      case "secondbrain_list_tasks": {
        const params = new URLSearchParams();
        if (args?.project_id !== undefined) params.append("project_id", args.project_id);
        if (args?.completed !== undefined) params.append("completed", args.completed ? "1" : "0");
        if (args?.min_score !== undefined) params.append("min_score", args.min_score);
        if (args?.max_score !== undefined) params.append("max_score", args.max_score);
        if (args?.criteria_ids !== undefined) params.append("criteria_ids", Array.isArray(args.criteria_ids) ? args.criteria_ids.join(",") : args.criteria_ids);

        const qs = params.toString() ? `?${params.toString()}` : "";
        const result = await apiRequest(`tasks${qs}`);
        return {
          content: [
            {
              type: "text",
              text: JSON.stringify(result.data, null, 2),
            },
          ],
        };
      }

      case "secondbrain_get_task": {
        const result = await apiRequest(`tasks/${args.task_id}`);
        return {
          content: [
            {
              type: "text",
              text: JSON.stringify(result.data, null, 2),
            },
          ],
        };
      }

      case "secondbrain_create_task": {
        const payload = {
          title: args.title,
          notes: args.notes || null,
          project_id: args.project_id || null,
          criteria_ids: args.criteria_ids || [],
        };
        const result = await apiRequest("tasks", {
          method: "POST",
          body: JSON.stringify(payload),
        });
        return {
          content: [
            {
              type: "text",
              text: JSON.stringify({ message: "Task created successfully", task: result.data }, null, 2),
            },
          ],
        };
      }

      case "secondbrain_update_task": {
        const payload = {};
        if (args.title !== undefined) payload.title = args.title;
        if (args.notes !== undefined) payload.notes = args.notes;
        if (args.project_id !== undefined) payload.project_id = args.project_id;
        if (args.criteria_ids !== undefined) payload.criteria_ids = args.criteria_ids;

        const result = await apiRequest(`tasks/${args.task_id}`, {
          method: "PUT",
          body: JSON.stringify(payload),
        });
        return {
          content: [
            {
              type: "text",
              text: JSON.stringify({ message: "Task updated successfully", task: result.data }, null, 2),
            },
          ],
        };
      }

      case "secondbrain_complete_task": {
        const isCompleted = args.is_completed !== undefined ? args.is_completed : true;
        const result = await apiRequest(`tasks/${args.task_id}`, {
          method: "PUT",
          body: JSON.stringify({ is_completed: isCompleted }),
        });
        return {
          content: [
            {
              type: "text",
              text: JSON.stringify({
                message: isCompleted ? "Task marked as completed! Points awarded to daily stats." : "Task marked as active.",
                task: result.data,
              }, null, 2),
            },
          ],
        };
      }

      case "secondbrain_delete_task": {
        const result = await apiRequest(`tasks/${args.task_id}`, {
          method: "DELETE",
        });
        return {
          content: [
            {
              type: "text",
              text: JSON.stringify(result, null, 2),
            },
          ],
        };
      }

      case "secondbrain_list_projects": {
        const result = await apiRequest("projects");
        return {
          content: [
            {
              type: "text",
              text: JSON.stringify(result.data, null, 2),
            },
          ],
        };
      }

      case "secondbrain_create_project": {
        const payload = {
          name: args.name,
          color: args.color || "#6C63FF",
          base_score: args.base_score || 0,
        };
        const result = await apiRequest("projects", {
          method: "POST",
          body: JSON.stringify(payload),
        });
        return {
          content: [
            {
              type: "text",
              text: JSON.stringify({ message: "Project created successfully", project: result.data }, null, 2),
            },
          ],
        };
      }

      case "secondbrain_list_scoring_criteria": {
        const params = new URLSearchParams();
        if (args?.project_id !== undefined) params.append("project_id", args.project_id);
        if (args?.global) params.append("global", "1");

        const qs = params.toString() ? `?${params.toString()}` : "";
        const result = await apiRequest(`scoring-criteria${qs}`);
        return {
          content: [
            {
              type: "text",
              text: JSON.stringify(result.data, null, 2),
            },
          ],
        };
      }

      case "secondbrain_create_scoring_criterion": {
        const payload = {
          name: args.name,
          points: args.points,
          color: args.color,
          project_id: args.project_id || null,
          is_complex_marker: !!args.is_complex_marker,
        };
        const result = await apiRequest("scoring-criteria", {
          method: "POST",
          body: JSON.stringify(payload),
        });
        return {
          content: [
            {
              type: "text",
              text: JSON.stringify({ message: "Criterion created successfully", criterion: result.data }, null, 2),
            },
          ],
        };
      }

      case "secondbrain_get_productivity_stats": {
        const result = await apiRequest("statistics");
        return {
          content: [
            {
              type: "text",
              text: JSON.stringify(result.data, null, 2),
            },
          ],
        };
      }

      default:
        throw new Error(`Unknown tool: ${name}`);
    }
  } catch (error) {
    return {
      isError: true,
      content: [
        {
          type: "text",
          text: `Error executing ${name}: ${error.message}`,
        },
      ],
    };
  }
});

// Start Server
async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error("SecondBrain MCP Server running on stdio");
}

main().catch((error) => {
  console.error("Fatal error starting SecondBrain MCP server:", error);
  process.exit(1);
});
