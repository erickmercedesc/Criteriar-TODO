<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyStatistic;
use App\Models\Project;
use App\Models\ScoringCriterion;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Native MCP (Model Context Protocol) Server Controller
 * 
 * Implements MCP 2024-11-05 specification over SSE and HTTP POST.
 * Compatible with Antigravity, Cursor, Claude Desktop, Windsurf, and custom AI agents.
 */
class McpController extends Controller
{
    /**
     * Authenticate request from Bearer Token or api_token query/param.
     */
    protected function authenticateUser(Request $request): ?User
    {
        if ($request->user()) {
            return $request->user();
        }

        $token = $request->bearerToken() 
            ?: $request->query('api_token') 
            ?: $request->query('token')
            ?: $request->input('api_token');

        if (!$token) {
            return null;
        }

        return User::where('api_token', $token)->first();
    }

    /**
     * Handle MCP SSE Connection (GET /api/mcp/sse or POST /api/mcp/sse fallback)
     */
    public function sse(Request $request)
    {
        // If client sends POST to /sse, handle as standard message to prevent 405 Method Not Allowed
        if ($request->isMethod('POST')) {
            return $this->handleMessage($request);
        }

        $user = $this->authenticateUser($request);

        if (!$user) {
            return response()->json(['error' => 'Unauthorized: Invalid or missing API token'], 401);
        }

        $sessionId = Str::uuid()->toString();
        Cache::put("mcp_session_{$sessionId}", $user->id, now()->addHours(6));

        $schemeAndHost = $request->getSchemeAndHttpHost();
        $token = $request->bearerToken() 
            ?: $request->query('api_token') 
            ?: $request->query('token')
            ?: $request->input('api_token');

        $tokenParam = $token ? "&api_token=" . urlencode($token) : "";
        $postEndpoint = "{$schemeAndHost}/api/mcp/message?sessionId={$sessionId}{$tokenParam}";

        $response = new StreamedResponse(function () use ($sessionId, $postEndpoint) {
            // Disable output buffering
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            // Send initial SSE endpoint announcement
            echo "event: endpoint\n";
            echo "data: {$postEndpoint}\n\n";
            flush();

            $startTime = time();
            $lastPing = time();

            while ((time() - $startTime) < 300 && !connection_aborted()) {
                // Check if there are messages in outbox for this session
                $messages = Cache::pull("mcp_outbox_{$sessionId}", []);
                if (!empty($messages)) {
                    foreach ($messages as $msg) {
                        echo "event: message\n";
                        echo "data: " . json_encode($msg, JSON_UNESCAPED_UNICODE) . "\n\n";
                        flush();
                    }
                }

                // Heartbeat ping every 15s
                if ((time() - $lastPing) >= 15) {
                    echo "event: ping\n";
                    echo "data: {}\n\n";
                    flush();
                    $lastPing = time();
                }

                usleep(100000); // 100ms poll interval
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-transform');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');

        return $response;
    }

    /**
     * Handle MCP JSON-RPC messages (POST /api/mcp/message or POST /api/mcp)
     */
    public function handleMessage(Request $request)
    {
        $sessionId = $request->query('sessionId') ?: $request->input('sessionId');
        $user = null;

        if ($sessionId) {
            $userId = Cache::get("mcp_session_{$sessionId}");
            if ($userId) {
                $user = User::find($userId);
            }
        }

        if (!$user) {
            $user = $this->authenticateUser($request);
        }

        if (!$user) {
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $request->input('id'),
                'error' => [
                    'code' => -32000,
                    'message' => 'Unauthorized: Invalid or missing API token',
                ],
            ], 401, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Headers' => '*',
            ]);
        }

        $rawContent = $request->getContent();
        $payload = json_decode($rawContent, true);
        if (!is_array($payload)) {
            $payload = $request->json()->all() ?: $request->all();
        }

        $method = $payload['method'] ?? null;
        $id = $payload['id'] ?? null;
        $params = $payload['params'] ?? [];

        // Handle Notifications (no response needed)
        if ($method === 'notifications/initialized') {
            return response()->noContent(200, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Headers' => '*',
            ]);
        }

        $responsePayload = null;

        switch ($method) {
            case 'initialize':
                $responsePayload = [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => [
                        'protocolVersion' => '2024-11-05',
                        'capabilities' => [
                            'tools' => new \stdClass(),
                        ],
                        'serverInfo' => [
                            'name' => 'SecondBrain MCP Server',
                            'version' => '1.0.0',
                        ],
                    ],
                ];
                break;

            case 'tools/list':
                $responsePayload = [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => [
                        'tools' => $this->getToolsSchema(),
                    ],
                ];
                break;

            case 'tools/call':
                $toolName = $params['name'] ?? '';
                $toolArgs = $params['arguments'] ?? [];

                try {
                    $result = $this->executeTool($user, $toolName, $toolArgs);
                    $responsePayload = [
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => [
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => is_string($result) ? $result : json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                                ],
                            ],
                        ],
                    ];
                } catch (\Throwable $e) {
                    $responsePayload = [
                        'jsonrpc' => '2.0',
                        'id' => $id,
                        'result' => [
                            'isError' => true,
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => "Error executing tool {$toolName}: " . $e->getMessage(),
                                ],
                            ],
                        ],
                    ];
                }
                break;

            default:
                $responsePayload = [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'error' => [
                        'code' => -32601,
                        'message' => "Method not found: {$method}",
                    ],
                ];
                break;
        }

        // If this message belongs to an active SSE session, queue it to the SSE outbox
        if ($sessionId && $responsePayload) {
            $outbox = Cache::get("mcp_outbox_{$sessionId}", []);
            $outbox[] = $responsePayload;
            Cache::put("mcp_outbox_{$sessionId}", $outbox, now()->addMinutes(5));
        }

        // Always return the response payload in the HTTP response for direct clients as well
        return response()->json($responsePayload, 200, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => '*',
        ]);
    }

    /**
     * Available Tools Schemas for MCP.
     */
    protected function getToolsSchema(): array
    {
        return [
            [
                'name' => 'secondbrain_get_top_tasks',
                'description' => "Get the highest priority tasks recommended by SecondBrain's scoring algorithm to do right now.",
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => [
                            'type' => 'number',
                            'description' => 'Optional project ID to filter priority tasks for a specific project.',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'secondbrain_list_tasks',
                'description' => 'List tasks with flexible filters for project, completed status, score range, and criteria.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'number', 'description' => 'Filter by project ID.'],
                        'completed' => ['type' => 'boolean', 'description' => 'Filter completed (true) or active (false) tasks.'],
                        'min_score' => ['type' => 'number', 'description' => 'Minimum score threshold.'],
                        'max_score' => ['type' => 'number', 'description' => 'Maximum score threshold.'],
                    ],
                ],
            ],
            [
                'name' => 'secondbrain_get_task',
                'description' => 'Get full details of a specific task including technical specs/notes, criteria, and project.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'task_id' => ['type' => 'number', 'description' => 'The ID of the task.'],
                    ],
                    'required' => ['task_id'],
                ],
            ],
            [
                'name' => 'secondbrain_create_task',
                'description' => 'Create a new task in SecondBrain. Notes contain technical specifications. Criteria add points.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string', 'description' => 'Task title.'],
                        'notes' => ['type' => 'string', 'description' => 'Technical specifications or context.'],
                        'project_id' => ['type' => 'number', 'description' => 'Optional project ID.'],
                        'criteria_ids' => ['type' => 'array', 'items' => ['type' => 'number'], 'description' => 'Criteria IDs.'],
                    ],
                    'required' => ['title'],
                ],
            ],
            [
                'name' => 'secondbrain_update_task',
                'description' => "Update an existing task's title, technical notes/specs, project, or scoring criteria.",
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'task_id' => ['type' => 'number', 'description' => 'The task ID.'],
                        'title' => ['type' => 'string', 'description' => 'Updated title.'],
                        'notes' => ['type' => 'string', 'description' => 'Updated notes/specs.'],
                        'project_id' => ['type' => 'number', 'description' => 'Updated project ID.'],
                        'criteria_ids' => ['type' => 'array', 'items' => ['type' => 'number'], 'description' => 'Updated criteria IDs.'],
                    ],
                    'required' => ['task_id'],
                ],
            ],
            [
                'name' => 'secondbrain_complete_task',
                'description' => 'Mark a task as completed or pending. Completing automatically awards all points to daily statistics.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'task_id' => ['type' => 'number', 'description' => 'The task ID.'],
                        'is_completed' => ['type' => 'boolean', 'description' => 'True to complete (default), false to reactivate.'],
                    ],
                    'required' => ['task_id'],
                ],
            ],
            [
                'name' => 'secondbrain_delete_task',
                'description' => 'Delete a task from SecondBrain.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'task_id' => ['type' => 'number', 'description' => 'The task ID.'],
                    ],
                    'required' => ['task_id'],
                ],
            ],
            [
                'name' => 'secondbrain_list_projects',
                'description' => 'List all projects with their base score and specific criteria.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name' => 'secondbrain_create_project',
                'description' => 'Create a new project with name, color, and base score.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'Project name.'],
                        'color' => ['type' => 'string', 'description' => 'Hex color code (e.g. #6C63FF).'],
                        'base_score' => ['type' => 'number', 'description' => 'Base score points.'],
                    ],
                    'required' => ['name'],
                ],
            ],
            [
                'name' => 'secondbrain_list_scoring_criteria',
                'description' => 'List scoring criteria (Global or by project_id).',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'project_id' => ['type' => 'number', 'description' => 'Filter by project ID.'],
                        'global' => ['type' => 'boolean', 'description' => 'True for only global criteria.'],
                    ],
                ],
            ],
            [
                'name' => 'secondbrain_create_scoring_criterion',
                'description' => 'Create a scoring criterion with points and optional complex anti-burnout marker.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'name' => ['type' => 'string', 'description' => 'Criterion name.'],
                        'points' => ['type' => 'number', 'description' => 'Points (-100 to 100).'],
                        'color' => ['type' => 'string', 'description' => 'Hex color code.'],
                        'project_id' => ['type' => 'number', 'description' => 'Optional project ID (omitted = Global).'],
                        'is_complex_marker' => ['type' => 'boolean', 'description' => 'Marks as complex task.'],
                    ],
                    'required' => ['name', 'points', 'color'],
                ],
            ],
            [
                'name' => 'secondbrain_get_productivity_stats',
                'description' => 'Get today productivity stats and 30-day history (focus time, completed tasks, points earned).',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
        ];
    }

    /**
     * Execute MCP Tool for Authenticated User.
     */
    protected function executeTool(User $user, string $name, array $args)
    {
        switch ($name) {
            case 'secondbrain_get_top_tasks':
                $projectId = $args['project_id'] ?? null;
                return $user->tasks()
                    ->where('is_completed', false)
                    ->when($projectId !== null && $projectId !== '', function ($q) use ($projectId) {
                        if ($projectId === 'none' || $projectId === 'null' || $projectId === 0 || $projectId === '0') {
                            return $q->whereNull('tasks.project_id');
                        }
                        return $q->where('tasks.project_id', $projectId);
                    })
                    ->with('criteria', 'project')
                    ->withSum('criteria', 'points')
                    ->addSelect(['project_score' => Project::select('base_score')->whereColumn('projects.id', 'tasks.project_id')->limit(1)])
                    ->orderByRaw('COALESCE(project_score, 0) DESC, COALESCE(criteria_sum_points, 0) DESC')
                    ->orderBy('created_at')
                    ->limit(3)
                    ->get();

            case 'secondbrain_list_tasks':
                $isCompleted = isset($args['completed']) ? (bool)$args['completed'] : false;
                $projectId = $args['project_id'] ?? null;
                $minScore = $args['min_score'] ?? null;
                $maxScore = $args['max_score'] ?? null;

                return $user->tasks()
                    ->where('is_completed', $isCompleted)
                    ->when($projectId !== null && $projectId !== '', function ($q) use ($projectId) {
                        if ($projectId === 'none' || $projectId === 'null' || $projectId === 0 || $projectId === '0') {
                            return $q->whereNull('tasks.project_id');
                        }
                        return $q->where('tasks.project_id', $projectId);
                    })
                    ->with('criteria', 'project')
                    ->withSum('criteria', 'points')
                    ->addSelect(['project_score' => Project::select('base_score')->whereColumn('projects.id', 'tasks.project_id')->limit(1)])
                    ->when($minScore !== null, fn($q) => $q->havingRaw('(COALESCE(project_score, 0) + COALESCE(criteria_sum_points, 0)) >= ?', [$minScore]))
                    ->when($maxScore !== null, fn($q) => $q->havingRaw('(COALESCE(project_score, 0) + COALESCE(criteria_sum_points, 0)) <= ?', [$maxScore]))
                    ->orderByRaw('COALESCE(project_score, 0) DESC, COALESCE(criteria_sum_points, 0) DESC')
                    ->orderBy('created_at')
                    ->get();

            case 'secondbrain_get_task':
                return $user->tasks()
                    ->with('criteria', 'project')
                    ->withSum('criteria', 'points')
                    ->addSelect(['project_score' => Project::select('base_score')->whereColumn('projects.id', 'tasks.project_id')->limit(1)])
                    ->findOrFail($args['task_id']);

            case 'secondbrain_create_task':
                $task = $user->tasks()->create([
                    'title' => $args['title'],
                    'notes' => $args['notes'] ?? null,
                    'project_id' => $args['project_id'] ?? null,
                    'is_completed' => false,
                ]);

                if (!empty($args['criteria_ids'])) {
                    $task->criteria()->sync($args['criteria_ids']);
                }

                return [
                    'message' => 'Task created successfully',
                    'task' => $task->load('criteria', 'project'),
                ];

            case 'secondbrain_update_task':
                $task = $user->tasks()->findOrFail($args['task_id']);
                
                $data = [];
                if (isset($args['title'])) $data['title'] = $args['title'];
                if (array_key_exists('notes', $args)) $data['notes'] = $args['notes'];
                if (array_key_exists('project_id', $args)) $data['project_id'] = $args['project_id'];

                if (!empty($data)) {
                    $task->update($data);
                }

                if (isset($args['criteria_ids'])) {
                    $task->criteria()->sync($args['criteria_ids']);
                }

                return [
                    'message' => 'Task updated successfully',
                    'task' => $task->fresh()->load('criteria', 'project'),
                ];

            case 'secondbrain_complete_task':
                $task = $user->tasks()->with('criteria', 'project')->findOrFail($args['task_id']);
                $isCompleted = $args['is_completed'] ?? true;

                $task->is_completed = $isCompleted;
                $task->completed_at = $isCompleted ? now() : null;
                $task->save();

                $points = ($task->project ? $task->project->base_score : 0) + $task->criteria()->sum('points');
                DailyStatistic::adjustStat($user->id, now()->toDateString(), 0, $isCompleted ? 1 : -1, $isCompleted ? $points : -$points);

                return [
                    'message' => $isCompleted ? 'Task marked as completed! Points added to stats.' : 'Task marked as pending.',
                    'task' => $task->fresh()->load('criteria', 'project'),
                ];

            case 'secondbrain_delete_task':
                $task = $user->tasks()->findOrFail($args['task_id']);
                $task->delete();
                return ['message' => 'Task deleted successfully'];

            case 'secondbrain_list_projects':
                return $user->projects()->with('criteria')->orderByDesc('base_score')->orderBy('name')->get();

            case 'secondbrain_create_project':
                $project = $user->projects()->create([
                    'name' => $args['name'],
                    'color' => $args['color'] ?? '#6C63FF',
                    'base_score' => $args['base_score'] ?? 0,
                ]);
                return [
                    'message' => 'Project created successfully',
                    'project' => $project->load('criteria'),
                ];

            case 'secondbrain_list_scoring_criteria':
                return $user->scoringCriteria()
                    ->when($args['global'] ?? false, fn($q) => $q->whereNull('project_id'))
                    ->when(isset($args['project_id']), fn($q) => $q->where('project_id', $args['project_id']))
                    ->orderByDesc('points')
                    ->get();

            case 'secondbrain_create_scoring_criterion':
                $criterion = $user->scoringCriteria()->create([
                    'name' => $args['name'],
                    'points' => (int)$args['points'],
                    'color' => $args['color'],
                    'project_id' => $args['project_id'] ?? null,
                    'is_complex_marker' => (bool)($args['is_complex_marker'] ?? false),
                ]);
                return [
                    'message' => 'Criterion created successfully',
                    'criterion' => $criterion,
                ];

            case 'secondbrain_get_productivity_stats':
                $stats = DailyStatistic::where('user_id', $user->id)
                    ->where('date', '>=', Carbon::now()->subDays(30)->toDateString())
                    ->orderBy('date', 'asc')
                    ->get();

                $today = $stats->firstWhere('date', Carbon::now()->toDateString()) ?? [
                    'date' => Carbon::now()->toDateString(),
                    'pomodoro_seconds' => 0,
                    'tasks_completed' => 0,
                    'points_earned' => 0,
                ];

                return [
                    'today' => $today,
                    'history_30_days' => $stats,
                ];

            default:
                throw new \InvalidArgumentException("Unknown tool: {$name}");
        }
    }
}
