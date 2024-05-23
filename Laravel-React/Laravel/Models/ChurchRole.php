<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\RefreshesPermissionCache;

class ChurchRole extends Model
{
    use HasFactory;
    use HasPermissions;
    use RefreshesPermissionCache;
    use LogsActivity;

    const DEFAULT_ROLE_PARISH_PRIEST = 'Parish Priest';
    const DEFAULT_ROLE_PRIEST = 'Priest';
    const DEFAULT_ROLE_VISITING_PASTOR = 'Visiting Pastor';
    const DEFAULT_ROLE_BELIEVER = 'Believer';
    const DEFAULT_ROLE_ADMIN = 'Administrator';

    const DEFAULT_ROLES = [
        self::DEFAULT_ROLE_PARISH_PRIEST,
        self::DEFAULT_ROLE_PRIEST,
        self::DEFAULT_ROLE_VISITING_PASTOR,
        self::DEFAULT_ROLE_BELIEVER,
        self::DEFAULT_ROLE_ADMIN,
    ];

    protected $guard_name = 'web'; // Important to replace "sanctum"

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]|bool
     */
    protected $guarded = ['id'];

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the church for the church role.
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * @return array
     */
    public function getAllPermissionNames(): array
    {
        return $this->getAllPermissions()->map(fn($permission) => $permission->name)->toArray();
    }

    /**
     * @return string[]
     */
    public static function defaultChurchPriestRoles(): array
    {
        return [
            self::DEFAULT_ROLE_ADMIN,
            self::DEFAULT_ROLE_PARISH_PRIEST,
            self::DEFAULT_ROLE_PRIEST,
            self::DEFAULT_ROLE_VISITING_PASTOR,
            self::DEFAULT_ROLE_BELIEVER,
        ];
    }
}
