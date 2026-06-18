<?php

namespace App\Domain\Enums;

enum AssignmentRole: string
{
    case Assignee = 'assignee';
    case Reviewer = 'reviewer';
    case Collaborator = 'collaborator';

    public function label(): string
    {
        return match ($this) {
            self::Assignee => 'مسئول',
            self::Reviewer => 'بازبین',
            self::Collaborator => 'همکار',
        };
    }
}
