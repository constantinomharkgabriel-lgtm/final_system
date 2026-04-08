<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class LivestockInventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'livestock_inventory';

    protected $fillable = [
        'farm_owner_id', 'flock_id', 'livestock_type', 'age_weeks', 'weeks_until_ready',
        'estimated_ready_date', 'quantity_available_for_sale', 'quantity_reserved', 'quantity_sold',
        'average_weight_kg', 'status', 'acquisition_cost_per_unit', 'total_feed_cost',
        'total_vaccine_cost', 'ready_date', 'last_updated'
    ];

    protected $casts = [
        'estimated_ready_date' => 'date',
        'ready_date' => 'date',
        'last_updated' => 'date',
        'acquisition_cost_per_unit' => 'decimal:2',
        'total_feed_cost' => 'decimal:2',
        'total_vaccine_cost' => 'decimal:2',
        'average_weight_kg' => 'decimal:2',
    ];

    protected $appends = ['days_until_ready', 'total_cost_per_unit', 'can_be_sold'];

    // Relationships
    public function farmOwner()
    {
        return $this->belongsTo(FarmOwner::class);
    }

    public function flock()
    {
        return $this->belongsTo(Flock::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'livestock_inventory_id', 'id');
    }

    // Scopes
    public function scopeByFarmOwner(Builder $query, int $farmOwnerId)
    {
        return $query->where('farm_owner_id', $farmOwnerId);
    }

    public function scopeReadyForSale(Builder $query)
    {
        return $query->whereIn('status', ['ready_for_sale', 'partial_sale'])
                    ->where('quantity_available_for_sale', '>', 0);
    }

    public function scopeByType(Builder $query, string $type)
    {
        return $query->where('livestock_type', $type);
    }

    public function scopeGrowing(Builder $query)
    {
        return $query->where('status', 'growing');
    }

    // Attributes
    public function getDaysUntilReadyAttribute(): int
    {
        if (!$this->estimated_ready_date) {
            return $this->weeks_until_ready * 7;
        }
        return now()->diffInDays($this->estimated_ready_date, false);
    }

    public function getTotalCostPerUnitAttribute(): float
    {
        return $this->acquisition_cost_per_unit 
            + ($this->total_feed_cost / max($this->quantity_available_for_sale + $this->quantity_sold, 1))
            + ($this->total_vaccine_cost / max($this->quantity_available_for_sale + $this->quantity_sold, 1));
    }

    public function getCanBeSoldAttribute(): bool
    {
        return $this->status === 'ready_for_sale' || $this->status === 'partial_sale';
    }

    // Methods
    public function updateAge(int $weeks): void
    {
        $this->age_weeks = $weeks;
        
        // Calculate weeks until ready
        $readyAge = $this->getStandardReadyAge();
        $this->weeks_until_ready = max($readyAge - $weeks, 0);
        
        // Update estimated ready date
        if ($this->weeks_until_ready > 0) {
            $this->estimated_ready_date = now()->addWeeks($this->weeks_until_ready);
            $this->status = 'growing';
        } else {
            $this->estimated_ready_date = today();
            $this->ready_date = today();
            $this->status = 'ready_for_sale';
        }
        
        $this->last_updated = today();
        $this->save();
    }

    public function recordSale(int $quantity): bool
    {
        if ($this->quantity_available_for_sale >= $quantity) {
            $this->quantity_available_for_sale -= $quantity;
            $this->quantity_sold += $quantity;
            
            if ($this->quantity_available_for_sale > 0) {
                $this->status = 'partial_sale';
            } else {
                $this->status = 'sold_out';
            }
            
            $this->save();

            InventoryTransaction::create([
                'farm_owner_id' => $this->farm_owner_id,
                'inventory_type' => 'livestock',
                'inventoryable_type' => self::class,
                'inventoryable_id' => $this->id,
                'transaction_type' => 'sale',
                'quantity' => $quantity,
                'recorded_by' => auth()->id(),
            ]);

            return true;
        }
        return false;
    }

    public function getStandardReadyAge(): int
    {
        return match ($this->livestock_type) {
            'broiler' => 8,           // 8 weeks
            'layer' => 16,            // 16 weeks
            'breeder' => 20,          // 20 weeks
            'fighting_cock' => 24,    // 24 weeks
            'native' => 16,           // 16 weeks
            'duck' => 10,             // 10 weeks
            'quail' => 6,             // 6 weeks
            default => 8,
        };
    }
}
