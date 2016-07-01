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

use Emagicone\Bridgeconnector\Helper\BridgeConnectorCore;
use Magento\Framework\Config\ConfigOptionsListConstants;

class MagentoOverrider extends BridgeConnectorCore
{

    public function __construct($module_name, $options_name)
    {
        $this->module_name  = $module_name;
        $this->options_name = $options_name;
    }

    private function getImagePath($entity_type, $image_id)
    {
        /** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
        $mediaDirectory = Tools::getObjectManager()->get('Magento\Framework\Filesystem')
            ->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);

        return $mediaDirectory->getAbsolutePath($this->getImageDir($entity_type)) . $image_id;
    }

    public function isModuleEnabled()
    {
        return Tools::getObjectManager()->get('Magento\Framework\Module\Manager')->isOutputEnabled($this->module_name);
    }

    public function getBridgeOptions()
    {
        $options = Tools::getConfigValue($this->options_name);

        if ($options) {
            $options = unserialize($options);

            return $options;
        }

        return false;
    }

    public function getShopRootDir()
    {
        return BP;
    }

    public function getDbHost()
    {
        $connection = Tools::getDeploymentConfig()->get(ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTION_DEFAULT);

        return $connection['host'];
    }

    public function getDbName()
    {
        $connection = Tools::getDeploymentConfig()->get(ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTION_DEFAULT);

        return $connection['dbname'];
    }

    public function getDbUsername()
    {
        $connection = Tools::getDeploymentConfig()->get(ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTION_DEFAULT);

        return $connection['username'];
    }

    public function getDbPassword()
    {
        $connection = Tools::getDeploymentConfig()->get(ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTION_DEFAULT);

        return $connection['password'];
    }

    public function getDbPrefix()
    {
        return Tools::getDbTablePrefix();
    }

    public function getSqlResults($sql, $type = self::ASSOC)
    {
        $ret = array();

        try {
            $result = Tools::getResource()->getConnection()->fetchAll($sql);

            if ($type == self::ASSOC) {
                return $result;
            }

            foreach ($result as $arr_values) {
                $ret[] = array_values($arr_values);
            }

        } catch (\Exception $e) {
            $error = $e->getPrevious();
            $this->error_no = $error->errorInfo[1];
            $this->error_msg = $error->errorInfo[2];

            return false;
        }

        return $ret;
    }

    public function execSql($sql, $reconnect = false)
    {
        $result = true;
//        $db = Db::getInstance();

        if ($reconnect) {
//            $db->connect();
        }

        try {
            /*if (!$db->execute($sql)) {
                throw new Exception('Error');
            }*/
            Tools::getResource()->getConnection()->rawQuery($sql);
        } catch (\Exception $e) {
            $this->error_no = $e->errorInfo[1];
            $this->error_msg = $e->errorInfo[2];
            $result = false;
        }

        return $result;
    }

    public function sanitizeSql($sql)
    {
        $data = Tools::getResource()->getConnection()->quote($sql);
        return substr($data, 1, $this->strLen($data) - 2);
    }

    public function issetRequestParam($param)
    {
        $result = false;

        if (!is_null(Tools::getObjectManager()->get('\Magento\Framework\App\Action\Action')->getRequest()->getParam($param))) {
            $result = true;
        }

        return $result;
    }

    public function getRequestParam($param)
    {
        return Tools::getObjectManager()->get('\Magento\Framework\App\Action\Action')->getRequest()->getParam($param);
    }

    public function getStoreLink($ssl = false)
    {
        /*if ($ssl) {
            return _PS_BASE_URL_SSL_.__PS_BASE_URI__;
        }

        return _PS_BASE_URL_.__PS_BASE_URI__;*/
        return false;
    }

    public function runIndexer()
    {
        $indexers = Tools::getObjectManager()->get('Magento\Indexer\Model\Indexer\Collection')->getItems();

        foreach ($indexers as $indexer) {
            $indexer->reindexAll();
            echo $indexer->getTitle()->getText() . ' index was rebuilt successfully<br>';
        }
    }

    public function getCartVersion()
    {
        return Tools::jsonEncode(
            [
                'cart_version' => Tools::getObjectManager()->get('Magento\Framework\App\ProductMetadataInterface')
                    ->getVersion(),
                'crypt_key' => Tools::getDeploymentConfig()->get(ConfigOptionsListConstants::CONFIG_PATH_CRYPT_KEY)
            ]
        );
    }

    public function getImage($entity_type, $image_id)
    {
        return $this->getImagePath($entity_type, $image_id);
    }

    public function setImage($entity_type, $image_id, $img, $type)
    {
        $result = false;
        $img_file = $this->getImage($entity_type, $image_id);
        $dirpath = dirname($img_file);

        if (!file_exists($dirpath)) {
            mkdir($dirpath, 0777, true);
        }

        if (file_exists($dirpath)) {
            if ($type == self::IMAGE_URL) {
                $result = file_put_contents($img_file, $this->fileGetContents($img));
            } else {
                $result = move_uploaded_file($_FILES[$img]['tmp_name'], $img_file);

    //            try {
    //                $uploader = Tools::getObjectManager()->create(
    //                    'Magento\MediaStorage\Model\File\Uploader',
    //                    ['fileId' => $img]
    //                );
    //                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);

    //                /** @var \Magento\Framework\Image\Adapter\AdapterInterface $imageAdapter */
    //                $imageAdapter = Tools::getObjectManager()->get('Magento\Framework\Image\AdapterFactory')->create();
    //                $uploader->addValidateCallback('catalog_product_image', $imageAdapter, 'validateUploadFile');
    //                $uploader->setAllowRenameFiles(true);
    //                $uploader->setFilesDispersion(true);

    //                /** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
    //                $mediaDirectory = Tools::getObjectManager()->get('Magento\Framework\Filesystem')
    //                    ->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);

    //                $result = $uploader->save($mediaDirectory->getAbsolutePath($this->getImageDir($entity_type)));
    //            } catch (\Exception $e) {
    //                die($this->jsonEncode(array(
    //                    self::CODE_RESPONSE => self::ERROR_CODE_COMMON,
    //                    self::KEY_MESSAGE   => 'File was not uploaded. '.$e->getMessage(),
    //                )));
    //            }
            }
        }

        if ($result) {
            die($this->jsonEncode(array(
                self::CODE_RESPONSE => self::SUCCESSFUL,
                self::KEY_MESSAGE   => 'File was successfully uploaded',
            )));
        } else {
            die($this->jsonEncode(array(
                self::CODE_RESPONSE => self::ERROR_CODE_COMMON,
                self::KEY_MESSAGE   => 'File was not uploaded',
            )));
        }
    }

    public function deleteImage($entity_type, $image_id)
    {
        $this->deleteFile($this->getImagePath($entity_type, $image_id));
    }

    public function deleteFile($filepath)
    {
        if (file_exists($filepath) && unlink($filepath)) {
            die($this->jsonEncode(array(
                self::CODE_RESPONSE => self::SUCCESSFUL,
                self::KEY_MESSAGE   => 'File was deleted from server successfully',
            )));
        }

        die($this->jsonEncode(array(
            self::CODE_RESPONSE => self::ERROR_CODE_COMMON,
            self::KEY_MESSAGE   => 'File was not deleted from server',
        )));
    }

    public function copyImage($entity_type, $from_image_id, $to_image_id)
    {
        return false;
    }

    public function getFile($folder, $filename)
    {
        return $this->getShopRootDir().'/'.$folder.'/'.$filename;
    }

    public function setFile($folder, $filename, $file)
    {
        $destination_directory = $this->getShopRootDir() . "/$folder";
        $result = mkdir($destination_directory, 0777, true);

        if ($result) {
            $destination_path = "$destination_directory/$filename";
            $result = move_uploaded_file($_FILES[$file]['tmp_name'], $destination_path);
        }

        if ($result) {
            die($this->jsonEncode(array(
                self::CODE_RESPONSE => self::SUCCESSFUL,
                self::KEY_MESSAGE   => 'File was successfully uploaded',
            )));
        } else {
            die($this->jsonEncode(array(
                self::CODE_RESPONSE => self::ERROR_CODE_COMMON,
                self::KEY_MESSAGE   => 'File was not uploaded',
            )));
        }
    }

    public function getImageDir($type)
    {
        $path = '';

        switch ($type) {
            case self::PRODUCT:
                /** @var \Magento\Catalog\Model\Product\Media\Config $config */
                $config = Tools::getObjectManager()->get('Magento\Catalog\Model\Product\Media\Config');
                $path = $config->getBaseMediaPath();
                break;
            case self::CATEGORY:
                $path = 'catalog/category/';
                break;
            case self::ATTRIBUTE:
                /** @var \Magento\Swatches\Helper\Media $swatches_media */
                $swatches_media = Tools::getObjectManager()->get('Magento\Swatches\Helper\Media');
                $path = $swatches_media::SWATCH_MEDIA_PATH;
                break;
        }

        return $path;
    }

    public function checkDataChanges($tables_arr = [])
    {
        $arr_result = [];
        $count = count($tables_arr);

        for ($i = 0; $i < $count; $i++) {
            $table = trim($tables_arr[$i]);

            if (empty($table)) {
                continue;
            }

            $sql = "SELECT `AUTO_INCREMENT` AS 'auto_increment'
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '" . $this->getDbName() . "' AND TABLE_NAME = '" . $table . "'";
            $result = $this->getSqlResults($sql);

            if ($result && isset($result[0]['auto_increment'])) {
                $arr_result[$table] = (int)$result[0]['auto_increment'] - 1;
            } else {
                $sql = "SELECT
                        `COLUMN_NAME` AS 'primary_key' INTO @primary_key_field
                    FROM
                        `information_schema`.`COLUMNS`
                    WHERE
                        (`TABLE_SCHEMA` = '" . $this->getDbName() . "')
                            AND (`TABLE_NAME` = '" . $table . "')
                            AND (`COLUMN_KEY` = 'PRI')";
                $this->execSql($sql);

                $sql = "SET @s = CONCAT('SELECT MAX(', @primary_key_field, ') AS max_id FROM " . $table . "')";
                $this->execSql($sql);

                $sql = "PREPARE stmt FROM @s";
                $this->execSql($sql);

                $sql = "EXECUTE stmt;";
                $result = $this->getSqlResults($sql);

                if ($result && isset($result[0]['max_id'])) {
                    $arr_result[$table] = (int)$result[0]['max_id'];
                }
            }
        }

        if (empty($arr_result)) {
            return $this->jsonEncode(
                [
                    self::CODE_RESPONSE => self::ERROR_CODE_COMMON,
                    self::KEY_MESSAGE   => Tools::jsonEncode($arr_result)
                ]
            );
        }

//        return '1|' . base64_encode(Tools::jsonEncode($arr_result));
        return $this->jsonEncode(
            [
                self::CODE_RESPONSE => self::SUCCESSFUL,
                self::KEY_MESSAGE    => Tools::jsonEncode($arr_result)
            ]
        );
    }

    public function getNewOrders($order_id = -1)
    {
        if ($order_id < 1) {
            return false;
        }

        $count_new_orders = 0;
        $max_order_id = 0;
        $order_info = [];

        // Select new orders count
        $sql = 'SELECT COUNT(entity_id) AS CountNewOrder FROM ' . $this->getDbPrefix()
            . 'sales_order WHERE entity_id > ' . $order_id;
        $result = $this->getSqlResults($sql);
        if ($result && isset($result[0]['CountNewOrder'])) {
            $count_new_orders = (int)$result[0]['CountNewOrder'];
        }

        // Select maximum order id
        $sql = 'SELECT MAX(entity_id) AS MaxOrderId FROM ' . $this->getDbPrefix() . 'sales_order';
        $result = $this->getSqlResults($sql);
        if ($result && isset($result[0]['MaxOrderId'])) {
            $max_order_id = (int)$result[0]['MaxOrderId'];
        }

        // Select new orders
        $sql = 'SELECT
                o.`entity_id` AS order_id,
                o.`customer_id`,
                o.`grand_total`,
                o.`total_paid`,
                o.`order_currency_code`,
                c.firstname,
                c.lastname
            FROM `' . $this->getDbPrefix() . 'sales_order` AS o
                LEFT JOIN `' . $this->getDbPrefix() . "customer_entity` AS c ON c.`entity_id` = o.`customer_id`
            WHERE o.`entity_id` > $order_id";
        $result = $this->getSqlResults($sql);

        if ($result) {
            $order_info = $result;
        }

        /*return '1|' . base64_encode(Tools::jsonEncode([
            'CountNewOrder' => $count_new_orders,
            'MaxOrderId' => $max_order_id,
            'OrderInfo' => $order_info
        ]));*/

        return $this->jsonEncode(
            [
                self::CODE_RESPONSE => self::SUCCESSFUL,
                self::KEY_MESSAGE   => Tools::jsonEncode(
                    [
                        'CountNewOrder' => $count_new_orders,
                        'MaxOrderId'    => $max_order_id,
                        'OrderInfo'     => $order_info
                    ]
                )
            ]
        );
    }

    public function getCache()
    {
        $caches = [];

        foreach (Tools::getCacheList()->getTypes() as $cache) {
            $caches[] = $cache->toArray();
        }

        if (empty($caches)) {
            return $this->jsonEncode(
                [
                    self::CODE_RESPONSE => self::ERROR_CODE_COMMON,
                    self::KEY_MESSAGE   => 'Cannot get cache types'
                ]
            );
        }

        return $this->jsonEncode(
            [
                self::CODE_RESPONSE => self::SUCCESSFUL,
                self::KEY_MESSAGE   => Tools::jsonEncode($caches)
            ]
        );
    }

    public function clearCache()
    {
        $cache_type = $this->getRequestParam('cache_type');

        if (!$cache_type) {
            die($this->jsonEncode(
                [
                    self::CODE_RESPONSE => self::ERROR_CODE_COMMON,
                    self::KEY_MESSAGE   => 'Incorrect cache type'
                ]
            ));
        }

        $cache_types = explode(';', $cache_type);
        $count = count($cache_types);

        for ($i = 0; $i < $count; $i++) {
            Tools::cleanCache($cache_types[$i]);
            echo "$cache_types[$i] refreshed";
        }
    }

    /*public function getCurlLink($ssl = false)
    {
        if ($ssl)
            return $this->getStoreLink(true).'/index.php?fc=module&module=bridgeconnector&controller=bridge&';

        return $this->getStoreLink().'/index.php?fc=module&module=bridgeconnector&controller=bridge&';
    }*/

    /*public function getUrlForPhpinfo()
    {
        return $this->getStoreLink().'/index.php?fc=module&module=bridgeconnector&controller=bridge&phpinfo';
    }*/

    public function strLen($str)
    {
        return strlen($str);
    }

    public function subStr($str, $start, $length = false)
    {
        if ($length) {
            return substr($str, $start, $length);
        }

        return substr($str, $start);
    }

    public function strToLower($str)
    {
        return strtolower($str);
    }

    public function strToUpper($str)
    {
        return strtoupper($str);
    }

    public function jsonEncode($arr)
    {
        return Tools::jsonEncode($arr);
    }

    public function stripSlashes($str)
    {
        return stripslashes($str);
    }

    public function fileGetContents($file)
    {
        return file_get_contents($file);
    }

    public function pSQL($data)
    {
        return $data;
    }

    public function saveConfigData($data)
    {
        Tools::saveConfigValue($this->options_name, serialize($data));
    }

    /*public function getRecursiveIteratorIterator($directory)
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
    }*/

}
