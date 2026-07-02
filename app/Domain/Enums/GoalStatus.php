<?php

namespace App\Domain\Enums;

enum GoalStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Completed => 'Completada',
            self::Archived => 'Archivada',
        };
    }
}
