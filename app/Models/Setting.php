<?php

namespace App\Models;

use App\Domain\Enums\SettingGroup;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];

    protected $casts = [
            'group' => SettingGroup::class,
        ];
}
