<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivacySetting extends Model
{
    protected $fillable = [
        'privacy_policy_content',
        'privacy_policy_version',
        'purpose_limitation',
        'data_retention_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'purpose_limitation' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public static function getActive(): self
    {
        return static::firstOrCreate(['is_active' => true]);
    }
}
