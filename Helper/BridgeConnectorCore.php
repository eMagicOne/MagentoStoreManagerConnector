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

/**
 * Class which contains those methods which have be overridden in child class
 */
abstract class BridgeConnectorCore
{

    public $module_name;
    protected $options_name;

    public $error_no;   /* error number during sql query execution */
    public $error_msg;  /* error message during sql query execution */

    const NUMERIC      = 1;     /* numeric array */
    const ASSOC        = 0;     /* associating array */
    const PRODUCT      = 'p';   /* entity 'product' */
    const CATEGORY     = 'c';   /* entity 'category' */
    const MANUFACTURER = 'm';   /* entity 'manufacturer' */
    const CARRIER      = 's';   /* entity 'carrier' */
    const SUPPLIER     = 'su';  /* entity 'supplier' */
    const ATTRIBUTE    = 'co';  /* entity 'attribute' */

    /*const IMAGE_FILE        = 'image_file';*/
    const IMAGE_URL         = 'image_url';
    const CODE_RESPONSE     = 'response_code';
    const KEY_MESSAGE       = 'message';
    const SUCCESSFUL        = 20; /* an operation was executed successfully */
    const ERROR_CODE_COMMON = 19;

    abstract protected function isModuleEnabled();
    abstract protected function getBridgeOptions();
    abstract protected function getShopRootDir();
    abstract protected function getDbHost();
    abstract protected function getDbName();
    abstract protected function getDbUsername();
    abstract protected function getDbPassword();
    abstract protected function getDbPrefix();
    abstract protected function getSqlResults($sql);
    abstract protected function execSql($sql, $reconnect);
    abstract protected function sanitizeSql($sql);
    abstract protected function issetRequestParam($param);
    abstract protected function getRequestParam($param);
    abstract protected function getStoreLink($ssl);
    abstract protected function runIndexer();
    abstract protected function getCartVersion();
    abstract protected function getImage($entity_type, $image_id);
    abstract protected function setImage($entity_type, $image_id, $image, $type);
    abstract protected function getImageDir($type);
    abstract protected function deleteImage($entity_type, $image_id);
    abstract protected function deleteFile($filepath);
    abstract protected function copyImage($entity_type, $from_image_id, $to_image_id);
    abstract protected function getFile($folder, $filename);
    abstract protected function setFile($folder, $filename, $file);
    abstract protected function checkDataChanges($tables_arr);
    abstract protected function getNewOrders($order_id);
//    abstract protected function getCache();
    abstract protected function clearCache();
    abstract protected function strLen($str);
    abstract protected function subStr($str, $start, $length);
    abstract protected function strToLower($str);
    abstract protected function strToUpper($str);
    abstract protected function jsonEncode($arr);
    abstract protected function stripSlashes($str);
    abstract protected function fileGetContents($file);
    abstract protected function pSQL($data);
    abstract protected function saveConfigData($data);
//    abstract protected function getRecursiveIteratorIterator($directory);

    public function getCodeResponse()
    {
        return self::CODE_RESPONSE;
    }

    public function getCodeSuccessful()
    {
        return self::SUCCESSFUL;
    }

    public function getKeyMessage()
    {
        return self::KEY_MESSAGE;
    }

}
