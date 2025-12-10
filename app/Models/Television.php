<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Television extends Model
{
    protected $fillable = [
        'name',
        'price',
        'image',
        'product_link',
        'specs',
        'tv_category_id',
        'external_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TvCategory::class, 'tv_category_id');
    }
}
