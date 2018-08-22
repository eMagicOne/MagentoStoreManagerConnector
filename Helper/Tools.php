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

namespace Emagicone\Bridgeconnector\Helper;

use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Tools
 * @package Emagicone\Bridgeconnector\Helper
 */
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
    private static $file;
    private static $escaper;
    private static $mcrypt;
    private static $mcrypt_block_cipher;

    private static function getBase64($value, $alreadyEncoded)
    {
        return self::getObjectManager()->create(
            'Emagicone\Bridgeconnector\Helper\Base64Helper',
            ['value' => $value, 'alreadyEncoded' => $alreadyEncoded]
        );
    }

    public static function base64Encode($data)
    {
        return self::getBase64($data, false)->getValueEncoded();
    }

    public static function base64Decode($data)
    {
        return self::getBase64($data, true)->getValue();
    }

    private static function getMcryptBlockCipher()
    {
        error_reporting(E_ALL ^ E_DEPRECATED);
        if (!self::$mcrypt_block_cipher) {
            self::$mcrypt_block_cipher = \Zend\Crypt\BlockCipher::factory('mcrypt');
            self::$mcrypt_block_cipher->setKey(Constants::CRYPT_KEY);
            self::$mcrypt_block_cipher->setSalt(Constants::CRYPT_IV);
        }

        return self::$mcrypt_block_cipher;
    }

    public static function getEncryptedData($data)
    {
        if (version_compare(phpversion(), '7.1', '<')) {
            return self::getMcryptBlockCipher()->encrypt($data);
        }

        return self::getObjectManager()->get(\Magento\Framework\Encryption\EncryptorInterface::class)->encrypt($data);
    }

    public static function getDecryptedData($data)
    {
        if (version_compare(phpversion(), '7.1', '<')) {
            return self::getMcryptBlockCipher()->decrypt($data);
        }

        return self::getObjectManager()->get(\Magento\Framework\Encryption\EncryptorInterface::class)->decrypt($data);
    }

    public static function isLoginPasswordDefault()
    {
        $data = self::getStoredSettings();

        return Constants::DEFAULT_LOGIN == $data['login']
            && Constants::DEFAULT_PASSWORD == self::getDecryptedData($data['password']);
    }

    public static function isPasswordEncryptedUsingBlockCipher($password = false)
    {
        if (!$password) {
            $data = self::getStoredSettings();
            $password = $data['password'];
            return self::getDecryptedData($password) !== $password;
        }

        return self::getDecryptedData($password) !== false;
    }

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
            $writer = self::getObjectManager()->create(
                'Zend\Log\Writer\Stream',
                ['streamOrUrl' => BP . '/var/log/emagicone_mobassistantconnector.log']
            );
            self::$logger = self::getObjectManager()->create('Zend\Log\Logger');
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

    public static function getFile()
    {
        if (!self::$file) {
            self::$file = self::getObjectManager()->get('Magento\Framework\Filesystem\Driver\File');
        }

        return self::$file;
    }

    public static function saveConfigValue($path, $value, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        if (!self::$config) {
            self::$config = self::getObjectManager()
                ->create('Magento\Framework\App\Config\ConfigResource\ConfigInterface');
        }

        return self::$config->saveConfig($path, $value, $scope, 0);
    }

    public static function deleteConfigValue($path, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
    {
        if (!self::$config) {
            self::$config = self::getObjectManager()
                ->create('Magento\Framework\App\Config\ConfigResource\ConfigInterface');
        }

        return self::$config->deleteConfig($path, $scope, 0);
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
        return self::getObjectManager()->create('Magento\Framework\Phrase', ['text' => $message_id])->__toString();
    }

    public static function unserialize($data)
    {
        return self::getObjectManager()->get('Magento\Framework\Unserialize\Unserialize')->unserialize($data);
    }

    public static function getDbTablePrefix()
    {
        return self::getDeploymentConfig()->get(ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX);
    }

    public static function cleanCache($typeCode)
    {
        self::getCacheList()->cleanType($typeCode);
    }

    public static function getStoredSettings()
    {
        return self::unserialize(self::getConfigValue(Constants::CONFIG_PATH_SETTINGS));
    }

    public static function getUploader($filename)
    {
        return Tools::getObjectManager()->create('Magento\MediaStorage\Model\File\Uploader', ['fileId' => $filename]);
    }

    public static function getEscaper()
    {
        if (!self::$escaper) {
            self::$escaper = self::getObjectManager()->create('Magento\Framework\Escaper');
        }

        return self::$escaper;
    }
}
