<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $table = 'inventory_transactions';

    protected $fillable = [
        'farm_owner_id', 'inventory_type', 'inventoryable_type', 'inventoryable_id',
        'transaction_type', 'quantity', 'unit_price', 'total_amount', 'reference_id',
        'notes', 'recorded_by', 'transaction_date'
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function farmOwner()
    {
        return $this->belongsTo(FarmOwner::class);
    }

    public function inventoryable()
    {
        return $this->morphTo();
    }

    public function recordedByUser()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeByFarmOwner(Builder $query, int $farmOwnerId)
    {
        return $query->where('farm_owner_id', $farmOwnerId);
    }

    public function scopeByType(Builder $query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeByDate(Builder $query, $startDate, $endDate = null)
    {
        return $query->whereBetween('transaction_date', [
            $startDate,
            $endDate ?? $startDate->copy()->endOfDay()
        ]);
    }
}
