<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Registry for block types.
 * New block types register themselves here at boot — never a hardcoded enum.
 */
final class BlockTypeRegistry
{
    /** @var array<string, array{label: string, icon: string}> */
    private static array $types = [];

    public static function register(string $type, string $label, string $icon = '□'): void
    {
        static::$types[$type] = ['label' => $label, 'icon' => $icon];
    }

    /** @return array<string, array{label: string, icon: string}> */
    public static function all(): array
    {
        return static::$types;
    }

    public static function has(string $type): bool
    {
        return isset(static::$types[$type]);
    }

    /** @return list<string> */
    public static function types(): array
    {
        return array_keys(static::$types);
    }
}
