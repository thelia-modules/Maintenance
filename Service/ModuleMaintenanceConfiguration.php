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

use Maintenance\Maintenance;

/**
 * Reads the switch and the access lists from the module configuration, the page from the web directory.
 */
final readonly class ModuleMaintenanceConfiguration implements MaintenanceConfiguration
{
    public function isEnabled(): bool
    {
        return Maintenance::isEnabled();
    }

    public function allowedPaths(): array
    {
        return AccessList::lines((string) Maintenance::getConfigValue(Maintenance::CONFIG_ALLOWED_PATHS, ''));
    }

    public function allowedIps(): array
    {
        return AccessList::lines((string) Maintenance::getConfigValue(Maintenance::CONFIG_ALLOWED_IPS, ''));
    }

    public function page(): string
    {
        try {
            return Maintenance::getMaintenanceFile()->getContents();
        } catch (\RuntimeException) {
            // The web directory is not writable: the page shipped with the module still closes the shop.
            return (string) file_get_contents(Maintenance::templateFile());
        }
    }
}
