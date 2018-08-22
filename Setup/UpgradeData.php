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

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Emagicone\Bridgeconnector\Helper\Tools;
use Emagicone\Bridgeconnector\Helper\Constants;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.4') < 0) {
            $previousBridgeData = $this->getBridgeOptions($setup);
            Tools::saveConfigValue(Constants::CONFIG_PATH_SETTINGS, $previousBridgeData);

            // To save in cache
            Tools::getConfigValue(Constants::CONFIG_PATH_SETTINGS);
        }
    }

    private function getBridgeOptions($setup)
    {
        $options = Tools::getConfigValue(Constants::OPTIONS_NAME);

        if (!$options) {
            return false;
        }

        $tmpDir = Tools::getObjectManager()->get('Magento\Framework\Module\Dir\Reader')
                ->getModuleDir('', 'Emagicone_Bridgeconnector') . '/tmp';
        $tmpDir = str_replace(str_replace('\\', '/', BP), '', $tmpDir);

        $bridgeOptions = Tools::unserialize($options);
        $tables = explode(';', Constants::EXCLUDE_DB_TABLES_DEFAULT);
        $count = count($tables);

        for ($i = 0; $i < $count; $i++) {
            $tables[$i] = $setup->getTable($tables[$i]);
        }

        $bridgeOptions['login'] = Constants::DEFAULT_LOGIN;
        $bridgeOptions['password'] = Tools::getEncryptedData(Constants::DEFAULT_PASSWORD);
        $bridgeOptions['bridge_hash'] = isset($bridgeOptions['bridge_hash'])
            ? $bridgeOptions['bridge_hash']
            : md5(Constants::DEFAULT_LOGIN . Constants::DEFAULT_PASSWORD);
        $bridgeOptions['tmp_dir'] = isset($bridgeOptions['tmp_dir'])
            ? $bridgeOptions['tmp_dir']
            : $tmpDir;
        $bridgeOptions['allowed_ips'] = isset($bridgeOptions['allowed_ips'])
            ? $bridgeOptions['allowed_ips']
            : Constants::DEFAULT_ALLOWED_IPS;
        $bridgeOptions['exclude_db_tables'] = isset($bridgeOptions['exclude_db_tables'])
            ? $bridgeOptions['exclude_db_tables']
            : implode(';', $tables);
        $bridgeOptions['notification_email'] = isset($bridgeOptions['notification_email'])
            ? $bridgeOptions['notification_email']
            : '';
        $bridgeOptions['allow_compression'] = isset($bridgeOptions['allow_compression'])
            ? (int)$bridgeOptions['allow_compression']
            : Constants::DEFAULT_ALLOW_COMPRESSION;
        $bridgeOptions['last_clear_date'] = isset($bridgeOptions['last_clear_date'])
            ? $bridgeOptions['last_clear_date']
            : time();

        if (!isset($bridgeOptions['limit_query_size'])) {
            $bridgeOptions['limit_query_size'] = Constants::DEFAULT_LIMIT_QUERY_SIZE;
        } elseif ((int)$bridgeOptions['limit_query_size'] < Constants::MIN_LIMIT_QUERY_SIZE) {
            $bridgeOptions['limit_query_size'] = Constants::MIN_LIMIT_QUERY_SIZE;
        } elseif ((int)$bridgeOptions['limit_query_size'] > Constants::MAX_LIMIT_QUERY_SIZE) {
            $bridgeOptions['limit_query_size'] = Constants::MAX_LIMIT_QUERY_SIZE;
        } else {
            $bridgeOptions['limit_query_size'] = (int)$bridgeOptions['limit_query_size'];
        }

        if (!isset($bridgeOptions['package_size'])) {
            $bridgeOptions['package_size'] = Constants::DEFAULT_PACKAGE_SIZE * 1024; // B
        } elseif ((int)$bridgeOptions['package_size'] < Constants::MIN_PACKAGE_SIZE) {
            $bridgeOptions['package_size'] = Constants::MIN_PACKAGE_SIZE * 1024;
        } elseif ((int)$bridgeOptions['package_size'] > Constants::MAX_PACKAGE_SIZE) {
            $bridgeOptions['package_size'] = Constants::MAX_PACKAGE_SIZE * 1024;
        } else {
            $bridgeOptions['package_size'] = (int)$bridgeOptions['package_size'];
        }

        // Values of $compress_level between 1 and 9 will trade off speed and efficiency, and the default is 6.
        // The 1 flag means "fast but less efficient" compression, and 9 means "slow but most efficient" compression.
        if (!isset($bridgeOptions['compress_level'])) {
            $bridgeOptions['compress_level'] = Constants::DEFAULT_COMPRESS_LEVEL;
        } elseif ((int)$bridgeOptions['compress_level'] < Constants::MIN_COMPRESS_LEVEL) {
            $bridgeOptions['compress_level'] = Constants::MIN_COMPRESS_LEVEL;
        } elseif ((int)$bridgeOptions['compress_level'] > Constants::MAX_COMPRESS_LEVEL) {
            $bridgeOptions['compress_level'] = Constants::MAX_COMPRESS_LEVEL;
        } else {
            $bridgeOptions['compress_level'] = (int)$bridgeOptions['compress_level'];
        }

        return serialize($bridgeOptions);
    }
}
