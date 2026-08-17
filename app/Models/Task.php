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

        $projectPoints = isset($this->attributes['project_score'])
            ? (int) $this->attributes['project_score']
            : ($this->relationLoaded('project') && $this->project
                ? ($this->project->relationLoaded('criteria') ? (int) $this->project->criteria->sum('points') : (int) $this->project->criteria()->sum('points'))
                : ($this->project_id ? (int) \Illuminate\Support\Facades\DB::table('project_scoring_criteria')
                    ->join('scoring_criteria', 'project_scoring_criteria.scoring_criterion_id', '=', 'scoring_criteria.id')
                    ->where('project_scoring_criteria.project_id', $this->project_id)
                    ->sum('scoring_criteria.points') : 0));

        return $taskPoints + $projectPoints;
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
