<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'title',
        'notes',
        'is_completed',
        'completed_at',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'total_score',
    ];

    /**
     * Calcula la puntuación total de la tarea sumando los puntos de sus criterios y los de su proyecto.
     *
     * @return int
     */
    public function getTotalScoreAttribute(): int
    {
        $taskPoints = isset($this->attributes['criteria_sum_points'])
            ? (int) $this->attributes['criteria_sum_points']
            : (int) ($this->relationLoaded('criteria') ? $this->criteria->sum('points') : $this->criteria()->sum('points'));

        $projectBaseScore = isset($this->attributes['project_score'])
            ? (int) $this->attributes['project_score']
            : ($this->relationLoaded('project') && $this->project
                ? (int) $this->project->base_score
                : ($this->project_id ? (int) \App\Models\Project::where('id', $this->project_id)->value('base_score') : 0));

        return $taskPoints + $projectBaseScore;
    }

    /**
     * Obtener los criterios asignados a esta tarea.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function criteria()
    {
        return $this->belongsToMany(ScoringCriterion::class, 'task_scoring_criteria');
    }

    /**
     * Obtener el usuario al que pertenece la tarea.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener el proyecto al que pertenece la tarea.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
