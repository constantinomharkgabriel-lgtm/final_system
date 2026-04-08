<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class EggCollection extends Model
{
    use HasFactory;

    protected $table = 'egg_collections';

    protected $fillable = [
        'farm_owner_id', 'flock_id', 'collection_date', 'eggs_collected',
        'eggs_broken', 'graded_a', 'graded_b', 'graded_c', 'batch_id'
    ];

    protected $casts = [
        'collection_date' => 'date',
    ];

    public function farmOwner()
    {
        return $this->belongsTo(FarmOwner::class);
    }

    public function flock()
    {
        return $this->belongsTo(Flock::class);
    }

    public function eggInventories()
    {
        return $this->hasMany(EggInventory::class);
    }

    public function scopeByFarmOwner(Builder $query, int $farmOwnerId)
    {
        return $query->where('farm_owner_id', $farmOwnerId);
    }

    public function getTotalEggsAttribute(): int
    {
        return $this->graded_a + $this->graded_b + $this->graded_c;
    }
}
