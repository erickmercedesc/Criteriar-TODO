---
name: secondbrain-agent
description: >-
  Expert guide for AI agents and planner bots on how to use SecondBrain's MCP tools,
  manage tasks, scoring criteria, projects, and navigate the productivity and anti-burnout system.
  Activate whenever creating, prioritizing, executing, or managing tasks in SecondBrain.
---

# SecondBrain AI Agent & MCP Operating Guide

This skill guides AI agents on how to interact with **SecondBrain** via its native Model Context Protocol (MCP) server. It defines the core productivity philosophy, tool execution workflows, and business rules to plan, execute, and track tasks effectively.

---

## 1. Core System Philosophy

SecondBrain is not a passive todo list; it is an **active decision engine** designed to eliminate human analysis paralysis and burnout through mathematics and gamification:

1. **Hierarchical Priority (The Math Rule):**
   $$\text{Ordering} = \text{Project Base Score DESC} \longrightarrow \sum (\text{Task Criteria Points}) \text{ DESC}$$
   - Tasks are prioritized first by the Project's base score, and secondly by the task's own criteria points.
   - Agents should never manually guess or override task priority; always let SecondBrain's scoring engine determine the order.

2. **Technical Specs in `notes`:**
   - The `notes` field of a task is reserved for **Technical Specifications**, acceptance criteria, dependencies, context links, and architectural notes.

3. **Anti-Burnout & Complexity (`is_complex_marker`):**
   - Criteria marked with `is_complex_marker: true` signal mentally demanding work. In Pomodoro sessions, completing or pausing complex tasks triggers break suggestions and skip options to prevent cognitive fatigue.

4. **Permanent Gamification Impact:**
   - Completing a task (`secondbrain_complete_task`) permanently credits all accumulated points to the user's `daily_statistics` and increments `tasks_completed`. Do not complete tasks prematurely.

---

## 2. Available MCP Tools Reference

| Tool | Purpose | Key Arguments |
| :--- | :--- | :--- |
| `secondbrain_get_top_tasks` | Get the top recommended task(s) to execute right now. | `project_id` (optional) |
| `secondbrain_list_tasks` | Search/filter tasks by status, score, project, or criteria. | `project_id`, `completed`, `min_score`, `max_score`, `criteria_ids` |
| `secondbrain_get_task` | Inspect full technical specs, criteria, and project details of a task. | `task_id` (required) |
| `secondbrain_create_task` | Create a task with technical specs, project, and scoring criteria. | `title` (required), `notes`, `project_id`, `criteria_ids` |
| `secondbrain_update_task` | Modify technical specs, title, project, or attached criteria. | `task_id` (required), `title`, `notes`, `project_id`, `criteria_ids` |
| `secondbrain_complete_task` | Mark a task done (awards points) or reactivate it. | `task_id` (required), `is_completed` (default: true) |
| `secondbrain_delete_task` | Remove a task permanently. | `task_id` (required) |
| `secondbrain_list_projects` | List all projects with their `base_score` and criteria. | None |
| `secondbrain_create_project` | Create a project with color and automatic base points. | `name` (required), `color`, `base_score` |
| `secondbrain_list_scoring_criteria` | List Global or project-specific scoring criteria. | `project_id`, `global` |
| `secondbrain_create_scoring_criterion` | Create a new criterion with point value and complex marker. | `name`, `points`, `color`, `project_id`, `is_complex_marker` |
| `secondbrain_get_productivity_stats` | Inspect daily focus hours, points earned, and 30-day trends. | None |

---

## 3. Standard Operating Procedures (Agent Runbooks)

### Runbook A: "What should I work on next?" (Execution Flow)
1. Call `secondbrain_get_top_tasks` (optionally pass `project_id` if the user is working in a specific project context).
2. The first task returned is the highest priority action.
3. Read the `notes` field to extract the **Technical Specs** and implementation steps.
4. Execute the work required by the task.
5. Once verified and finished, call `secondbrain_complete_task({ task_id: ID, is_completed: true })`.

---

### Runbook B: "Create a new task / Plan a feature" (Creation Flow)
1. **Discover Context:**
   - Call `secondbrain_list_projects` to find the appropriate project.
   - Call `secondbrain_list_scoring_criteria` (with `project_id` or `global: true`) to see available scoring criteria.
2. **Formulate Technical Specs:**
   - Write clear, concise, and actionable specifications in the `notes` argument (e.g. Acceptance Criteria, file paths, endpoints).
3. **Assign Criteria for Proper Scoring:**
   - **Never create a task with 0 criteria**, as it will have 0 priority points and sink to the bottom.
   - Select 1 to 3 relevant criteria (e.g., `Trabajo Urgente (+25)`, `Genera Dinero (+20)`).
   - If the task is mentally taxing or requires deep architecture, ensure a criterion with `is_complex_marker: true` is included.
4. **Call `secondbrain_create_task`:**
   ```json
   {
     "title": "Implement Stripe Webhook Validation",
     "project_id": 10,
     "criteria_ids": [17, 21],
     "notes": "Acceptance Criteria:\n1. Verify webhook signature using STRIPE_WEBHOOK_SECRET.\n2. Handle customer.subscription.updated event.\n3. Return HTTP 200 on success."
   }
   ```

---

### Runbook C: "Organize / Structure a New Project"
1. Call `secondbrain_create_project` with an identifying color and a sensible `base_score` (e.g., `30` to `50` for high-impact client projects, `15` to `20` for maintenance).
2. Call `secondbrain_create_scoring_criterion` passing the `project_id` to create project-specific tags (e.g., `Stripe Integration (+35)`).
3. Break down the project into discrete tasks and create them using `secondbrain_create_task`.

---

### Runbook D: "Review Productivity & Daily Standup"
1. Call `secondbrain_get_productivity_stats` to get today's focus time, completed task count, and points earned.
2. Call `secondbrain_list_tasks({ completed: true })` to summarize completed deliverables for the standup report.
3. Call `secondbrain_get_top_tasks` to announce the upcoming goals for the day.

---

## 4. Business Rules & Guardrails for AI

- **Do Not Guess IDs:** Always retrieve project and criteria IDs using `secondbrain_list_projects` and `secondbrain_list_scoring_criteria` before assigning them to tasks.
- **Do Not Manually Overwrite Priority:** Priorities are dynamically calculated by the database. To increase priority, assign higher-value criteria or increase the project's `base_score`.
- **Preserve Notes Integrity:** When updating a task with `secondbrain_update_task`, append or refine existing specs without erasing valuable context unless explicitly asked.
- **Negative Criteria Usage:** Negative criteria (e.g. `Procrastinación (-10)`) are intended to deprioritize distraction tasks without deleting them.
