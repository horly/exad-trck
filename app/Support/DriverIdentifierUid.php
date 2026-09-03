<?php

namespace App\Support;

final class DriverIdentifierUid
{
    /**
     * @return list<string>
     */
    public static function candidates(mixed $value): array
    {
        $normalized = self::normalize($value);

        if ($normalized === null) {
            return [];
        }

        $candidates = [$normalized];

        if (preg_match('/^[0-9A-F]{16}$/', $normalized) === 1) {
            $reversed = implode('', array_reverse(str_split($normalized, 2)));

            if ($reversed !== $normalized) {
                $candidates[] = $reversed;
            }
        }

        return $candidates;
    }

    public static function normalize(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalized = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $value));

        if ($normalized === '' || preg_match('/^0+$/', $normalized) === 1) {
            return null;
        }

        return $normalized;
    }
}
