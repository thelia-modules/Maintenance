<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Maintenance\Service;

/**
 * The access lists typed in the module configuration: one entry per line.
 */
final class AccessList
{
    /**
     * @return list<string> the non-empty lines, trimmed
     */
    public static function lines(string $text): array
    {
        return array_values(array_filter(
            array_map(trim(...), preg_split('/\R/', $text) ?: []),
            static fn (string $line): bool => '' !== $line,
        ));
    }

    /**
     * @return list<string> the lines that are not a path: a path starts with "/", and "/" alone would reopen the shop
     */
    public static function invalidPaths(string $text): array
    {
        return array_values(array_filter(
            self::lines($text),
            static fn (string $path): bool => !str_starts_with($path, '/') || '' === rtrim($path, '/') || 1 === preg_match('/\s/', $path),
        ));
    }

    /**
     * @return list<string> the lines that are neither an IPv4/IPv6 address nor a CIDR range
     */
    public static function invalidIps(string $text): array
    {
        return array_values(array_filter(
            self::lines($text),
            static fn (string $ip): bool => !self::isIpOrRange($ip),
        ));
    }

    private static function isIpOrRange(string $value): bool
    {
        [$address, $mask] = array_pad(explode('/', $value, 2), 2, null);

        if (false === filter_var($address, \FILTER_VALIDATE_IP)) {
            return false;
        }

        if (null === $mask) {
            return true;
        }

        $maxMask = false !== filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) ? 32 : 128;

        return 1 === preg_match('/^\d{1,3}$/', $mask) && (int) $mask <= $maxMask;
    }
}
