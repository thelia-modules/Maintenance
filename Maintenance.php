<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace Maintenance;

use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Thelia\Log\Tlog;
use Thelia\Module\BaseModule;

class Maintenance extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'maintenance';

    const MAINTENANCE_FILE = THELIA_WEB_DIR.'maintenance.html';

    /** Module configuration keys. */
    const CONFIG_ENABLED = 'maintenance_enabled';
    const CONFIG_ALLOWED_PATHS = 'allowed_paths';
    const CONFIG_ALLOWED_IPS = 'allowed_ips';

    /** Opening line of the guard that versions before 3.1 wrote into the front controller. */
    const LEGACY_INDEX_GUARD = '//⚠--MAINTENANCE';

    public function postActivation(?ConnectionInterface $con = null): void
    {
        if (!file_exists(self::MAINTENANCE_FILE)) {
            copy(self::templateFile(), self::MAINTENANCE_FILE);
        }
    }

    public function update($currentVersion, $newVersion, ?ConnectionInterface $con = null): void
    {
        // Up to 3.0, maintenance was a guard written into the front controller. 3.1 never touches that file:
        // a guard left there keeps the site closed whatever the switch says, and has to be removed by hand.
        if (version_compare((string) $currentVersion, '3.1.0', '<') && self::hasLegacyIndexGuard()) {
            Tlog::getInstance()->warning(sprintf(
                'Maintenance %s: a maintenance guard written by a previous version is still in %s. It was left as is; remove it by hand, maintenance is now switched from the module configuration.',
                $newVersion,
                THELIA_WEB_DIR.'index.php'
            ));
        }
    }

    public static function isEnabled(): bool
    {
        return '1' === self::getConfigValue(self::CONFIG_ENABLED, '0');
    }

    public static function hasLegacyIndexGuard(): bool
    {
        $index = THELIA_WEB_DIR.'index.php';

        return is_file($index) && str_contains((string) file_get_contents($index), self::LEGACY_INDEX_GUARD);
    }

    public static function templateFile(): string
    {
        return __DIR__.DS.'templates'.DS.'maintenance.html';
    }

    /**
     * @return SplFileInfo
     */
    public static function getMaintenanceFile(): SplFileInfo
    {
        if (!file_exists(self::MAINTENANCE_FILE)) {
            $source = self::templateFile();
            if (!copy($source, self::MAINTENANCE_FILE)) {
                throw new \RuntimeException(sprintf('Unable to copy maintenance template from "%s" to "%s". Check directory permissions.', $source, self::MAINTENANCE_FILE));
            }
        }

        $finder = new Finder();
        $finder->files()->depth('== 0')->in(THELIA_WEB_DIR)->name("maintenance.html");

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            return $file;
        }

        throw new \RuntimeException(sprintf('Unable to locate or create the maintenance file at "%s".', self::MAINTENANCE_FILE));
    }

    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([__DIR__.'/I18n/*', __DIR__.'/Tests/*'])
            ->autowire(true)
            ->autoconfigure(true);
    }
}
