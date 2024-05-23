<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use \Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\SpatialBuilder;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property Point $coordinate
 * @property Polygon $area
 * @property string $role_name
 * @property ?float $distance
 * @property array | Collection $church_priests
 * @property bool $is_followed
 * @method static Builder query()
 * @method static Builder orderBy($column, $direction = 'asc')
 * @method static Builder find($column)
 * @method static SpatialBuilder updateOrCreate($whereArr, $dataArr)
 */
class Church extends Model
{
    use HasFactory;
    use LogsActivity;

    // for churches.type field
    const TYPE_ACTIVE = 'active';
    const TYPE_INACTIVE = 'inactive';
    const TYPE_OCCUPIED = 'occupied';
    const TYPE_HALF_BUILT = 'half-built';
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'coordinate' => Point::class,
        'area' => Polygon::class,
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]|bool
     */
    protected $guarded = ['id'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['social_links'];


    public function newEloquentBuilder($query): SpatialBuilder
    {
        return new SpatialBuilder($query);
    }

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return array
     */
    public function getSocialLinksAttribute(): array
    {
        return json_decode($this->attributes['social_links'] ?? '[]', true);
    }

    /**
     * Get the users for the church.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the users for the church.
     */
    public function churchUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'church_users');
    }

    /**
     * Get the roles for the church.
     */
    public function roles(): HasMany
    {
        return $this->hasMany(ChurchRole::class);
    }

    /**
     * Get the events for the church.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the users with roles for the church.
     */
    public function usersWithRoles()
    {
        return $this->hasManyThrough(
            ChurchUser::class,
            ChurchRole::class,
        );
    }

    /**
     * Get the church for the events.
     */
    public function diocese(): BelongsTo
    {
        return $this->belongsTo(Diocese::class);
    }

    /**
     * @return HasMany
     */
    public function translations(): HasMany
    {
        return $this->hasMany(ChurchTranslation::class);
    }

    /**
     * @return string|null
     */
    public function getNameAttribute(): string|null
    {
        $name = $this->translations()->where('language_code', app()->getLocale())->first(['name'])?->name;
        return !empty($name) ? $name : $this->attributes['name'];
    }

    /**
     * @return string|null
     */
    public function getShortDescriptionAttribute(): string|null
    {
        $shortDescription = $this->translations()->where('language_code', app()->getLocale())->first(['short_description'])?->short_description;
        return !empty($shortDescription) ? $shortDescription : $this->attributes['short_description'];
    }

    /**
     * @return string|null
     */
    public function getHistoryAttribute(): string|null
    {
        $history = $this->translations()->where('language_code', app()->getLocale())->first(['history'])?->history;
        return !empty($history) ? $history : $this->attributes['history'];
    }

    /**
     * @return string|null
     */
    public function getAddressViewAttribute(): string|null
    {
        $addressView = $this->translations()->where('language_code', app()->getLocale())->first(['address_view'])?->address_view;
        return !empty($addressView) ? $addressView : $this->attributes['address_view'];
    }

    /**
     * @return string|null
     */
    public function getCityAttribute(): string|null
    {
        $city = $this->translations()->where('language_code', app()->getLocale())->first(['city'])?->city;
        return !empty($city) ? $city : $this->attributes['city'];
    }

    /**
     * @return string|null
     */
    public function getStateAttribute(): string|null
    {
        $state = $this->translations()->where('language_code', app()->getLocale())->first(['state'])?->state;
        return !empty($state) ? $state : $this->attributes['state'];
    }

    /**
     * @return string|null
     */
    public function getCountryAttribute(): string|null
    {
        $country = $this->translations()->where('language_code', app()->getLocale())->first(['country'])?->country;
        return !empty($country) ? $country : $this->attributes['country'];
    }

    /**
     * @return string|null
     */
    public function getPlaceAdminNameAttribute(): string|null
    {
        $placeAdminName = $this->translations()->where('language_code', app()->getLocale())->first(['place_admin_name'])?->place_admin_name;
        return !empty($placeAdminName) ? $placeAdminName : $this->attributes['place_admin_name'];
    }

    /**
     * @return string|null
     */
    public function getCapitalAttribute(): string|null
    {
        $capital = $this->translations()->where('language_code', app()->getLocale())->first(['capital'])?->capital;
        return !empty($capital) ? $capital : $this->attributes['capital'];
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to search by church, diocese, or translation.
     *
     * @param EloquentBuilder $query
     * @param string $search
     * @return EloquentBuilder
     */
    public function scopeSearchByChurchOrDioceseOrTranslation(EloquentBuilder $query, string $search): EloquentBuilder
    {
        $locale = app()->getLocale();
        return $query->where(function ($q) use ($search, $locale) {
            // Search by translations with language code and name matching $search
            $q->orWhereHas('translations', function ($t) use ($search, $locale) {
                $t->where([
                    ['language_code', $locale],
                    ['name', 'LIKE', "%$search%"]
                ]);
            })
                // Search by diocese translations with language code and name matching $search
                ->orWhereHas('diocese.translations', function ($t) use ($search, $locale) {
                    $t->where([
                        ['language_code', $locale],
                        ['name', 'LIKE', "%$search%"]
                    ]);
                })
                // Search for records where diocese translations don't exist with the given language code
                ->orWhere(function ($innerQ) use ($search, $locale) {
                    $innerQ->whereNotExists(function ($ne) use ($locale) {
                        $ne->from('diocese_translations')
                            ->whereColumn('diocese_translations.diocese_id', 'churches.diocese_id')
                            ->where('diocese_translations.language_code', $locale);
                    })
                        ->whereHas('diocese', function ($t) use ($search) {
                            $t->where('name', 'LIKE', "%$search%");
                        });
                })
                // Search for records where church translations don't exist with the given language code
                ->orWhere(function ($innerQ) use ($search, $locale) {
                    $innerQ->whereNotExists(function ($ne) use ($locale) {
                        $ne->from('church_translations')
                            ->whereColumn('church_translations.church_id', 'churches.id')
                            ->where('church_translations.language_code', $locale);
                    })
                        ->where('name', 'LIKE', "%$search%"); // Match the church name with $search
                });
        });
    }


    /**
     * Get the regular procedure for the church.
     */
    public function regularProcedures(): HasMany
    {
        return $this->hasMany(ChurchRegularProcedure::class);
    }

    public function getRegularProceduresByKeyAttribute()
    {
        return $this->regularProcedures->keyBy('name')->toArray();
    }

    /**
     * Check if the current language is the default language.
     *
     * @return bool
     */
    public function isDefaultLanguage(): bool
    {
        return $this->default_language === (session('locale') ?? app()->getLocale());
    }
}
