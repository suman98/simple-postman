<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function endpoints(): HasMany
    {
        return $this->hasMany(Endpoint::class);
    }
}
