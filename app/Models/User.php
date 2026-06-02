<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'subscription_id',
        'name',
        'email',
        'password',
        'role',
        'status',
        'disabled_at',
        'permissions',
        'phone',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'disabled_at' => 'datetime',
            'permissions' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(UserLoginHistory::class)->latest('logged_in_at');
    }

    public function fleets(): BelongsToMany
    {
        return $this->belongsToMany(Fleet::class)
            ->withPivot('permission')
            ->withTimestamps();
    }

    public function isSuperadmin(): bool
    {
        return $this->role === UserRole::Superadmin;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isSimpleUser(): bool
    {
        return $this->role === UserRole::User;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->disabled_at === null;
    }

    public function canAccessSubscription(Subscription|int|null $subscription): bool
    {
        if ($this->isSuperadmin()) {
            return true;
        }

        $subscriptionId = $subscription instanceof Subscription ? $subscription->id : $subscription;

        return $subscriptionId !== null && (int) $this->subscription_id === (int) $subscriptionId;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->whereNull('disabled_at');
    }

    public function scopeForSubscription(Builder $query, Subscription|int $subscription): Builder
    {
        $subscriptionId = $subscription instanceof Subscription ? $subscription->id : $subscription;

        return $query->where('subscription_id', $subscriptionId);
    }

    public function scopeSuperadmins(Builder $query): Builder
    {
        return $query->where('role', UserRole::Superadmin->value);
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('role', UserRole::Admin->value);
    }

    public function scopeSimpleUsers(Builder $query): Builder
    {
        return $query->where('role', UserRole::User->value);
    }
}
