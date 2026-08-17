<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property int $points
 * @property string $color
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property bool $is_complex_marker
 */
class ScoringCriterion extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'name',
        'points',
        'color',
        'is_complex_marker',
    ];

    protected $casts = [
        'is_complex_marker' => 'boolean',
    ];

    /**
     * Tareas que tienen asignado este criterio.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_scoring_criteria');
    }

    /**
     * Obtener el usuario al que pertenece este criterio.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener el proyecto al que pertenece este criterio (si es específico de un proyecto).
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope para obtener solo criterios globales (sin proyecto).
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('project_id');
    }

    /**
     * Scope para obtener criterios disponibles (globales + los del proyecto especificado).
     */
    public function scopeAvailableFor($query, $projectId = null)
    {
        if (!$projectId) {
            return $query->whereNull('project_id');
        }
        return $query->where(function ($q) use ($projectId) {
            $q->whereNull('project_id')->orWhere('project_id', $projectId);
        });
    }
}
