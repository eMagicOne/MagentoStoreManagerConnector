<?php
/**
 *    This file is part of Bridge Connector.
 *
 *   Bridge Connector is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   Bridge Connector is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with Bridge Connector.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Emagicone\Bridgeconnector\Helper;

use Emagicone\Bridgeconnector\Helper\Constants;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Tools
{

    private static $object_manager;
    private static $resource;
    private static $logger;
    private static $config;
    private static $json_encoder;
    private static $json_decoder;
    private static $deployment_config;
    private static $cache_list;

    public static function getObjectManager()
    {
        if (!self::$object_manager) {
            self::$object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        }

        return self::$object_manager;
    }

    public static function getResource()
    {
        if (!self::$resource) {
            self::$resource = self::getObjectManager()->get('Magento\Framework\App\ResourceConnection');
        }

        return self::$resource;
    }

    public static function getLogger()
    {
        if (!self::$logger) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/emagicone_mobassistantconnector.log');
            self::$logger = new \Zend\Log\Logger();
            self::$logger->addWriter($writer);
        }

        return self::$logger;
    }

    public static function getDeploymentConfig()
    {
        if (!self::$deployment_config) {
            self::$deployment_config = self::getObjectManager()->create('Magento\Framework\App\DeploymentConfig');
        }

        return self::$deployment_config;
    }

    public static function getConfigValue($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        self::cleanCache('config');

        return self::getObjectManager()->create('Magento\Framework\App\Config\ScopeConfigInterface')
            ->getValue($path, $scope);
    }

    public static function getCacheList()
    {
        if (!self::$cache_list) {
            self::$cache_list = self::getObjectManager()->get('Magento\Framework\App\Cache\TypeListInterface');
        }

        return self::$cache_list;
    }

    public static function saveConfigValue($path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        if (!self::$config) {
            self::$config = self::getObjectManager()->create('Magento\Framework\App\Config\ConfigResource\ConfigInterface');
        }

        return self::$config->saveConfig($path, $value, $scope, 0);
    }

    public static function jsonEncode($data)
    {
        if (!self::$json_encoder) {
            self::$json_encoder = self::getObjectManager()->create('Magento\Framework\Json\EncoderInterface');
        }

        return self::$json_encoder->encode($data);
    }

    public static function jsonDecode($data)
    {
        if (!self::$json_decoder) {
            self::$json_decoder = self::getObjectManager()->create('Magento\Framework\Json\DecoderInterface');
        }

        return self::$json_decoder->decode($data);
    }

    public static function translate($message_id)
    {
        $message = new \Magento\Framework\Phrase($message_id);
        return $message->__toString();
    }

    public static function getDbTablePrefix()
    {
        return self::getDeploymentConfig()->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX);
    }

    public static function cleanCache($typeCode) {
        self::getCacheList()->cleanType($typeCode);
    }

    public static function getEncryptedData($data)
    {
        return call_user_func(
            'base64_encode',
            mcrypt_encrypt(MCRYPT_RIJNDAEL_128, Constants::CRYPT_KEY, $data, MCRYPT_MODE_ECB)
        );
    }

    public static function getDecryptedData($data)
    {
        return trim(
            mcrypt_decrypt(
                MCRYPT_RIJNDAEL_128,
                Constants::CRYPT_KEY,
                call_user_func('base64_decode', $data),
                MCRYPT_MODE_ECB
            )
        );
    }

    public static function getStoredSettings()
    {
        return unserialize(self::getConfigValue(Constants::CONFIG_PATH_SETTINGS));
    }

    public static function isLoginPasswordDefault()
    {
        $isDefault = false;
        $data = self::getStoredSettings();

        if (
            $data['login'] == Constants::DEFAULT_LOGIN
            && self::getDecryptedData($data['password']) == Constants::DEFAULT_PASSWORD
        ) {
            $isDefault = true;
        }

        return $isDefault;
    }

}