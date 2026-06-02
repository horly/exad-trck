<?php

namespace App\Models;

use Database\Factories\FleetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fleet extends Model
{
    /** @use HasFactory<FleetFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'subscription_id',
        'name',
        'code',
        'description',
        'status',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('permission')
            ->withTimestamps();
    }

    public function managers(): BelongsToMany
    {
        return $this->users()->where('fleet_user.permission', 'manager');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperadmin()) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->whereHas('users', fn (Builder $query): Builder => $query->whereKey($user->id))
                ->orWhere(function (Builder $query) use ($user): void {
                    $query
                        ->whereDoesntHave('users')
                        ->where('subscription_id', $user->subscription_id);
                });
        });
    }
}
