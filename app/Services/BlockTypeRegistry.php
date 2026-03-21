<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Registry for block types.
 * New block types register themselves here at boot — never a hardcoded enum.
 */
final class BlockTypeRegistry
{
    /** @var array<string, array{label: string, icon: string, crm: bool}> */
    private static array $types = [];

    public static function register(string $type, string $label, string $icon = '□', bool $crm = false): void
    {
        self::$types[$type] = ['label' => $label, 'icon' => $icon, 'crm' => $crm];
    }

    /** @return array<string, array{label: string, icon: string, crm: bool}> */
    public static function all(): array
    {
        return self::$types;
    }

    /** @return array<string, array{label: string, icon: string, crm: bool}> */
    public static function local(): array
    {
        return array_filter(self::$types, fn ($t) => !$t['crm']);
    }

    /** @return array<string, array{label: string, icon: string, crm: bool}> */
    public static function crm(): array
    {
        return array_filter(self::$types, fn ($t) => $t['crm']);
    }

    public static function has(string $type): bool
    {
        return isset(self::$types[$type]);
    }

    /** @return list<string> */
    public static function types(): array
    {
        return array_keys(self::$types);
    }
}
