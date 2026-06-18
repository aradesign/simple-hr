<?php

namespace App\Domain\Enums;

enum FamilyRelation: string
{
    case Spouse = 'spouse';
    case Child = 'child';
    case Parent = 'parent';
    case Sibling = 'sibling';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spouse => 'همسر',
            self::Child => 'فرزند',
            self::Parent => 'والد',
            self::Sibling => 'خواهر/برادر',
            self::Other => 'سایر',
        };
    }
}
