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
}
