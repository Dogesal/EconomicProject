<?php

namespace App\Application\WhatsApp;

final class ApplyResult
{
    public function __construct(
        public readonly int $applied = 0,
        public readonly int $failed = 0,
        public readonly bool $needsAccountSetup = false,
    ) {}

    public static function empty(): self
    {
        return new self;
    }

    public function hasChanges(): bool
    {
        return $this->applied > 0 || $this->failed > 0;
    }
}
