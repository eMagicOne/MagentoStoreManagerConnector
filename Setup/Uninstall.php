<?php
/**
 *    This file is part of Magento Store Manager Connector.
 *
 *   Magento Store Manager Connector is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Magento Store Manager Connector is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Magento Store Manager Connector.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Emagicone\Bridgeconnector\Setup;

use Emagicone\Bridgeconnector\Helper\Tools;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Emagicone\Bridgeconnector\Helper\Constants;

/**
 * Class Install
 *
 * @package Emagicone\Bridgeconnector\Uninstall
 */
class Uninstall implements UninstallInterface
{
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $setupConnection = $setup->getConnection();

        Tools::deleteConfigValue(Constants::CONFIG_PATH_SETTINGS);
		
		$table = $setup->getTable(Constants::TABLE_SESSION_KEYS);
        if ($setupConnection->isTableExists($table)) {
            $setupConnection->dropTable($table);
        }

		$table = $setup->getTable(Constants::TABLE_FAILED_LOGIN);
        if ($setupConnection->isTableExists($table)) {
            $setupConnection->dropTable($table);
        }

        $setup->endSetup();
    }
}
