<?php

namespace App\Models;

use Illuminate\Support\Collection;

class Customer extends DocumentModel
{
    protected $table = 'customers';

    protected $fillable = [
        'business_profile_id',
        'business_profile_ids',
        'customer_name',
        'gstin',
        'state',
        'state_code',
        'address',
        'phone',
        'email',
        'customer_type',
        'is_interstate',
    ];

    protected $casts = [
        'business_profile_ids' => 'array',
        'is_interstate' => 'boolean',
    ];

    public function businessProfile()
    {
        return $this->belongsTo(BusinessProfile::class);
    }

    public function relatedBusinessProfileIds(): array
    {
        return collect($this->business_profile_ids ?? [])
            ->push($this->business_profile_id)
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function isRelatedToProfile(?string $profileId): bool
    {
        if (! $profileId) {
            return false;
        }

        return in_array((string) $profileId, $this->relatedBusinessProfileIds(), true);
    }

    public function relatedBusinessProfiles(?Collection $profiles = null): Collection
    {
        $profileIds = $this->relatedBusinessProfileIds();

        if ($profiles) {
            return $profiles
                ->filter(fn (BusinessProfile $profile): bool => in_array((string) $profile->id, $profileIds, true))
                ->values();
        }

        return empty($profileIds)
            ? collect()
            : BusinessProfile::query()->whereIn('id', $profileIds)->get()->values();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
