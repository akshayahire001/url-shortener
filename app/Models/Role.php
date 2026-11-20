<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const SUPER_ADMIN = 'SuperAdmin';

    public const ADMIN = 'Admin';

    public const MEMBER = 'Member';

    public const SALES = 'Sales';

    public const MANAGER = 'Manager';

    public const ALL = [
        self::SUPER_ADMIN,
        self::ADMIN,
        self::MEMBER,
        self::SALES,
        self::MANAGER,
    ];

    protected $fillable = [
        'name',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }
}
