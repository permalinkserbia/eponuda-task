<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TvCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'url',
        'parent_id',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TvCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(TvCategory::class, 'parent_id');
    }

    public function televisions(): HasMany
    {
        return $this->hasMany(Television::class, 'tv_category_id');
    }
}
