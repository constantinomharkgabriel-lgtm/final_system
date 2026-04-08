<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class EggInventory extends Model
{
    use HasFactory;

    protected $table = 'egg_inventory';

    protected $fillable = [
        'farm_owner_id', 'flock_id', 'egg_collection_id', 'egg_type', 'grade', 'size',
        'quantity_total', 'quantity_available', 'quantity_sold', 'quantity_expired',
        'collection_date', 'freshness_expires_at', 'status', 'batch_id'
    ];

    protected $casts = [
        'collection_date' => 'date',
        'freshness_expires_at' => 'date',
    ];

    protected $appends = ['days_until_expiry', 'product_name'];

    // Relationships
    public function farmOwner()
    {
        return $this->belongsTo(FarmOwner::class);
    }

    public function flock()
    {
        return $this->belongsTo(Flock::class);
    }

    public function eggCollection()
    {
        return $this->belongsTo(EggCollection::class);
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'inventory_id', 'id');
    }

    // Scopes
    public function scopeByFarmOwner(Builder $query, int $farmOwnerId)
    {
        return $query->where('farm_owner_id', $farmOwnerId);
    }

    public function scopeInStock(Builder $query)
    {
        return $query->where('quantity_available', '>', 0)
                    ->where('status', '!=', 'expired');
    }

    public function scopeByGrade(Builder $query, string $grade)
    {
        return $query->where('grade', $grade);
    }

    public function scopeByType(Builder $query, string $eggType)
    {
        return $query->where('egg_type', $eggType);
    }

    public function scopeBySize(Builder $query, string $size)
    {
        return $query->where('size', $size);
    }

    public function scopeFresh(Builder $query)
    {
        return $query->where('status', 'fresh')
                    ->where('freshness_expires_at', '>=', today());
    }

    public function scopeExpiringToday(Builder $query)
    {
        return $query->where('freshness_expires_at', '=', today());
    }

    // Attributes
    public function getDaysUntilExpiryAttribute(): int
    {
        return now()->diffInDays($this->freshness_expires_at, false);
    }

    public function getProductNameAttribute(): string
    {
        return ucfirst($this->egg_type) . " Eggs - Grade {$this->grade} - " . ucfirst($this->size);
    }

    // Methods
    public function reserve(int $quantity): bool
    {
        if ($this->quantity_available >= $quantity) {
            $this->quantity_available -= $quantity;
            $this->save();
            return true;
        }
        return false;
    }

    public function recordSale(int $quantity): void
    {
        $this->quantity_sold += $quantity;
        $this->quantity_available -= $quantity;
        $this->save();

        InventoryTransaction::create([
            'farm_owner_id' => $this->farm_owner_id,
            'inventory_type' => 'egg',
            'inventoryable_type' => self::class,
            'inventoryable_id' => $this->id,
            'transaction_type' => 'sale',
            'quantity' => $quantity,
            'recorded_by' => auth()->id(),
        ]);
    }

    public function markExpired(): void
    {
        $this->status = 'expired';
        $this->quantity_expired = $this->quantity_available;
        $this->quantity_available = 0;
        $this->save();

        InventoryTransaction::create([
            'farm_owner_id' => $this->farm_owner_id,
            'inventory_type' => 'egg',
            'inventoryable_type' => self::class,
            'inventoryable_id' => $this->id,
            'transaction_type' => 'expiry',
            'quantity' => $this->quantity_expired,
            'recorded_by' => auth()->id(),
            'notes' => 'Eggs expired on ' . $this->freshness_expires_at->format('Y-m-d'),
        ]);
    }

    public static function updateFreshStatus(): void
    {
        // Mark expiring soon
        self::where('freshness_expires_at', '=', today()->addDay())
            ->where('status', '!=', 'expired')
            ->update(['status' => 'expiring_soon']);

        // Mark expired
        self::where('freshness_expires_at', '<', today())
            ->where('status', '!=', 'expired')
            ->each(fn($item) => $item->markExpired());
    }
}
