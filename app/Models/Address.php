<?php

namespace App\Models;

use App\Support\Casts\Mobile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $guarded = [];

    protected $casts = [
        'receiver_mobile' => Mobile::class,
        'is_default'      => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function fullText(): string
    {
        return trim(collect([
            $this->province_name ?? $this->province?->name,
            $this->city_name ?? $this->city?->name,
            $this->line,
            $this->plaque ? 'پلاک '.$this->plaque : null,
            $this->unit ? 'واحد '.$this->unit : null,
        ])->filter()->implode('، '));
    }

    public function snapshot(): array
    {
        return [
            'receiver_name'   => $this->receiver_name,
            'receiver_mobile' => $this->receiver_mobile,
            'province_id'     => $this->province_id,
            'province'        => $this->province_name ?? $this->province?->name,
            'city'            => $this->city_name ?? $this->city?->name,
            'postal_code'     => $this->postal_code,
            'text'            => $this->fullText(),
        ];
    }
}
