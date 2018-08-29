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
use Magento\Framework\Data\Collection;
use Magento\Framework\ObjectManager\ObjectManager;

/**
 * Class MagentoOverrider
 * @package Emagicone\Bridgeconnector\Helper
 */
class MagentoOverrider extends BridgeConnectorCore
{
    private $request;

    /**
     * @var ObjectManager
     */
    private $_objectManager;

    /**
     * MagentoOverrider constructor.
     * @param $module_name
     * @param $options_name
     * @param $request
     * @param $object_manager
     */
    public function __construct(
        $module_name,
        $options_name,
        $request,
        ObjectManager $object_manager
    ) {
        $this->module_name = $module_name;
        $this->options_name = $options_name;
        $this->request = $request;
        $this->_objectManager = $object_manager;
    }

    public function isModuleEnabled()
    {
        return $this->_objectManager->get('Magento\Framework\Module\Manager')->isOutputEnabled($this->module_name);
    }

    public function getBridgeOptions()
    {
        $options = Tools::getConfigValue($this->options_name);

        return $options ? $this->unserialize($options) : false;
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
        $ret = [];

        try {
            $result = [];
            $query = Tools::getResource()->getConnection()->query($sql);

            if ($query) {
                while ($row = $query->fetch()) {
                    $result[] = $row;
                }
            }

            if ($type == self::ASSOC) {
                return $result;
            }

            foreach ($result as $arr_values) {
                $ret[] = array_values($arr_values);
            }
        } catch (\Exception $e) {
            $error = $e->getPrevious();
            if (!empty($error) && version_compare(phpversion(), '7.0', '>=')) {
                $this->error_no = $error->getCode();
                $this->error_msg = $error->getMessage();
            } elseif (!empty($error)) {
                $this->error_no = $error->errorInfo[1];
                $this->error_msg = $error->errorInfo[2];
            }

            return false;
        }

        return $ret;
    }

    public function execSql($sql)
    {
        $result = true;

        try {
            Tools::getResource()->getConnection()->rawQuery($sql);
        } catch (\Exception $e) {
            if (!empty($error) && version_compare(phpversion(), '7.0', '>=')) {
                $this->error_no = $e->getCode();
                $this->error_msg = $e->getMessage();
            } elseif (!empty($error)) {
                $this->error_no = $e->errorInfo[1];
                $this->error_msg = $e->errorInfo[2];
            }
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
        return null !== $this->getRequest()->getParam($param);
    }

    private function getRequest()
    {
        return $this->request;
    }

    public function getRequestParam($param)
    {
        return $this->getRequest()->getParam($param);
    }

    public function getRequestData()
    {
        return $this->getRequest()->getParams();
    }

    public function getStoreLink($ssl = false)
    {
        return false;
    }

    public function runIndexer()
    {
        $indexers = $this->_objectManager->get('Magento\Indexer\Model\Indexer\Collection')->getItems();
        $result = '';

        foreach ($indexers as $indexer) {
            $indexer->reindexAll();
            $result .= $indexer->getTitle()->getText() . ' index was rebuilt successfully<br>';
        }

        return $result;
    }

    public function getCartVersion()
    {
        return Tools::jsonEncode(
            [
                'cart_version' => $this->_objectManager->get('Magento\Framework\App\ProductMetadataInterface')
                    ->getVersion(),
                'crypt_key' => Tools::getDeploymentConfig()->get(ConfigOptionsListConstants::CONFIG_PATH_CRYPT_KEY)
            ]
        );
    }

    public function getImage($entity_type, $image_id)
    {
        return $this->getImagePath($entity_type, $image_id);
    }

    private function getImagePath($entity_type, $image_id)
    {
        /** @var \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
        $mediaDirectory = $this->_objectManager->get('Magento\Framework\Filesystem')
            ->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);

        return $mediaDirectory->getAbsolutePath($this->getImageDir($entity_type)) . $image_id;
    }

    public function setImage($entity_type, $image_id, $img, $type)
    {
        $result = false;
        $img_file = $this->getImage($entity_type, $image_id);
        $dirpath = $this->getParentDirectory($img_file);

        if (!$this->fileExists($dirpath)) {
            $this->createDirectory($dirpath, 0777);
        }

        if ($this->fileExists($dirpath)) {
            if ($type == self::IMAGE_URL) {
                $result = $this->filePutContents($img_file, $this->fileGetContents($img));
                if (!$result) {
                    $this->filePutContents($img_file, $this->fileGetCurlContents($img));
                }
            } else {
                $newFileName = basename($image_id);
                $result = $this->moveUploadedFile($img, $dirpath, $newFileName);
            }
        }

        if ($result) {
            $response = $this->jsonEncode([
                self::CODE_RESPONSE => self::SUCCESSFUL,
                self::KEY_MESSAGE => 'File was successfully uploaded',
            ]);
        } else {
            $response = $this->jsonEncode([
                self::CODE_RESPONSE => self::ERROR_CODE_COMMON,
                self::KEY_MESSAGE => 'File was not uploaded',
            ]);
        }

        return $response;
    }

    public function deleteImage($entity_type, $image_id)
    {
        return $this->deleteFile($this->getImagePath($entity_type, $image_id));
    }

    public function deleteFile($filepath)
    {
        if ($this->fileExists($filepath) && $this->unlink($filepath)) {
            $result = $this->jsonEncode([
                self::CODE_RESPONSE => self::SUCCESSFUL,
                self::KEY_MESSAGE => 'File was deleted from server successfully',
            ]);
        } else {
            $result = $this->jsonEncode(
                [
                    self::CODE_RESPONSE => self::ERROR_CODE_COMMON,
                    self::KEY_MESSAGE => 'File was not deleted from server',
                ]
            );
        }

        return $result;
    }

    public function copyImage($entity_type, $from_image_id, $to_image_id)
    {
        return false;
    }

    public function getFile($folder, $filename)
    {
        return $this->getShopRootDir() . '/' . $folder . '/' . $filename;
    }

    public function setFile($folder, $file)
    {
        $destinationPath = $this->getShopRootDir() . "/$folder";
        $result = $this->createDirectory($destinationPath, 0777);

        if ($result) {
            $result = $this->moveUploadedFile($file, $destinationPath);
        }

        if ($result) {
            $response = $this->jsonEncode([
                self::CODE_RESPONSE => self::SUCCESSFUL,
                self::KEY_MESSAGE => 'File was successfully uploaded',
            ]);
        } else {
            $response = $this->jsonEncode([
                self::CODE_RESPONSE => self::ERROR_CODE_COMMON,
                self::KEY_MESSAGE => 'File was not uploaded',
            ]);
        }

        return $response;
    }

    public function getImageDir($type)
    {
        $path = '';

        switch ($type) {
            case self::PRODUCT:
                /** @var \Magento\Catalog\Model\Product\Media\Config $config */
                $config = $this->_objectManager->get('Magento\Catalog\Model\Product\Media\Config');
                $path = $config->getBaseMediaPath();
                break;
            case self::CATEGORY:
                $path = 'catalog/category/';
                break;
            case self::ATTRIBUTE:
                /** @var \Magento\Swatches\Helper\Media $swatches_media */
                $swatches_media = $this->_objectManager->get('Magento\Swatches\Helper\Media');
                $path = $swatches_media::SWATCH_MEDIA_PATH;
                break;
        }

        return $path;
    }

    public function getUploadedFileInfo($filename)
    {
        $uploaded_file_info = false;

        try {
            $uploaded_file = $this->_objectManager->create(
                'Magento\MediaStorage\Model\File\Uploader',
                ['fileId' => $filename]
            );

            $uploaded_file_info = $uploaded_file->validateFile();
        } catch (\Exception $e) {
            $error = $e->getPrevious();
            if (!empty($error) && version_compare(phpversion(), '7.0', '>=')) {
                $this->error_no = $error->getCode();
                $this->error_msg = $error->getMessage();
            } elseif (!empty($error)) {
                $this->error_no = $error->errorInfo[1];
                $this->error_msg = $error->errorInfo[2];
            }
        }

        return $uploaded_file_info;
    }

    public function strLen($str)
    {
        return strlen($str);
    }

    public function subStr($str, $start, $length = false)
    {
        return $length ? substr($str, $start, $length) : substr($str, $start);
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
        return Tools::getFile()->fileGetContents($file);
    }

    public function fileGetCurlContents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public function filePutContents($path, $content, $mode = null)
    {
        return Tools::getFile()->filePutContents($path, $content, $mode);
    }

    public function pSQL($data)
    {
        return $data;
    }

    public function saveConfigData($data)
    {
        Tools::saveConfigValue($this->options_name, serialize($data));
    }

    public function fileOpen($path, $mode)
    {
        return Tools::getFile()->fileOpen($path, $mode);
    }

    public function fileClose($resource)
    {
        return Tools::getFile()->fileClose($resource);
    }

    public function isReadable($path)
    {
        return Tools::getFile()->isReadable($path);
    }

    public function isWritable($path)
    {
        return Tools::getFile()->isWritable($path);
    }

    public function isDirectory($path)
    {
        return Tools::getFile()->isDirectory($path);
    }

    public function isFile($path)
    {
        return Tools::getFile()->isFile($path);
    }

    public function stat($path)
    {
        return Tools::getFile()->stat($path);
    }

    public function search($path, $pattern = '*', $onlyDir = false)
    {
        $data = Tools::getFile()->search($pattern, $path);

        if ($onlyDir) {
            $dirs = [];

            foreach ($data as $item) {
                if ($this->isDirectory($item)) {
                    $dirs[] = $item;
                }
            }

            return $dirs;
        }

        return $data;
    }

    public function readDirectory($path)
    {
        return Tools::getFile()->readDirectory($path);
    }

    public function filemtime($path)
    {
        $data = $this->stat($path);

        if (!$data || !isset($data['size'])) {
            return false;
        }

        return $data['mtime'];
    }

    public function fileSize($path)
    {
        $data = $this->stat($path);

        if (!$data || !isset($data['size'])) {
            return false;
        }

        return $data['size'];
    }

    public function fileExists($path)
    {
        return Tools::getFile()->isExists($path);
    }

    public function fileWrite($resource, $data)
    {
        return Tools::getFile()->fileWrite($resource, $data);
    }

    public function fileRead($resource, $length)
    {
        return Tools::getFile()->fileRead($resource, $length);
    }

    public function gzFileOpen($path, $mode)
    {
        $file = $this->_objectManager->create('Emagicone\Bridgeconnector\Helper\GzFile', ['filePath' => $path]);
        $file->gzOpen($mode);

        return $file;
    }

    public function gzFileWrite($resource, $data)
    {
        $resource->gzWrite($data);
    }

    public function gzFileClose($resource)
    {
        $resource->gzClose();
    }

    public function unlink($path)
    {
        return Tools::getFile()->deleteFile($path);
    }

    public function moveUploadedFile($filename, $destinationFolder, $newFileName = null)
    {
        return Tools::getUploader($filename)->save($destinationFolder, $newFileName);
    }

    public function createDirectory($path, $permissions)
    {
        return Tools::getFile()->createDirectory($path, $permissions);
    }

    public function getParentDirectory($path)
    {
        return Tools::getFile()->getParentDirectory($path);
    }

    public function unserialize($data)
    {
        return Tools::unserialize($data);
    }

    public function getRemoteAddress($ipToLong = false)
    {
        return $this->_objectManager->get('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress')
            ->getRemoteAddress($ipToLong);
    }

    public function escapeQuote($data, $addSlashes = false)
    {
        return Tools::getEscaper()->escapeQuote($data, $addSlashes);
    }

    public function base64Encode($data)
    {
        return Tools::base64Encode($data);
    }

    public function base64Decode($data)
    {
        return Tools::base64Decode($data);
    }

    /**
     * @param $key
     * @return bool
     */
    public function isSessionKeyValid($key)
    {
        $key = $this->_objectManager->create('Emagicone\Bridgeconnector\Model\SessionKey')
            ->getCollection()
            ->addFieldToFilter('session_key', $key)
            ->addFieldToFilter('last_activity', ['gt' => date('Y-m-d H:i:s', (time() - Constants::MAX_KEY_LIFETIME))])
            ->fetchItem();

        if ($key) {
            $key->setData('last_activity', date('Y-m-d H:i:s'))->save();

            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function addFailedAttempt()
    {
        $timestamp = time();

        // Add failed attempt
        $this->_objectManager->create('Emagicone\Bridgeconnector\Model\FailedLogin')
            ->setData(['ip' => $this->getRemoteAddress(), 'date_added' => date('Y-m-d H:i:s', $timestamp)])
            ->save();

        // Select count of failed attempts
        $collection = $this->_objectManager->create('Emagicone\Bridgeconnector\Model\FailedLogin')
            ->getCollection()
            ->addFieldToFilter('ip', $this->getRemoteAddress())
            ->addFieldToFilter(
                'date_added',
                ['gt' => date('Y-m-d H:i:s', ($timestamp - Constants::MAX_KEY_LIFETIME))]
            );

        return $collection->getSize();
    }

    /**
     * @param $hash
     * @return string
     */
    public function generateSessionKey($hash)
    {
        $timestamp = time();
        $key = $this->_objectManager->create('Emagicone\Bridgeconnector\Model\SessionKey')
            ->getCollection()
            ->addFieldToFilter(
                'last_activity',
                ['gt' => date('Y-m-d H:i:s', ($timestamp - Constants::MAX_KEY_LIFETIME))]
            )
            ->addOrder('last_activity', Collection::SORT_ORDER_DESC)
            ->fetchItem();

        if ($key && $key->getSessionKey()) {
            return $key->getSessionKey();
        }

        // Generate new session key and store it in database
        $key = hash('sha256', $hash . $timestamp);
        $date = date('Y-m-d H:i:s', $timestamp);
        $this->_objectManager->create('Emagicone\Bridgeconnector\Model\SessionKey')
            ->setData(['session_key' => $key, 'date_added' => $date, 'last_activity' => $date])
            ->save();

        return $key;
    }

    /**
     * @param $key
     * @return bool
     */
    public function deleteSessionKey($key)
    {
        if (!$key) {
            return false;
        }

        $this->_objectManager->create('Emagicone\Bridgeconnector\Model\SessionKey')
            ->getCollection()
            ->addFieldToFilter('session_key', ['eq' => $key])
            ->walk('delete');

        return true;
    }

    public function clearOldData()
    {
        $timestamp = time();
        $date = date('Y-m-d H:i:s', ($timestamp - Constants::MAX_KEY_LIFETIME));

        // Delete old session keys
        $this->_objectManager->create('Emagicone\Bridgeconnector\Model\SessionKey')
            ->getCollection()
            ->addFieldToFilter('last_activity', ['lt' => $date])
            ->walk('delete');

        // Delete old failed login
        $this->_objectManager->create('Emagicone\Bridgeconnector\Model\FailedLogin')
            ->getCollection()
            ->addFieldToFilter('date_added', ['lt' => $date])
            ->walk('delete');
    }

    public function checkDataChanges($tablesArr = [])
    {
        $arr_result = [];
        $count = count($tablesArr);

        for ($i = 0; $i < $count; $i++) {
            $table = trim($tablesArr[$i]);

            if (empty($table)) {
                continue;
            }

            try {
                $arr_result[$table] = $this->_objectManager->get('Magento\ImportExport\Model\ResourceModel\Helper')
                        ->getNextAutoincrement($table) - 1;
            } catch (\Exception $e) {
                $arr_result[$table] = '';
            }
        }

        if (empty($arr_result)) {
            return $this->jsonEncode(
                [
                    self::CODE_RESPONSE => self::ERROR_CODE_COMMON,
                    self::KEY_MESSAGE => Tools::jsonEncode($arr_result)
                ]
            );
        }

        return $this->jsonEncode(
            [
                self::CODE_RESPONSE => self::SUCCESSFUL,
                self::KEY_MESSAGE => Tools::jsonEncode($arr_result)
            ]
        );
    }

    public function getNewOrders($order_id = -1)
    {
        if ($order_id < 1) {
            return false;
        }

        $max_order_id = 0;
        $order_info = [];
        $collection_factory = $this->_objectManager->get('\Magento\Sales\Model\ResourceModel\Order\CollectionFactory');

        // Get max order id
        $collection = $collection_factory->create();
        $collection->addAttributeToSort('entity_id', 'desc')
            ->setPageSize(1)
            ->load();
        $item = $collection->fetchItem();
        if ($item) {
            $max_order_id = (int)$item->getEntityId();
        }

        // Get new orders
        $collection = $collection_factory->create();
        $collection->addAttributeToFilter('entity_id', ['gt' => $order_id]);
        $count_new_orders = (int)$collection->getSize();
        foreach ($collection as $order) {
            $order_info[] = [
                'order_id' => $order->getEntityId(),
                'customer_id' => $order->getCustomerId(),
                'grand_total' => $order->getGrandTotal(),
                'total_paid' => $order->getTotalPaid(),
                'order_currency_code' => $order->getOrderCurrencyCode(),
                'firstname' => $order->getCustomerFirstname(),
                'lastname' => $order->getCustomerLastname()
            ];
        }

        return $this->jsonEncode(
            [
                self::CODE_RESPONSE => self::SUCCESSFUL,
                self::KEY_MESSAGE => Tools::jsonEncode(
                    [
                        'CountNewOrder' => $count_new_orders,
                        'MaxOrderId' => $max_order_id,
                        'OrderInfo' => $order_info
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
                    self::KEY_MESSAGE => 'Cannot get cache types'
                ]
            );
        }

        return $this->jsonEncode(
            [
                self::CODE_RESPONSE => self::SUCCESSFUL,
                self::KEY_MESSAGE => Tools::jsonEncode($caches)
            ]
        );
    }

    public function clearCache()
    {
        $cache_type = $this->getRequestParam('cache_type');

        if (!$cache_type) {
            return $this->jsonEncode(
                [
                    self::CODE_RESPONSE => self::ERROR_CODE_COMMON,
                    self::KEY_MESSAGE => 'Incorrect cache type'
                ]
            );
        }

        $cache_types = explode(';', $cache_type);
        $count = count($cache_types);
        $result = '';

        for ($i = 0; $i < $count; $i++) {
            Tools::cleanCache($cache_types[$i]);
            $result .= "$cache_types[$i] refreshed";
        }

        return $result;
    }

    public function getZipArchiveInstance()
    {
        return $this->_objectManager->create('\ZipArchive');
    }

    public function getZipArchiveCreateValue()
    {
        return \ZipArchive::CREATE;
    }

    public function getPaymentAndShippingMethods()
    {
        return false;
    }
}
