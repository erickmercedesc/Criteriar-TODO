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
        'title',
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
}
