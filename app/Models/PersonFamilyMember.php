<?php

namespace App\Models;

use App\Domain\Enums\FamilyRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonFamilyMember extends Model
{
    protected $table = 'person_family_members';

    protected $fillable = [
        'person_id',
        'full_name',
        'relation',
        'national_id',
        'birth_date',
        'mobile',
    ];

    protected $casts = [
            'relation' => FamilyRelation::class,
            'birth_date' => 'date',
        ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
