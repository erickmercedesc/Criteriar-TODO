<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name',
        'color',
        'base_score',
        'user_id',
    ];

    protected $casts = [
        'base_score' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function criteria()
    {
        return $this->hasMany(ScoringCriterion::class);
    }
}
