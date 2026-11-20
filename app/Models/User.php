<?php

namespace App\Models;

use App\Support\AssignmentRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'role_id',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function shortUrls(): HasMany
    {
        return $this->hasMany(ShortUrl::class);
    }

    public function invitationsSent(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    public function roleName(): ?string
    {
        $this->loadMissing('role');

        return $this->role?->name;
    }

    public function hasRole(string $role): bool
    {
        return $this->roleName() === $role;
    }

    public function canInvite(): bool
    {
        return in_array($this->roleName(), [Role::SUPER_ADMIN, Role::ADMIN], true);
    }

    public function canCreateShortUrls(): bool
    {
        return $this->creationBlockedReason() === null;
    }

    public function creationBlockedReason(): ?string
    {
        return match ($this->roleName()) {
            Role::SUPER_ADMIN => AssignmentRules::SUPER_ADMIN_CREATE,
            Role::ADMIN, Role::MEMBER => AssignmentRules::ADMIN_MEMBER_CREATE,
            Role::SALES, Role::MANAGER => null,
            default => 'You are not allowed to create short urls.',
        };
    }

    public function visibilityNote(): string
    {
        return match ($this->roleName()) {
            Role::SUPER_ADMIN => AssignmentRules::SUPER_ADMIN_LIST,
            Role::ADMIN => AssignmentRules::ADMIN_LIST,
            Role::MEMBER => AssignmentRules::MEMBER_LIST,
            Role::SALES, Role::MANAGER => 'Sales and Manager can create short URLs for their company.',
            default => 'Your role cannot create short URLs.',
        };
    }
}
