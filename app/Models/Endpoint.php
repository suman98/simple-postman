<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Endpoint extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'method',
        'url',
        'body_type',
        'headers',
        'params',
        'body',
    ];

    protected $casts = [
        'headers' => 'array',
        'params' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
