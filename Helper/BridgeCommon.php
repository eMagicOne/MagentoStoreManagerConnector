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

/**
 * Class which has common bridge functionality
 */
class BridgeCommon
{
    /** @var $shop_cart MagentoOverrider */
    private $shop_cart;
    private $backup_file_ext = 'sql';
    private $br_errors;
    private $bridge_options;
    private $sql_compatibility;
    private $sql_delimiter = '/*DELIMITER*/';
    private $count_sql_exec_prev = 0;
    private $request_params;
    private $db_tables = [];
    private $db_views = [];
    private $handled_tables = [];           /* Array of processed tables */
    private $table_sizes = [];              /* Information about size of each table */
    private $db_size = 0;                   /* Size of all data in database which will be processed */
    private $log_file_reset = false;
    private $db_file_handler;
    private $tmp_folder_path;
    private $module_version;
    private $revision;
    private $post_replace_from_sm = ['-' => '+', '_' => '/', ',' => '=']; /* Replace symbols in chunk */
    private $image_url;
    private $code_response;
    private $key_message;
    private $successful_code;
    private $error_code_common;
    private $dump_data_prev = false;
    private $default_tmp_dir;
    private $dump_file_current;
    private $dump_file_part_number = 1;
    private $response;
    private $responseKeyOutput;
    private $responseKeyHeaders;
    private $maxKeyLifetime;

    const TEST_POST_STRING                   = '////AjfiIkllsomsdjUNNLkdsuinmJNFIkmsiidmfmiOKSFKMI/////';
    const TEST_OK                            = '<span style="color: #008000;">Ok</span>';
    const TEST_FAIL                          = '<span style="color: #ff0000;">Fail</span>';
    const TEST_YES                           = '<span style="color: #008000;">Yes</span>';
    const TEST_SKIP                          = '<span style="color: #808080;">Test Skipped</span>';
    const TEST_NO                            = '<span style="color: #ff0000;">Fail</span>';
    const QOUTE_CHAR                         = '"';
    const LOG_FILENAME                       = 'bridgeconnector.log';
    const DB_FILE_PREFIX                     = 'm1bridge_';
    const TMP_FILE_PREFIX                    = 'm1bridgetmp_';
    const INTERMEDIATE_FILE_NAME             = 'sm_intermediate.txt';
    const DB_FILE_MAIN                       = 'em1_bridge_db_dump';
    const DB_DATA_TMP                        = 'em1_dump_data_tmp.txt';
    const FILE_TMP_GET_SQL                   = 'em1_tmp_get_sql.txt';
    const FILE_TMP_PUT_SQL                   = 'em1_tmp_put_sql.txt';
    const GET_SQL_CANCEL_MESSAGE             = 'Generating database dump is canceled';
    const GET_SQL_CANCEL_PARAM               = 'get_sql_cancel';
    const GET_SQL_TABLE                      = 'get_sql_table';
    const GET_SQL_PERCENTAGE                 = 'get_sql_percentage';
    const GET_SQL_FILE_PART                  = 'get_sql_file_part';
    const GET_SQL_FILE_PART_NAME             = 'get_sql_file_part_name';
    const GET_SQL_FILE_NAME_GENERATING       = 'get_sql_file_name_generating';
    const FILE_NAME_FILE_LIST                = 'em1_bridge_file_list';
    const FILE_NAME_GET_FILE_LIST_TMP        = 'sm_tmp_get_file_list.txt';
    const KEY_PROCESS_ID                     = 'process_id';
    const DB_FILE_EXT_COMPRESSION_NO         = '.sql';
    const DB_FILE_EXT_COMPRESSION_YES        = '.gz';
    const FILE_EXT_TXT                       = '.txt';
    const FILE_NAME_PART_NUMBER_COUNT_DIGITS = 3;
    const NUMERIC                            = 1;
    const ASSOC                              = 0;
    const PUT_SQL_ENCODED                    = 'base_64_encoded_';
    const UPLOAD_FILE_NAME                   = 'file';
    const FILE_READ_SIZE                     = 102400; /* B */
    const DELAY_TO_GENERATE_DUMP             = 10; /* seconds */

    /* Section of default values which are stored in database */
    const DEFAULT_LOGIN                      = '1';
    const DEFAULT_PASSWORD                   = '1';
    const DEFAULT_ALLOW_COMPRESSION          = 1;
    const DEFAULT_COMPRESS_LEVEL             = 6;      /* 1 - 9 */
    const DEFAULT_LIMIT_QUERY_SIZE           = 8192;      /* kB */
    const DEFAULT_PACKAGE_SIZE               = 1024;      /* kB */
    const DEFAULT_EXCLUDE_DB_TABLES          = '';
    const DEFAULT_NOTIFICATION_EMAIL         = '';
    const DEFAULT_ALLOWED_IPS                = '';
    const MIN_COMPRESS_LEVEL                 = 1;
    const MAX_COMPRESS_LEVEL                 = 9;
    const MIN_LIMIT_QUERY_SIZE               = 100;    /* kB */
    const MAX_LIMIT_QUERY_SIZE               = 100000;    /* kB */
    const MIN_PACKAGE_SIZE                   = 100;    /* kB */
    const MAX_PACKAGE_SIZE                   = 30000;    /* kB */

    /* Chunk checksum from the store manager and chunk checksum from the bridge file are different */
    const POST_ERROR_CHUNK_CHECKSUM_DIF      = 21;

    /* Chunk checksum are correct, but some sql code was not executed; has one parameter - an index of sql code
    which was not executed */
    const POST_ERROR_SQL_INDEX               = 22;

    const ERROR_CODE_AUTHENTICATION          = 25;
    const ERROR_CODE_SESSION_KEY             = 26;
    const ERROR_TEXT_AUTHENTICATION          = 'Authentication error';
    const ERROR_TEXT_SESSION_KEY             = 'Session key error';

    /* It is used to retry putting sql when server is temporary unavailable */
    const MAX_COUNT_ATTEMPT_POST             = 3;   /* maximum count of attempts */
    const DELAY_BETWEEN_POST                 = 20;  /* delay between attempts (seconds) */

    /**
     * BridgeCommon constructor.
     * @param $shop_cart_overrider
     * @param $module_version
     * @param $revision
     * @param $responseKeyOutput
     * @param $responseKeyHeaders
     * @param $maxKeyLifetime
     */
    public function __construct(
        $shop_cart_overrider,
        $module_version,
        $revision,
        $responseKeyOutput,
        $responseKeyHeaders,
        $maxKeyLifetime
    ) {
        $this->shop_cart = $shop_cart_overrider;
        $this->default_tmp_dir = '/modules/'.$this->shop_cart->module_name.'/tmp';
        $this->image_url = $shop_cart_overrider::IMAGE_URL;
        $this->code_response = $shop_cart_overrider::CODE_RESPONSE;
        $this->key_message = $shop_cart_overrider::KEY_MESSAGE;
        $this->successful_code = $shop_cart_overrider::SUCCESSFUL;
        $this->error_code_common = $shop_cart_overrider::ERROR_CODE_COMMON;
        $this->module_version = $module_version;
        $this->revision = $revision;
        $this->responseKeyOutput = $responseKeyOutput;
        $this->responseKeyHeaders = $responseKeyHeaders;
        $this->maxKeyLifetime = $maxKeyLifetime;
        $this->getErrors();
        $this->bridge_options = $this->shop_cart->getBridgeOptions();
        $this->tmp_folder_path = $this->shop_cart->getShopRootDir().$this->bridge_options['tmp_dir'];
        $timestamp = time();

        if (!isset($this->bridge_options['last_clear_date'])
            || ($timestamp - (int)$this->bridge_options['last_clear_date']) > $this->maxKeyLifetime
        ) {
            $this->clearOldData();
            $this->bridge_options['last_clear_date'] = $timestamp;
            $this->shop_cart->saveConfigData($this->bridge_options);
        }

        $this->checkBridgeOptions();

        if (!$this->shop_cart->issetRequestParam('task')) {
            $this->response = $this->runSelfTest();
        } elseif (!$this->shop_cart->isModuleEnabled()) {
            $this->response = $this->generateError($this->br_errors['module_disabled']);
        } else {
            $this->response = $this->checkAuth();
            if (!$this->response) {
                // Uncomment if have some troubles with getting data
                //
                // ini_set('max_execution_time', 7200);
                // ini_set('memory_limit', -1);
                $this->response = $this->bridgeAction();
            }
        }
    }

    private static function underscoresToCamelCase($string)
    {
        if (!$string) {
            return '';
        }

        $str    = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        $str[0] = strtolower($str[0]);

        return $str;
    }

    private function getErrors()
    {
        $this->br_errors = [
            'authentification' => "BridgeConnector (v.{$this->module_version}): Authentication Error",
            'create_tmp_file' => "BridgeConnector (v.{$this->module_version}): Can't Create Temporary File",
            'open_tmp_file' => "BridgeConnector (v.{$this->module_version}): Can't Open Temporary File",
            'not_writeable_dir' => "BridgeConnector (v.{$this->module_version}): Temporary Directory specified in
                BridgeConnector settings doesn't exist or is not writeable",
            'temporary_file_exist_not' => "BridgeConnector (v.{$this->module_version}): Temporary File doesn't exist",
            'temporary_file_readable_not' => "BridgeConnector (v.{$this->module_version}): Temporary File isn't
                readable",
            'file_uid_mismatch' => "BridgeConnector (v.{$this->module_version}): SAFE MODE Restriction in effect.
                The script uid is not allowed to access tmp folder owned by other uid. If you don't understand this
                error, please contact your hosting provider for help",
            'open_basedir' => "BridgeConnector (v.{$this->module_version}): Please create local Temporary Directory,
                see \$temporary_dir variable in bridge.php",
            'checksum_dif' => 'Checksums are different',
            'ip_check' => "BridgeConnector (v.{$this->module_version}): Add your IP to allowed list to run bridge,
                please",
            'module_disabled' => 'Module is disabled',
            'filename_param_missing' => 'Request parameter "filename" is missing',
            'position_param_missing' => 'Request parameter "position" is missing',
            'sql_param_missing' => 'Request parameter "sql" is missing',
            'category_param_missing' => 'Request parameter "category" is missing',
            'searchpath_param_missing' => 'Request parameter "search_path" is missing',
            'varsmaindir_param_missing' => 'Request parameter "vars_main_dir" is missing',
            'varsnames_param_missing' => 'Request parameter "vars_names" is missing',
            'xmlpath_param_missing' => 'Request parameter "xml_path" is missing',
            'xmlfields_param_missing' => 'Request parameter "xml_fields" is missing',
            'xmlitemsnode_param_missing' => 'Request parameter "xml_items_node" is missing',
            'xmlitemsinfonode_param_missing' => 'Request parameter "xml_items_info_node" is missing',
            'tablename_param_missing' => 'Request parameter "table_name" is missing',
            'orderid_param_missing' => 'Request parameter "order_id" is missing',
            'entitytype_param_missing' => 'Request parameter "entity_type" is missing',
            'imageid_param_missing' => 'Request parameter "image_id" is missing',
            'toimageid_param_missing' => 'Request parameter "to_image_id" is missing',
            'path_param_missing' => 'Request parameter "path" is missing',
            'searchpath_param_empty' => 'Request parameter "search_path" is empty',
            'varsmaindir_param_empty' => 'Request parameter "vars_main_dir" is empty',
            'varsnames_param_empty' => 'Request parameter "vars_names" is empty',
            'xmlpath_param_empty' => 'Request parameter "xml_path" is empty',
            'xmlfields_param_empty' => 'Request parameter "xml_fields" is empty',
            'xmlitemsnode_param_empty' => 'Request parameter "xml_items_node" is empty',
            'xmlitemsinfonode_param_empty' => 'Request parameter "xml_items_info_node" is empty',
            'tablename_param_empty' => 'Request parameter "table_name" is empty',
            'entitytype_param_empty' => 'Request parameter "entity_type" is empty',
            'imageurl_param_empty' => 'Request parameter "image_url" is empty',
            'key_param_empty' => 'Request parameter "key" is empty',
            'hash_param_empty' => 'Request parameter "hash" is empty',
            'filename_param_empty' => 'Request parameter "filename" is empty',
            'path_param_empty' => 'Request parameter "path" is empty',
            'category_param_empty' => 'Request parameter "category" is empty',
            'orderid_param_incorrect' => 'Request parameter "order_id" is incorrect',
            'imageid_param_incorrect' => 'Request parameter "image_id" is incorrect',
            'toimageid_param_incorrect' => 'Request parameter "to_image_id" is incorrect',
            'upload_file_error' => 'Some error occurs uploading file into temporary server folder',
            'delete_file_error' => 'No such file',
            'zip_archive_not_supported' => 'ZipArchive is supported in php version >= 5.2.0',
            'zip_not_loaded' => 'Zip extension not loaded',
            'cannot_archive_files' => 'Cannot archive files',
        ];
    }

    private function checkBridgeOptions()
    {
        $this->bridge_options['tmp_dir'] = isset($this->bridge_options['tmp_dir'])
            ? $this->bridge_options['tmp_dir']
            : $this->default_tmp_dir;
        $this->bridge_options['bridge_hash'] = isset($this->bridge_options['bridge_hash'])
            ? $this->bridge_options['bridge_hash']
            : '';
        $this->bridge_options['allowed_ips'] = isset($this->bridge_options['allowed_ips'])
            ? $this->bridge_options['allowed_ips']
            : self::DEFAULT_ALLOWED_IPS;
        $this->bridge_options['exclude_db_tables'] = isset($this->bridge_options['exclude_db_tables'])
            ? $this->bridge_options['exclude_db_tables']
            : self::DEFAULT_EXCLUDE_DB_TABLES;
        $this->bridge_options['notification_email'] = isset($this->bridge_options['notification_email'])
            ? $this->bridge_options['notification_email']
            : self::DEFAULT_NOTIFICATION_EMAIL;
        $this->bridge_options['allow_compression'] = isset($this->bridge_options['allow_compression'])
            ? (int)$this->bridge_options['allow_compression']
            : self::DEFAULT_ALLOW_COMPRESSION;

        if (!isset($this->bridge_options['limit_query_size'])) {
            $this->bridge_options['limit_query_size'] = self::DEFAULT_LIMIT_QUERY_SIZE;
        } elseif ((int)$this->bridge_options['limit_query_size'] < self::MIN_LIMIT_QUERY_SIZE) {
            $this->bridge_options['limit_query_size'] = self::MIN_LIMIT_QUERY_SIZE;
        } elseif ((int)$this->bridge_options['limit_query_size'] > self::MAX_LIMIT_QUERY_SIZE) {
            $this->bridge_options['limit_query_size'] = self::MAX_LIMIT_QUERY_SIZE;
        } else {
            $this->bridge_options['limit_query_size'] = (int)$this->bridge_options['limit_query_size'];
        }

        if (!isset($this->bridge_options['package_size'])) {
            $this->bridge_options['package_size'] = self::DEFAULT_PACKAGE_SIZE * 1024; // B
        } elseif ((int)$this->bridge_options['package_size'] < self::MIN_PACKAGE_SIZE) {
            $this->bridge_options['package_size'] = self::MIN_PACKAGE_SIZE * 1024;
        } elseif ((int)$this->bridge_options['package_size'] > self::MAX_PACKAGE_SIZE) {
            $this->bridge_options['package_size'] = self::MAX_PACKAGE_SIZE * 1024;
        } else {
            $this->bridge_options['package_size'] = (int)$this->bridge_options['package_size'] * 1024;
        }

        // Values of $compress_level between 1 and 9 will trade off speed and efficiency, and the default is 6.
        // The 1 flag means "fast but less efficient" compression, and 9 means "slow but most efficient" compression.
        if (!isset($this->bridge_options['compress_level'])) {
            $this->bridge_options['compress_level'] = self::DEFAULT_COMPRESS_LEVEL;
        } elseif ((int)$this->bridge_options['compress_level'] < self::MIN_COMPRESS_LEVEL) {
            $this->bridge_options['compress_level'] = self::MIN_COMPRESS_LEVEL;
        } elseif ((int)$this->bridge_options['compress_level'] > self::MAX_COMPRESS_LEVEL) {
            $this->bridge_options['compress_level'] = self::MAX_COMPRESS_LEVEL;
        } else {
            $this->bridge_options['compress_level'] = (int)$this->bridge_options['compress_level'];
        }
    }

    private function checkAuth()
    {
        $output = false;

        if ($this->shop_cart->issetRequestParam('key')) {
            $key = (string)$this->shop_cart->getRequestParam('key');

            if (empty($key)) {
                $this->addFailedAttempt();
                return $this->generateError($this->br_errors['key_param_empty']);
            }

            if (!$this->isSessionKeyValid($key)) {
                $this->addFailedAttempt();
                $output = $this->shop_cart->jsonEncode(
                    [
                        $this->code_response => self::ERROR_CODE_SESSION_KEY,
                        $this->key_message   => self::ERROR_TEXT_SESSION_KEY,
                    ]
                );
            }
        } elseif ($this->shop_cart->issetRequestParam('hash')) {
            $hash = (string)$this->shop_cart->getRequestParam('hash');

            if (empty($hash)) {
                $this->addFailedAttempt();
                return $this->generateError($this->br_errors['hash_param_empty']);
            }

            if (!$this->isHashValid($hash)) {
                $this->addFailedAttempt();
                $output = $this->shop_cart->jsonEncode(
                    [
                        $this->code_response => self::ERROR_CODE_AUTHENTICATION,
                        $this->key_message   => self::ERROR_TEXT_AUTHENTICATION,
                    ]
                );

                return [$this->responseKeyOutput => $output];
            }

            $key  = $this->generateSessionKey($hash);
            $task = $this->shop_cart->getRequestParam('task');

            if ($task == 'get_version') {
                $output = $this->shop_cart->jsonEncode(
                    [
                        $this->code_response => $this->successful_code,
                        'revision'           => $this->revision,
                        'module_version'     => $this->module_version,
                        'session_key'        => $key,
                    ]
                );

                return [$this->responseKeyOutput => $output];
            }

            $output = $this->shop_cart->jsonEncode(
                [
                    $this->code_response => $this->successful_code,
                    'session_key'        => $key,
                ]
            );
        } else {
            $this->addFailedAttempt();
            $output = $this->shop_cart->jsonEncode(
                [
                    $this->code_response => self::ERROR_CODE_AUTHENTICATION,
                    $this->key_message   => self::ERROR_TEXT_AUTHENTICATION,
                ]
            );
        }

        return $output ? [$this->responseKeyOutput => $output] : false;
    }

    private function isHashValid($hash)
    {
        return $this->bridge_options['bridge_hash'] == $hash;
    }

    private function bridgeAction()
    {
        $result = $this->checkDataBeforeRun();

        if (true !== $result) {
            return $result;
        }

        $request = $this->shop_cart->getRequestData();

        // Disabling magic quotes at runtime
        if (get_magic_quotes_runtime() || get_magic_quotes_gpc()) {
            $request = array_map([$this, 'stripslashesDeep'], filter_input_array($request));
        }

        $this->request_params = $this->validateTypes(
            $request,
            [
                'task'                => 'STR',
                'category'            => 'STR',
                'include_tables'      => 'STR',
                'sql'                 => 'STR',
                'filename'            => 'STR',
                'position'            => 'INT',
                'vars_names'          => 'STR',
                'vars_main_dir'       => 'STR',
                'xml_path'            => 'STR',
                'xml_fields'          => 'STR',
                'xml_items_node'      => 'STR',
                'xml_items_info_node' => 'STR',
                'xml_filters'         => 'STR',
                'search_path'         => 'STR',
                'mask'                => 'STR',
                'ignore_dir'          => 'STR',
                'checksum_sm'         => 'STR',
                'fc'                  => 'STR',
                'module'              => 'STR',
                'controller'          => 'STR',
                'hash'                => 'STR',
                'entity_type'         => 'STR',
                'image_id'            => 'INT',
                'to_image_id'         => 'INT',
            ]
        );

        $result = $this->getMethod($this->request_params['task']);
        if (!$result) {
            $this->deleteSessionKey();
            $result = [
                $this->responseKeyOutput => $this->shop_cart->jsonEncode(
                    [
                        $this->code_response => $this->error_code_common,
                        $this->key_message   => 'Missing parameters',
                    ]
                )
            ];
        }

        return $result;
    }

    private function getMethod($method)
    {
        $result = false;

        $renamed_methods = [
            'get_sql'             => 'get_db_dump',
            'get_sql_file'        => 'get_db_file',
            'get_version'         => 'get_module_version',
            'get_var_from_script' => 'get_vars',
            'get_sql_cancel'      => 'create_db_dump_cancel',
            'get_sql_progress'    => 'create_db_dump_progress',
        ];

        $allowed_methods = [
            'put_sql',
            'get_config',
            'get_category_tree',
            'run_indexer',
            'get_xml_data',
            'get_ftp_files',
            'get_cart_version',
            'check_data_changes',
            'get_new_orders',
            'get_sql_file_part_info',
            'get_image',
            'set_image',
            'delete_image',
            'delete_file',
            'copy_image',
            'get_file',
            'set_file',
            'get_cache',
            'clear_cache',
            'get_store_file_archive',
            'get_payment_and_shipping_methods',
        ];

        if (in_array($method, array_keys($renamed_methods))) {
            $method = $renamed_methods[$method];
        }

        $method = self::underscoresToCamelCase($method);
        if (method_exists($this, $method) && in_array($method, array_keys($allowed_methods))) {
            $result = $this->$method();
        }

        return $result;
    }

    private function stripslashesDeep($value)
    {
        $value = is_array($value)
            ? array_map([$this, 'stripslashesDeep'], $value)
            : $this->shop_cart->stripSlashes($value);

        return $value;
    }

    private function checkDataBeforeRun()
    {
        if (!$this->checkAllowedIp()) {
            return $this->generateError($this->br_errors['ip_check']);
        }

        if (!ini_get('date.timezone') || ini_get('date.timezone') == '') {
            date_default_timezone_set(date_default_timezone_get());
        }

        if ($this->shop_cart->issetRequestParam('sql_compatibility')) {
            $this->sql_compatibility = $this->shop_cart->getRequestParam('sql_compatibility');
        }

        if ($this->shop_cart->issetRequestParam('sql_delimiter')) {
            $this->sql_delimiter = $this->shop_cart->getRequestParam('sql_delimiter');
        }

        if (!(function_exists('gzopen')
            && function_exists('gzread')
            && function_exists('gzwrite')
            && function_exists('gzclose'))
        ) {
            $this->bridge_options['allow_compression'] = 0;
        }

        // Detecting open_basedir - required for temporary file storage
        if (ini_get('open_basedir') && null == $this->bridge_options['tmp_dir']) {
            return $this->generateError($this->br_errors['open_basedir']);
        }

        // Checking temporary directory
        if (!$this->shop_cart->isDirectory($this->tmp_folder_path)
            || !$this->shop_cart->isWritable($this->tmp_folder_path)
        ) {
            return $this->generateError($this->br_errors['not_writeable_dir']);
        }

        $tmp_file_stat = $this->shop_cart->stat($this->tmp_folder_path);
        if (function_exists('getmyuid') && (ini_get('safe_mode') && getmyuid() != (int)$tmp_file_stat['uid'])) {
            return $this->generateError($this->br_errors['file_uid_mismatch']);
        }

        if ($this->shop_cart->getRequestParam('task') == 'test_post') {
            return [$this->responseKeyOutput => self::TEST_POST_STRING];
        }

        return true;
    }

    private function validateTypes(&$array, $names)
    {
        foreach ($names as $name => $type) {
            if (isset($array[$name])) {
                switch ($type) {
                    case 'INT':
                        $array[$name] = (int)$array[$name];
                        break;
                    case 'FLOAT':
                        $array[$name] = (float)$array[$name];
                        break;
                    case 'STR':
                        $array[$name] = str_replace(
                            ["\r", "\n"],
                            ' ',
                            $this->shop_cart->escapeQuote(trim(urldecode($array[$name])), true)
                        );
                        break;
                    default:
                        $array[$name] = '';
                }
            } else {
                $array[$name] = '';
            }
        }

        $array_keys = array_keys($array);

        foreach ($array_keys as $key) {
            if (!$this->shop_cart->issetRequestParam($key) && $key != 'hash') {
                $array[$key] = '';
            }
        }

        return $array;
    }

    private function checkAllowedIp()
    {
        if (empty($this->bridge_options['allowed_ips'])) {
            return true;
        }

        $allowed_ips = explode(',', $this->bridge_options['allowed_ips']);
        $ip_allowed = false;

        foreach ($allowed_ips as $ip) {
            $ip = trim($ip);
            $str_without_x = $ip;

            if (strpos($ip, 'x') !== false) {
                $str_without_x = $this->shop_cart->subStr($ip, 0, strpos($ip, 'x'));
            }

            if ($this->checkIp($str_without_x) === true) {
                $ip_allowed = true;
                break;
            }
        }

        return $ip_allowed;
    }

    private function checkIp($ip)
    {
        return strpos($this->shop_cart->getRemoteAddress(), $ip) === 0;
    }

    /**
     * Generate database dump
     */
    private function getDbDump()
    {
        $this->dump_data_prev = $this->getDumpData();

        if (!$this->dump_data_prev) {
            $this->setGeneratingDumpValue(
                [
                    self::GET_SQL_CANCEL_PARAM => 0,
                    self::GET_SQL_TABLE        => '',
                    self::GET_SQL_PERCENTAGE   => 0,
                    self::GET_SQL_FILE_PART    => 0
                ]
            );
        } else {
            if ($this->isDumpGenerating()) {
                return [$this->responseKeyOutput => 'Dump is being generated. Could not run next attempt'];
            }

            $this->log_file_reset = true;
        }

        $this->putLog('Initializing');

        // Delete old files, create new and open it for putting data
        $this->openDbFile();

        // Generate database dump
        $result = $this->createDump();

        $this->setGeneratingDumpValue(
            [self::GET_SQL_CANCEL_PARAM => 0, self::GET_SQL_TABLE => '', self::GET_SQL_PERCENTAGE => 0]
        );

        if (true !== $result) {
            return $result;
        }

        // Output generated database dump information
        return $this->downloadDump($this->dump_file_current, $this->bridge_options['allow_compression']);
    }

    private function getDumpData()
    {
        $content = false;
        $file    = $this->tmp_folder_path . '/' . self::DB_DATA_TMP;
        $file_db = $this->tmp_folder_path . '/' . self::DB_FILE_MAIN
            . $this->getPartNumber($this->dump_file_part_number)
            . self::DB_FILE_EXT_COMPRESSION_NO;

        if ($this->shop_cart->fileExists($file)) {
            if ($this->shop_cart->fileExists($file_db) && (time() - $this->shop_cart->filemtime($file_db)) > 600) {
                $this->shop_cart->unlink($file_db);
                return false;
            }

            if (!$this->shop_cart->fileExists($file_db)) {
                return false;
            }

            $content = $this->shop_cart->fileGetContents($file);
            $content = $this->shop_cart->unserialize($content);
        }

        return $content;
    }

    private function isDumpGenerating()
    {
        $file = $this->tmp_folder_path.'/'.self::LOG_FILENAME;

        if ($this->shop_cart->fileExists($file)) {
            $checksum_prev = md5_file($file);
            self::sleepFor(self::DELAY_TO_GENERATE_DUMP);

            if ($checksum_prev != md5_file($file)) {
                return true;
            }
        }

        return false;
    }

    private function setGeneratingDumpValue($arr)
    {
        if (!is_array($arr)) {
            $arr = [$arr];
        }

        $file_data = $this->getGeneratingDumpValueFromFile();

        foreach ($arr as $key => $value) {
            $file_data[$key] = $value;
        }

        return $this->shop_cart->filePutContents(
            $this->tmp_folder_path.'/'.self::FILE_TMP_GET_SQL,
            serialize($file_data)
        );
    }

    private function getGeneratingDumpValue($name)
    {
        $ret    = false;
        $values = $this->getGeneratingDumpValueFromFile();

        if (is_array($name)) {
            $ret = [];

            foreach ($name as $val) {
                if (isset($values[$val])) {
                    $ret[$val] = $values[$val];
                } else {
                    $ret[$val] = '';
                }
            }
        } elseif (isset($values[$name])) {
            $ret = $values[$name];
        }

        return $ret;
    }

    private function getGeneratingDumpValueFromFile()
    {
        $ret = [];
        $file = $this->tmp_folder_path.'/'.self::FILE_TMP_GET_SQL;

        if ($this->shop_cart->fileExists($file)) {
            $content = $this->shop_cart->fileGetContents($file);
            $ret     = $this->shop_cart->unserialize($content);
        }

        return $ret;
    }

    private function getPartNumber($number)
    {
        return str_pad($number, self::FILE_NAME_PART_NUMBER_COUNT_DIGITS, '0', STR_PAD_LEFT);
    }

    private function openDbFile()
    {
        if ($this->bridge_options['allow_compression']) {
            $this->backup_file_ext = 'gz';
        }

        $this->putLog('Creating backup file');
        $this->dump_file_current = self::DB_FILE_MAIN.$this->getPartNumber($this->dump_file_part_number)
            .self::DB_FILE_EXT_COMPRESSION_NO;
        $this->db_file_handler = $this->shop_cart->fileOpen($this->tmp_folder_path.'/'.$this->dump_file_current, 'ab');
    }

    private function createDump()
    {
        $tabinfo  = [0];
        $tabsize  = [];
        $continue = false;

        // Get information about all tables
        $result = $this->getTables();
        if (true !== $result) {
            return $result;
        }

        $result = $this->shop_cart->getSqlResults('SHOW TABLE STATUS');

        if (!$result) {
            return $this->generateError(
                'Error selecting table status. Error: '.$this->shop_cart->error_no.'; '.$this->shop_cart->error_msg
            );
        }

        $tablesAndViews     = array_merge($this->db_tables, $this->db_views);
        $limitQuerySize     = ((int)$this->bridge_options['limit_query_size'] * 1024);
        foreach ($result as $item) {
            if (in_array($item['Name'], $tablesAndViews)) {
                $tableSize      = (int)$item['Data_length'] + (int)$item['Index_length'];
                $tableRows      = (int)(empty($item['Rows']) ? 0 : $item['Rows']);
                $tableAvgSize   = (int)($item['Avg_row_length'] <= 0 ? 1 : $item['Avg_row_length']);

                $item['Rows']   = $tableRows;
                $tabinfo[0]     += $tableRows;
                $tabinfo[$item['Name']] = $tableRows;

                $tabsize[$item['Name']] = ($tableRows == 0 ? 1 : 1 + round($limitQuerySize/$tableAvgSize));
                $this->table_sizes[$item['Name']]['size'] = $tableSize;
                $this->table_sizes[$item['Name']]['rows'] = $tableRows;
                $this->db_size += $tableSize;
            }
        }

        if (!$this->dump_data_prev) {
            $result = $this->shop_cart->getSqlResults(
                "SELECT DEFAULT_CHARACTER_SET_NAME AS charset FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = '"
                .$this->shop_cart->getDbName()."'"
            );

            if (!$result) {
                return $this->generateError(
                    "Error selecting database charset. Error: {$this->shop_cart->error_no};
                    {$this->shop_cart->error_msg}"
                );
            }

            $row = array_shift($result);
            $this->dbFileWrite("ALTER DATABASE CHARACTER SET '{$row['charset']}';\nSET NAMES 'utf8';\n\n");
        }

        $this->shop_cart->execSql('SET SQL_QUOTE_SHOW_CREATE = 1');
        $this->shop_cart->execSql('SET @@session.time_zone = \'+00:00\'');

        // Form database dump file
        foreach ($tablesAndViews as $table) {
            $this->handled_tables[] = $table;

            if ($this->dump_data_prev) {
                if ($this->dump_data_prev['table'] == $table) {
                    $this->putLog('Next attempt of generating dump');
                    $continue = true;
                } elseif (!$continue) {
                    continue;
                }
            }

            $tableDump = $this->generateDumpFromTable($table, $tabinfo, $tabsize);

            if (true !== $tableDump) {
                return $tableDump;
            }
        }

        // Close database dump file
        $this->dbFileClose();
        $this->generateArchive();

        return true;
    }

    private function generateDumpFromTable($table, $tabinfo, $tabsize)
    {
        if (!$this->dump_data_prev || $this->dump_data_prev['table'] != $table) {
            $this->putLog("Handling table `{$table}` [".$this->getFormatedIntNumber($tabinfo[$table]).'].');
        }

        $table_empty = true;
        $result = $this->shop_cart->getSqlResults("SHOW CREATE TABLE `{$table}`", self::NUMERIC);

        if ($result === false) {
            return $this->generateError(
                'Error selecting table structure. Error: '.$this->shop_cart->error_no.'; '
                .$this->shop_cart->error_msg
            );
        }

        $tab = array_shift($result);
        $tab = preg_replace(
            '/(default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP|collate \w+)/i',
            '/*!40101 \\1 */',
            $tab
        );

        if (in_array($table, $this->db_views, true)) {
            $this->dbFileWrite("DROP VIEW IF EXISTS `{$table}`;\n{$tab[1]};\n\n");
        }

        if (in_array($table, $this->db_tables, true) && !in_array($table, $this->db_views, true)) {
            $this->dbFileWrite("DROP TABLE IF EXISTS `{$table}`;\n{$tab[1]};\n\n");
        }

        $numeric_column = [];
        $result = $this->shop_cart->getSqlResults("SHOW COLUMNS FROM `{$table}`", self::NUMERIC);

        if ($result === false) {
            return $this->generateError(
                'Error selecting table columns. Error: '.$this->shop_cart->error_no.'; '
                .$this->shop_cart->error_msg
            );
        }

        $field = 0;
        foreach ($result as $col) {
            $numeric_column[$field++] = preg_match('/^(\w*int|year)/', $col[1]) ? 1 : 0;
        }

        if ($this->dump_data_prev && $this->dump_data_prev['table'] == $table) {
            $from = $this->dump_data_prev['from'];
        } else {
            $from = 0;
        }

        $fields = $field;
        $limit  = $tabsize[$table];
        $i      = 0;

        // Check if table got rows to do limitations
        $limitQueryPart = '';
        if ((int)$tabinfo[$table] > 0) {
            if ($from == 0) {
                $limitQueryPart = 'LIMIT ' . (int)$limit;
            } else {
                $limitQueryPart = 'LIMIT ' . (int)$from . ', ' . (int)$limit;
            }
        }

        $query  = "SELECT * FROM `{$table}`" . $limitQueryPart;
        $result = $this->shop_cart->getSqlResults($query, self::NUMERIC);

        if ($result === false) {
            return $this->generateError(
                "Error selecting data from table `{$table}`. Error: ".$this->shop_cart->error_no.'; '
                .$this->shop_cart->error_msg
            );
        }

        $count_result = count($result);
        if (in_array($table, $this->db_views, true)) {
            $handled_tables[] = $table;
            return true;
        }

        if ($count_result > 0 && in_array($table, $this->db_tables, true)) {
            $this->dbFileWrite("INSERT INTO `{$table}` VALUES");
        }

        while ($result && $count_result > 0) {
            $table_empty = false;
            $this->putLog("-$query");

            foreach ($result as $row) {
                $i++;
                $this->handleDbDumpRow($row, $table, $fields, $numeric_column, $i);
            }

            // Set data of generating database dump progress
            $this->setCreateDbDumpProgress($table, $from);

            $this->putDumpData($table, $from + $limit);

            // Check if generating database dump should be canceled
            if ($this->checkDbDumpCancel()) {
                return [$this->responseKeyOutput => self::GET_SQL_CANCEL_MESSAGE];
            }

            // If store manager needs to get part of dump
            if ($this->getGeneratingDumpValue(self::GET_SQL_FILE_PART)) {
                $this->dbFileClose();
                $this->generateArchive();
                $this->setGeneratingDumpValue(
                    [
                        self::GET_SQL_FILE_PART_NAME => $this->dump_file_current,
                        self::GET_SQL_FILE_PART      => 0
                    ]
                );
                $this->dump_file_part_number++;
                $this->openDbFile();
            }

            if ($count_result < $limit) {
                break;
            }

            $from  += $limit;
            $query  = "SELECT * FROM {$table} LIMIT {$from}, {$limit}";
            $result = $this->shop_cart->getSqlResults($query, self::NUMERIC);

            if ($result === false) {
                return $this->generateError(
                    "Error selecting data from table `{$table}`. Error: ".$this->shop_cart->error_no.'; '
                    .$this->shop_cart->error_msg
                );
            }

            $count_result = count($result);
        }

        // Add table to array of processed tables
        $handled_tables[] = $table;

        if (!$table_empty) {
            $this->dbFileWrite(';');
        }

        $this->dbFileWrite("\n\n");

        return true;
    }

    private function checkDbDumpCancel()
    {
        if (!$this->getGeneratingDumpValue(self::GET_SQL_CANCEL_PARAM)) {
            return false;
        }

        $this->putLog(self::GET_SQL_CANCEL_PARAM);
        $path_sm_tmp_get_sql_txt     = $this->tmp_folder_path . '/' . self::FILE_TMP_GET_SQL;
        $path_dump_data_tmp_txt      = $this->tmp_folder_path . '/' . self::DB_DATA_TMP;
        $path_em1_bridge_db_dump_sql = $this->tmp_folder_path . '/' . self::DB_FILE_MAIN
            . $this->getPartNumber($this->dump_file_part_number)
            . self::DB_FILE_EXT_COMPRESSION_NO;

        if ($this->shop_cart->fileExists($path_sm_tmp_get_sql_txt)) {
            $this->shop_cart->unlink($path_sm_tmp_get_sql_txt);
        }

        if ($this->shop_cart->fileExists($path_dump_data_tmp_txt)) {
            $this->shop_cart->unlink($path_dump_data_tmp_txt);
        }

        if ($this->shop_cart->fileExists($path_em1_bridge_db_dump_sql)) {
            $this->dbFileClose();
            $this->shop_cart->unlink($path_em1_bridge_db_dump_sql);
        }

        return true;
    }

    private function handleDbDumpRow($row, $table, $fields, $numeric_column, $i)
    {
        for ($k = 0; $k < $fields; $k ++) {
            if ($numeric_column[$k]) {
                $row[$k] = isset($row[$k]) ? $row[$k] : 'NULL';
            } else {
                if (isset($row[$k])) {
                    $row[$k] = ' ' . self::QOUTE_CHAR . $this->shop_cart->sanitizeSql($row[$k])
                        . self::QOUTE_CHAR.' ';
                } else {
                    $row[$k] = 'NULL';
                }
            }
        }

        $row_ex = $i == 1 ? '' : ',';

        if ($i % 500 == 0 && $i > 0) {
            $this->dbFileWrite(";\nINSERT INTO `{$table}` VALUES");
            $row_ex = '';
        }

        $this->dbFileWrite($row_ex."\n(".implode(', ', $row).')');
    }

    private function generateArchive()
    {
        if ($this->bridge_options['allow_compression']) {
            $file_gz = self::DB_FILE_MAIN . $this->getPartNumber($this->dump_file_part_number)
                . self::DB_FILE_EXT_COMPRESSION_YES;
            $fname_gz_path = $this->tmp_folder_path . "/$file_gz";
            $fp_gz = $this->shop_cart->gzFileOpen(
                $fname_gz_path,
                "wb{$this->bridge_options['compress_level']}"
            );

            $fname_path = $this->tmp_folder_path . "/$this->dump_file_current";
            $fp         = $this->shop_cart->fileOpen($fname_path, 'r');
            if ($fp_gz && $fp) {
                while (!feof($fp)) {
                    $content = $this->shop_cart->fileRead($fp, $this->bridge_options['package_size']);
                    $this->shop_cart->gzFileWrite($fp_gz, $content);
                }

                $this->shop_cart->fileClose($fp);
                $this->shop_cart->unlink($fname_path);
                $this->shop_cart->gzFileClose($fp_gz);
                $this->dump_file_current = $file_gz;
            }
        }
    }

    private function putDumpData($table, $from)
    {
        $data = [
            'table' => $table,
            'from' => $from,
            self::GET_SQL_FILE_NAME_GENERATING => $this->dump_file_current
        ];

        $this->shop_cart->filePutContents($this->tmp_folder_path.'/'.self::DB_DATA_TMP, serialize($data));
    }

    private function getTables()
    {
        $this->putLog('Selecting tables');
        $result = $this->shop_cart->getSqlResults(
            'SHOW FULL TABLES FROM `' . $this->shop_cart->getDbName() .
            "` WHERE Table_type = 'BASE TABLE' OR Table_type = 'VIEW'",
            self::NUMERIC
        );

        if ($result === false) {
            return $this->generateError(
                "Error selecting tables. Error: {$this->shop_cart->error_no}; {$this->shop_cart->error_msg}"
            );
        }

        $quoted_tbls            = $this->getExcludeDbTablesPattern();
        $tables_exclude_pattern = empty($quoted_tbls) ? '' : implode('|', $quoted_tbls);

        $quoted_tbls            = $this->getIncludeDbTablesPattern();
        $tables_include_pattern = empty($quoted_tbls) ? '' : implode('|', $quoted_tbls);

        $tables = [];
        $inc_tables = 0;
        foreach ($result as $table) {
            if (preg_match("/$tables_include_pattern/", $table[0])) {
                $inc_tables++;
            }

            $tables[] = $table;
        }

        foreach ($tables as $table) {
            if (preg_match('/' . $tables_exclude_pattern . '/', $table[0])) {
                continue;
            }

            if ($inc_tables == 0 || preg_match('/' . $tables_include_pattern . '/', $table[0])) {
                if ($table[1] === 'VIEW') {
                    $this->db_views[] = $table[0];
                    continue;
                }

                $this->db_tables[] = $table[0];
            }
        }

        return true;
    }

    private function getExcludeDbTablesPattern()
    {
        if (!$this->bridge_options['exclude_db_tables']) {
            return [];
        }

        $quoted_tbls = [];
        $arr_exclude_db_tables = explode(';', $this->bridge_options['exclude_db_tables']);
        $count = count($arr_exclude_db_tables);

        for ($i = 0; $i < $count; $i++) {
            $quoted_tbls[] = $this->generateTablePattern($arr_exclude_db_tables[$i]);
        }

        return $quoted_tbls;
    }

    private function getIncludeDbTablesPattern()
    {
        if (!$this->request_params['include_tables']) {
            return [];
        }

        $quoted_tables           = ['^sm_.*$'];
        $arr_include_db_tables = explode(';', $this->request_params['include_tables']);
        $count                 = count($arr_include_db_tables);

        for ($i = 0; $i < $count; $i++) {
            $quoted_tables[] = $this->generateTablePattern($arr_include_db_tables[$i]);
        }

        return $quoted_tables;
    }

    private function generateTablePattern($table)
    {
        $table = preg_quote($table, '/');
        $table = str_replace(array('\*', '\?'), array('.*', '?'), $table);

        return '^' . $this->shop_cart->getDbPrefix() . "$table$";
    }

    private function dbFileWrite($str)
    {
        $this->shop_cart->fileWrite($this->db_file_handler, $str);
    }

    private function dbFileClose()
    {
        $this->shop_cart->fileClose($this->db_file_handler);
    }

    /**
     * Set table name and percentage of processed data in session
     * @param string $handling_table
     * Table name which is being processing at the moment
     * @param int $handled_rows
     * Count of processed rows in table name which is being processing at the moment
     */
    private function setCreateDbDumpProgress($handling_table, $handled_rows)
    {
        $size_handled = 0;

        foreach ($this->handled_tables as $table) {
            $size_handled += $this->table_sizes[$table]['size'];
        }

        if ($handled_rows >= $this->table_sizes[$handling_table]['rows']) {
            $size_handled += $this->table_sizes[$handling_table]['size'];
        } else {
            $size_handled += round(
                $this->table_sizes[$handling_table]['size'] / $this->table_sizes[$handling_table]['rows']
                * $handled_rows,
                0
            );
        }

        $percentage = round($size_handled / $this->db_size * 100, 0);
        $this->setGeneratingDumpValue(
            [self::GET_SQL_TABLE => $handling_table, self::GET_SQL_PERCENTAGE => $percentage]
        );
    }

    /**
     * Form information about database dump file and output it
     */
    private function downloadDump($file_name, $is_compressed)
    {
        $fname = $this->tmp_folder_path.'/'.$file_name;

        if (!$this->shop_cart->fileExists($fname)) {
            $this->putLog('File not exists.');
        }

        if (!$this->shop_cart->isReadable($fname)) {
            $this->putLog('File is not readable.');
        }

        $file_size     = $this->shop_cart->fileSize($fname);
        $file_checksum = md5_file($fname);
        $output      = "0\r\n";

        if ($is_compressed) {
            $output .= '1';
        } else {
            $output .= '0';
        }

        $output .= '|';
        $div_last = $file_size % $this->bridge_options['package_size'];

        if ($div_last == 0) {
            $output .= (($file_size - $div_last) / $this->bridge_options['package_size']);
        } else {
            $output .= (($file_size - $div_last) / $this->bridge_options['package_size'] + 1);
        }

        $output .= "|$file_size";
        $res = $this->shop_cart->getSqlResults('SELECT @@character_set_database AS charset');

        if (!$res) {
            $output .= '';
        } else {
            $res = array_shift($res);
            $output .= '|'.$res['charset'];
        }

        $output .= "\r\n$file_name\r\n$file_checksum\r\n";
        $headers = false;

        if (!headers_sent()) {
            $headers = [
                ['name' => 'Content-Length', 'value' => $this->shop_cart->strLen($output)],
                ['name' => 'Content-Length-Alternative', 'value' => $this->shop_cart->strLen($output)],
                ['name' => 'Cache-Control', 'value' => 'must-revalidate, post-check=0, pre-check=0'],
                ['name' => 'Pragma', 'value' => 'public'],
                ['name' => 'Pragma', 'value' => 'no-cache'],
            ];
        }

        return [$this->responseKeyOutput => $output, $this->responseKeyHeaders => $headers];
    }

    /**
     * Read database dump file and output its data by parts
     */
    private function getDbFile()
    {
        if (!$this->shop_cart->issetRequestParam('filename')) {
            return $this->generateError($this->br_errors['filename_param_missing']);
        }

        if (!$this->shop_cart->issetRequestParam('position')) {
            return $this->generateError($this->br_errors['position_param_missing']);
        }

        $filename = (string)$this->tmp_folder_path.'/'.$this->shop_cart->getRequestParam('filename');
        $position = (int)$this->shop_cart->getRequestParam('position');

        if (!$this->shop_cart->fileExists($filename)) {
            return $this->generateError($this->br_errors['temporary_file_exist_not']);
        }

        if (!$this->shop_cart->isReadable($filename)) {
            return $this->generateError($this->br_errors['temporary_file_readable_not']);
        }

        $output         = '';
        $package_size   = $this->bridge_options['package_size'];
        $filesize       = $this->shop_cart->fileSize($filename);
        $filesize       = $filesize - $position * $package_size;
        $delete_db_file = false;
        $headers        = false;

        if ($filesize > $package_size) {
            $filesize = $package_size;
        }

        if ($filesize < 0) {
            $filesize = 0;
        }

        if ($filesize < $package_size) {
            $delete_db_file = true;
        }

        $fp = $this->shop_cart->fileOpen($filename, 'rb');
        fseek($fp, $package_size * $position);
        $output .= $this->shop_cart->fileRead($fp, $package_size);
        $this->shop_cart->fileClose($fp);

        if ($delete_db_file) {
            if ($this->shop_cart->fileExists($filename)) {
                $this->shop_cart->unlink($filename);
            }

            $file = $this->tmp_folder_path . '/' . self::DB_DATA_TMP;
            if ($this->shop_cart->fileExists($file)) {
                $this->shop_cart->unlink($file);
            }
        }

        if (!headers_sent()) {
            $headers = [
                ['name' => 'Content-Length', 'value' => $this->shop_cart->strLen($output) + $filesize],
                ['name' => 'Content-Length-Alternative', 'value' => $this->shop_cart->strLen($output) + $filesize],
                ['name' => 'Cache-Control', 'value' => 'must-revalidate, post-check=0, pre-check=0'],
                ['name' => 'Pragma', 'value' => 'public'],
                ['name' => 'Pragma', 'value' => 'no-cache'],
            ];
        }

        return [$this->responseKeyOutput => $output, $this->responseKeyHeaders => $headers];
    }

    /**
     * Check data and run SQLs
     */
    private function putSql()
    {
        if (!$this->shop_cart->issetRequestParam('sql')) {
            return $this->generateError($this->br_errors['sql_param_missing']);
        }

        $this->putLog('Put sql. Start');
        $sql = $this->shop_cart->getRequestParam('sql');

        // Put all SQLs into log file
        $this->putLog($sql);

        // Run SQLs
        $output = $this->putSqlRun($sql);

        // Output answer
        if (empty($output)) {
            $output = "0\r\n";
        }

        return [$this->responseKeyOutput => $output];
    }

    private function putSqlRun($data)
    {
        $checksum_prev             = '';
        $this->count_sql_exec_prev = 0;
        $post_replace_to_sm        = array_flip($this->post_replace_from_sm);
        $sql_delimiter_pv          = $this->shop_cart->getRequestParam('sql_delimiter');
        $sql_compatibility_pv      = $this->shop_cart->getRequestParam('sql_compatibility');

        // Read checksum and count of processed SQLs from file
        $checksum_arr = $this->getChecksumPrev();

        // Get encoded string in base64 to check below if data are encoded in base64
        $encoded_data_begin = strtr($this->shop_cart->base64Encode(self::PUT_SQL_ENCODED), $post_replace_to_sm);

        if ($checksum_arr) {
            $checksum_prev = $checksum_arr[0];
            $this->count_sql_exec_prev = $checksum_arr[1];
        }

        if ($sql_delimiter_pv !== false && !empty($sql_delimiter_pv)) {
            $this->sql_delimiter = (string)$this->shop_cart->getRequestParam('sql_delimiter');
        }

        if ($sql_compatibility_pv) {
            $this->shop_cart->execSql("SET SQL_MODE = '".(string)$sql_compatibility_pv."'");
        }

        $checksum_current = str_pad($this->shop_cart->strToUpper(dechex(crc32($data))), 8, '0', STR_PAD_LEFT);

        // Check if chunk checksum from the store manager and checksum of retrieved data are different
        if ($this->shop_cart->issetRequestParam('checksum')
            && $this->shop_cart->getRequestParam('checksum') != $checksum_current
        ) {
            return self::POST_ERROR_CHUNK_CHECKSUM_DIF.'|'.$this->br_errors['checksum_dif'];
        }

        if ($this->shop_cart->issetRequestParam('checksum') && strpos($data, $encoded_data_begin) === 0) {
            $data = $this->shop_cart->subStr($data, $this->shop_cart->strLen($encoded_data_begin));
            $data = $this->shop_cart->base64Decode(strtr($data, $this->post_replace_from_sm));
        }

        $ret = $this->runSqls(explode($this->sql_delimiter, $data), $checksum_current, $checksum_prev);

        return $ret == '' && $this->shop_cart->issetRequestParam('checksum')
            ? $this->successful_code.'|Data were posted successfully'
            : $ret;
    }

    private function runSqls($sql_queries, $checksum_current, $checksum_prev)
    {
        $ret = '';

        if ($this->shop_cart->issetRequestParam('checksum')) {
            if ($checksum_current != $checksum_prev) {
                foreach ($sql_queries as $query) {
                    $query = trim($query);

                    if (!empty($query)) {
                        if ($ret == '') {
                            $ret .= $this->importRunQuery($query, $checksum_current);
                        }
                    } elseif (empty($query)) {
                        break;
                    }
                }
            } else {
                foreach ($sql_queries as $key => $query) {
                    $query = trim($query);

                    if (!empty($query) && $ret == '' && $key >= $this->count_sql_exec_prev) {
                        $ret .= $this->importRunQuery($query, $checksum_current);
                    }
                }
            }
        } else {
            foreach ($sql_queries as $query) {
                $query = trim($query);

                if (!empty($query)) {
                    $ret .= $this->importRunQuery($query);
                }
            }
        }

        return $ret;
    }

    private function getChecksumPrev()
    {
        $checksum_arr = false;
        $file_name_intermediate = $this->tmp_folder_path.'/'.self::INTERMEDIATE_FILE_NAME;

        if ($this->shop_cart->fileExists($file_name_intermediate)) {
            $fp = $this->shop_cart->fileOpen($file_name_intermediate, 'r');

            if ($fp) {
                $file_size_intermediate = $this->shop_cart->fileSize($file_name_intermediate);

                if ($file_size_intermediate > 0) {
                    $content = $this->shop_cart->fileRead($fp, $file_size_intermediate);
                    $checksum_arr = explode('|', $content);

                    if (count($checksum_arr) == 2) {
                        $checksum_arr[0] = (string)$checksum_arr[0];
                        $checksum_arr[1] = (int)$checksum_arr[1];

                        if ($checksum_arr[1] < 0) {
                            $checksum_arr[1] = 0;
                        }
                    } else {
                        $checksum_arr = false;
                    }
                }

                $this->shop_cart->fileClose($fp);
            }
        }

        return $checksum_arr;
    }

    /**
     * Run one SQL and put data into file
     */
    private function importRunQuery($query, $checksum = '')
    {
        $ret = '';
        $this->putLog($query);
        $result = $this->shop_cart->execSql($query);

        // Error Code: 2006 - MySQL server has gone away; Error Code: 1317 - Query execution was interrupted
        if (!$result && ($this->shop_cart->error_no == 2006 || $this->shop_cart->error_no == 1317)) {
            $result = $this->retryPutSql($query);
        }

        if ($result) {
            if ($this->shop_cart->issetRequestParam('checksum')) {
                $this->shop_cart->filePutContents(
                    $this->tmp_folder_path.'/'.self::INTERMEDIATE_FILE_NAME,
                    $checksum.'|'.(++$this->count_sql_exec_prev)
                );
            }
        } else {
            $ret .= self::POST_ERROR_SQL_INDEX.'|'.($this->count_sql_exec_prev + 1).'|<font color="#000000"><b>'
                .$this->shop_cart->error_no.'; '.$this->shop_cart->error_msg.'</b></font><br>'.htmlspecialchars($query)
                .'<br>';

            if ($this->shop_cart->issetRequestParam('checksum')) {
                $this->shop_cart->filePutContents(
                    $this->tmp_folder_path.'/'.self::INTERMEDIATE_FILE_NAME,
                    $checksum.'|'.$this->count_sql_exec_prev
                );
            }
        }

        return $ret;
    }

    private function retryPutSql($query)
    {
        $result = false;

        for ($i = 0; $i < self::MAX_COUNT_ATTEMPT_POST; $i++) {
            self::sleepFor(self::DELAY_BETWEEN_POST);
            $result = $this->shop_cart->execSql($query);

            if ($result || ($this->shop_cart->error_no != 2006 && $this->shop_cart->error_no != 1317)) {
                break;
            }
        }

        return $result;
    }

    private function getModuleVersion()
    {
        $output = $this->shop_cart->jsonEncode(
            [
                $this->code_response => $this->successful_code,
                'revision'           => $this->revision,
                'module_version'     => $this->module_version,
            ]
        );

        return [$this->responseKeyOutput => $output];
    }

    private function getConfig()
    {
        $output = "0\r\ndatabase_host=".$this->shop_cart->getDbHost()."<br>\r\ndatabase_name="
            .$this->shop_cart->getDbName()."<br>\r\ndatabase_username=".$this->shop_cart->getDbUsername()
            ."<br>\r\ndatabase_password=".$this->shop_cart->getDbPassword()."<br>\r\ndatabase_table_prefix="
            .$this->shop_cart->getDbPrefix()."<br>\r\nphp_version=".phpversion()."<br>\r\ngzip="
            .(int)extension_loaded('zlib')."<br>\r\n";

        if (defined('VM_VERSION')) {
            $output .= 'vm_version='.VM_VERSION."<br>\r\n";
        }

        return [$this->responseKeyOutput => $output];
    }

    private function getCategoryTree()
    {
        if (!$this->shop_cart->issetRequestParam('category')) {
            $this->putLog('Error: Category name is empty');
            return $this->generateError($this->br_errors['category_param_missing']);
        }

        $category = $this->shop_cart->getRequestParam('category');
        if (empty($category)) {
            $this->putLog('Error: Category name is empty');
            return $this->generateError($this->br_errors['category_param_empty']);
        }

        $dir = (string)$this->shop_cart->getRequestParam('category');
        if (empty($category)) {
            $this->putLog('Error: Category name is empty');
            $this->generateError($this->br_errors['category_param_empty']);
        }

        $tmp_dir_info = dir($this->tmp_folder_path);

        while (false !== ($entry = $tmp_dir_info->read())) {
            if ($entry != '.'
                && $entry != '..'
                && $this->shop_cart->subStr(
                    $entry,
                    0,
                    $this->shop_cart->strLen(self::TMP_FILE_PREFIX)
                ) == self::TMP_FILE_PREFIX
            ) {
                $this->shop_cart->unlink($this->tmp_folder_path.'/'.$entry);
            }
        }

        $tmpfname = $this->shop_cart->strToLower(self::TMP_FILE_PREFIX.date('H_i_s-d_M_Y'));
        $this->putLog('Creating and opening tmp file for get category path');

        if ($this->bridge_options['allow_compression']) {
            $tmpfname .= '.txt.gz';
            $tmpfd = $this->shop_cart->gzFileOpen(
                $this->tmp_folder_path.'/'.$tmpfname,
                'wb'.$this->bridge_options['compress_level']
            );
        } else {
            $tmpfname .= '.txt';
            $tmpfd = $this->shop_cart->fileOpen($this->tmp_folder_path.'/'.$tmpfname, 'wb');
        }

        if (!$tmpfd) {
            $this->putLog('Error creating and opening tmp file');
            return $this->generateError($this->br_errors['open_tmp_file']);
        }

        // Creates ignore directory list
        $arr_ignore_dir = [];
        if ($this->shop_cart->issetRequestParam('ignore_dir')) {
            $ignore_dir = (string)$this->shop_cart->getRequestParam('ignore_dir');

            if (!empty($ignore_dir)) {
                $arr_ignore_dir = explode(';', $ignore_dir);
            }
        }

        $files = $this->getFilesRecursively($dir, $arr_ignore_dir, false, '*', true);

        foreach ($files as $file) {
            $this->bridge_options['allow_compression']
                ? $this->shop_cart->gzFileWrite($tmpfd, "$file\r\n")
                : $this->shop_cart->fileWrite($tmpfd, "$file\r\n");
        }

        $this->bridge_options['allow_compression']
            ? $this->shop_cart->gzFileClose($tmpfd)
            : $this->shop_cart->fileClose($tmpfd);

        return $this->generateFileData($tmpfname, $this->bridge_options['allow_compression']);
    }

    private function runIndexer()
    {
        return [$this->responseKeyOutput => $this->shop_cart->runIndexer()];
    }

    /**
     * Get values of variables from FTP files
     */
    private function getVars()
    {
        if (!$this->shop_cart->issetRequestParam('vars_main_dir')) {
            return $this->generateError($this->br_errors['varsmaindir_param_missing']);
        }

        if (!$this->shop_cart->issetRequestParam('vars_names')) {
            return $this->generateError($this->br_errors['varsnames_param_missing']);
        }

        $translations  = [];
        $vars_main_dir = (string)$this->shop_cart->getRequestParam('vars_main_dir');
        $vars_main_dir = trim('\\', trim('/', $vars_main_dir));
        $vars_main_dir = $this->shop_cart->getShopRootDir().'/'.$vars_main_dir;
        $vars_names    = (string)$this->shop_cart->getRequestParam('vars_names');

        if ($vars_main_dir == '') {
            return $this->generateError($this->br_errors['varsmaindir_param_empty']);
        }

        if ($vars_names == '') {
            return $this->generateError($this->br_errors['varsnames_param_empty']);
        }

        foreach ($this->shop_cart->readDirectory($vars_main_dir) as $item) {
            if ($this->shop_cart->subStr($item, 0, 1) != '.' && !$this->shop_cart->isDirectory($item)) {
                $translations[(string)$item] = $this->getVarsFromScript($vars_main_dir.'/'.$item, $vars_names);
            }
        }

        return [$this->responseKeyOutput => '1|' . $this->shop_cart->jsonEncode($translations)];
    }

    /**
     * Get values of variables from FTP file
     */
    private function getVarsFromScript($path_to_script, $vars_names)
    {
        if ($this->shop_cart->fileExists("./$path_to_script")
            && $this->shop_cart->isReadable("./$path_to_script")
            && $this->shop_cart->fileSize("./$path_to_script") > 0
        ) {
            $current_translations = [];
            $content = $this->shop_cart->fileGetContents("./$path_to_script");

            if (!$content) {
                return $this->generateError("Cannot open file: $path_to_script");
            }

            $rows     = explode("\n", $content);
            $pattern = '/^\$\_\[\'(.*)\']\s*\=\s*(.*)\;/i';

            foreach ($rows as $data) {
                preg_match($pattern, $data, $matches);

                if (in_array($matches[1], $vars_names) && isset($matches[2]) && $matches[2] != null) {
                    $current_translations[$matches[1]] = $matches[2];
                }
            }

            return $current_translations;
        }

        return '';
    }

    /**
     * Get data from .xml file on FTP server
     */
    private function getXmlData()
    {
        $issetParams = $this->checkIssetParams(['xml_path', 'xml_fields', 'xml_items_node', 'xml_items_info_node']);

        if (true !== $issetParams) {
            return $issetParams;
        }

        $xml_path            = (string)$this->shop_cart->getRequestParam('xml_path');
        $xml_fields          = (string)$this->shop_cart->getRequestParam('xml_fields');
        $xml_items_node      = (string)$this->shop_cart->getRequestParam('xml_items_node');
        $xml_items_info_node = (string)$this->shop_cart->getRequestParam('xml_items_info_node');
        $xml_filters         = [];
        $xml_filters_pv      = $this->shop_cart->getRequestParam('xml_filters');

        if (empty($xml_path)) {
            return $this->generateError($this->br_errors['xmlpath_param_empty']);
        }

        if ($xml_fields == '') {
            return $this->generateError($this->br_errors['xmlfields_param_empty']);
        }

        if ($xml_items_node == '') {
            return $this->generateError($this->br_errors['xmlitemsnode_param_empty']);
        }

        if ($xml_items_info_node == '') {
            return $this->generateError($this->br_errors['xmlitemsinfonode_param_empty']);
        }

        if ($xml_filters_pv !== false && !empty($xml_filters_pv)) {
            $xml_filters = explode(';', (string)$xml_filters_pv);
        }

        $path_xml_file = $this->shop_cart->getShopRootDir().'/'.$xml_path;

        if (!$this->shop_cart->fileExists($path_xml_file)) {
            return $this->generateError("File {$xml_path} not found!");
        }

        return $this->getItemsList($path_xml_file, $xml_fields, $xml_items_node, $xml_items_info_node, $xml_filters);
    }

    private function checkIssetParams(array $params)
    {
        foreach ($params as $i => $paramValue) {
            if (!$this->shop_cart->issetRequestParam($paramValue)) {
                return $this->generateError($this->br_errors[str_replace('_', '', $paramValue) . '_param_missing']);
            }
        }

        return true;
    }

    /**
     * Get value of nodes from .xml file
     */
    private function getItemsList($path_xml_file, $fields, $items_node, $items_info_node, $filters)
    {
        $items_list      = [];
        $filters_matched = [];
        // Bug here -> https://bugs.php.net/bug.php?id=62577 using similar call
        // $xml          = simplexml_load_file($path_xml_file);
        $xml             = simplexml_load_string(file_get_contents($path_xml_file));

        foreach ($filters as $filter) {
            preg_match('/^(.*)\:(.*)$/', $filter, $matches);
            $filters_matched[$matches[1]] = $matches[2];
        }

        $count_filters_matched  = count($filters_matched);
        $fields                 = explode(',', $fields);
        $items                  = $xml->xpath((string)($items_node));
        $items_keys             = reset($items);

        /** @var \SimpleXMLElement $item */
        foreach ($items_keys as $item) {
            if ($items_node != $items_info_node) {
                $items_info = $xml->xpath("{$items_info_node}/{$item->getName()}");
                $items_info = reset($items_info);
            } else {
                $items_info = $item;
            }

            if ($count_filters_matched > 0) {
                foreach ($filters_matched as $filter_name => $filter_value) {
                    if ((string)$items_info->$filter_name != $filter_value) {
                        continue 2;
                    }
                }
            }

            foreach ($fields as $field) {
                $items_list[$item->getName()][$field] = (string)$items_info->$field;
            }
        }

        return [$this->responseKeyOutput => '1|' . $this->shop_cart->jsonEncode($items_list) . "\r\n"];
    }

    /**
     * Get file structure from FTP server by path
     */
    private function getFtpFiles()
    {
        if (!$this->shop_cart->issetRequestParam('search_path')) {
            return $this->generateError($this->br_errors['searchpath_param_missing']);
        }

        $path           = (string)$this->shop_cart->getRequestParam('search_path');
        $mask           = '*';
        $arr_ignore_dir = [];

        if ($path == '') {
            return $this->generateError($this->br_errors['searchpath_param_empty']);
        }

        $path = $this->shop_cart->getShopRootDir().'/'.$path;

        if ($this->shop_cart->issetRequestParam('ignore_dir')) {
            $ignore_dir = (string)$this->shop_cart->getRequestParam('ignore_dir');

            if ($ignore_dir != '') {
                $arr_ignore_dir = explode(';', $ignore_dir);
            }
        }

        if ($this->shop_cart->issetRequestParam('mask')) {
            $mask = (string)$this->shop_cart->getRequestParam('mask');
        }

        $include_subdir = $this->shop_cart->issetRequestParam('include_subdir')
            && (int)$this->shop_cart->getRequestParam('include_subdir') == 1
            ? true
            :false;
        $files = $this->getFilesRecursively($path, $arr_ignore_dir, true, $mask, $include_subdir);
        $output = $this->shop_cart->jsonEncode(
            [
                $this->shop_cart->getCodeResponse() => $this->shop_cart->getCodeSuccessful(),
                $this->shop_cart->getKeyMessage()   => $files,
            ]
        );

        return [$this->responseKeyOutput => $output];
    }

    /**
     * @param $path
     * @param array $arr_ignore_dir
     * @param bool|true $ignore_dir_has_full_root_path
     * @param string $mask
     * @param bool|false $include_subdir
     * @param array $skip
     * @param array $files
     * @return array
     */
    private function getFilesRecursively(
        $path,
        $arr_ignore_dir = [],
        $ignore_dir_has_full_root_path = true,
        $mask = '*',
        $include_subdir = false,
        $skip = ['.', '..'],
        $files = []
    ) {
        if ($this->shop_cart->isReadable($path)) {
            foreach ($this->shop_cart->search($path, $mask) as $file) {
                if ($this->shop_cart->isFile($file)) {
                    $files[] = $file;
                }
            }

            if ($include_subdir) {
                foreach ($this->shop_cart->search($path, '*', true) as $directory) {
                    if (in_array($directory, $arr_ignore_dir)) {
                        continue;
                    }

                    $files = $this->getFilesRecursively(
                        $directory,
                        $arr_ignore_dir,
                        $ignore_dir_has_full_root_path,
                        $mask,
                        $include_subdir,
                        $skip,
                        $files
                    );
                }
            }
        }

        return $files;
    }

    private function getCartVersion()
    {
        return [$this->responseKeyOutput => $this->shop_cart->getCartVersion()];
    }

    private function checkDataChanges()
    {
        if (!$this->shop_cart->issetRequestParam('table_name')) {
            return $this->generateError($this->br_errors['tablename_param_missing']);
        }

        $table_name = (string)$this->shop_cart->getRequestParam('table_name');

        if (empty($table_name)) {
            return $this->generateError($this->br_errors['tablename_param_empty']);
        }

        return [
            $this->responseKeyOutput => $this->shop_cart->checkDataChanges(
                explode(';', $this->shop_cart->base64Decode($table_name))
            )
        ];
    }

    private function getNewOrders()
    {
        if (!$this->shop_cart->issetRequestParam('order_id')) {
            return $this->generateError($this->br_errors['orderid_param_missing']);
        }

        $order_id = (int)$this->shop_cart->getRequestParam('order_id');

        if ($order_id < 1) {
            return $this->generateError($this->br_errors['orderid_param_incorrect']);
        }

        return [$this->responseKeyOutput => $this->shop_cart->getNewOrders($order_id)];
    }

    /**
     * Set value in session to cancel of generating database dump
     */
    private function createDbDumpCancel()
    {
        return [$this->responseKeyOutput => $this->setGeneratingDumpValue([self::GET_SQL_CANCEL_PARAM => 1])];
    }

    /**
     * Get information about state of generating database dump
     */
    private function createDbDumpProgress()
    {
        $ret    = [];
        $output = '0|';

        $ret['table']      = $this->getGeneratingDumpValue(self::GET_SQL_TABLE);
        $ret['percentage'] = $this->getGeneratingDumpValue(self::GET_SQL_PERCENTAGE);

        if ($ret['table'] !== false && $ret['percentage'] !== false) {
            $output = '1|'.$this->shop_cart->jsonEncode($ret);
        }

        return [$this->responseKeyOutput => $output];
    }

    private function getSqlFilePartInfo()
    {
        $this->setGeneratingDumpValue([self::GET_SQL_FILE_PART => 1]);
        $output = [$this->responseKeyOutput => 'Cannot give a file'];

        for ($i = 0; $i < 10; $i++) {
            self::sleepFor(10);
            $file_part = $this->getGeneratingDumpValue(self::GET_SQL_FILE_PART_NAME);

            if ($file_part && !empty($file_part)) {
                $is_compressed = false;

                if (preg_match('/.gz$/', $file_part)) {
                    $is_compressed = true;
                }

                $output = $this->downloadDump($file_part, $is_compressed);
                $this->setGeneratingDumpValue([self::GET_SQL_FILE_PART_NAME => '']);
            }
        }

        return $output;
    }

    private function getImage()
    {
        if (!$this->shop_cart->issetRequestParam('entity_type')) {
            return $this->generateError($this->br_errors['entitytype_param_missing']);
        }

        if (!$this->shop_cart->issetRequestParam('image_id')) {
            return $this->generateError($this->br_errors['imageid_param_missing']);
        }

        $entity_type = (string)$this->shop_cart->getRequestParam('entity_type');
        $image_id    = (string)$this->shop_cart->getRequestParam('image_id');

        if (!isset($entity_type[0])) {
            return $this->generateError($this->br_errors['entitytype_param_empty']);
        }

        if (empty($image_id)) {
            return $this->generateError($this->br_errors['imageid_param_incorrect']);
        }

        $image_path = $this->shop_cart->getImage($entity_type, $image_id);
        $headers    = false;

        if ($image_path && $this->shop_cart->fileExists($image_path)) {
            $file_size = $this->shop_cart->fileSize($image_path);
            $output    = $this->shop_cart->fileRead($this->shop_cart->fileOpen($image_path, 'rb'), $file_size);

            $headers = [
                ['name' => 'Content-Type', 'value' => 'image/jpeg'],
                ['name' => 'Content-Length', 'value' => $file_size],
            ];
        } else {
            $output = 'Image is missing';
        }

        return [$this->responseKeyOutput => $output, $this->responseKeyHeaders => $headers];
    }

    private function setImage()
    {
        if (!$this->shop_cart->issetRequestParam('entity_type')) {
            return $this->generateError($this->br_errors['entitytype_param_missing']);
        }

        if (!$this->shop_cart->issetRequestParam('image_id')) {
            return $this->generateError($this->br_errors['imageid_param_missing']);
        }

        $entity_type = (string)$this->shop_cart->getRequestParam('entity_type');
        $image_id    = (string)$this->shop_cart->getRequestParam('image_id');

        if (!isset($entity_type[0])) {
            return $this->generateError($this->br_errors['entitytype_param_empty']);
        }

        if (empty($image_id)) {
            return $this->generateError($this->br_errors['imageid_param_incorrect']);
        }

        if ($this->shop_cart->issetRequestParam('image_url')) {
            $image_url = (string)$this->shop_cart->getRequestParam('image_url');

            if (empty($image_url)) {
                return $this->generateError($this->br_errors['imageurl_param_empty']);
            }

            $output = $this->shop_cart->setImage($entity_type, $image_id, $image_url, $this->image_url);
        } else {
            $uploaded_file = $this->shop_cart->getUploadedFileInfo(self::UPLOAD_FILE_NAME);

            if ($uploaded_file['error']) {
                return $this->generateError($this->br_errors['upload_file_error']);
            }

            $output = $this->shop_cart->setImage(
                $entity_type,
                $image_id,
                self::UPLOAD_FILE_NAME,
                self::UPLOAD_FILE_NAME
            );
        }

        return [$this->responseKeyOutput => $output];
    }

    private function deleteImage()
    {
        if (!$this->shop_cart->issetRequestParam('entity_type')) {
            return $this->generateError($this->br_errors['entitytype_param_missing']);
        }

        if (!$this->shop_cart->issetRequestParam('image_id')) {
            return $this->generateError($this->br_errors['imageid_param_missing']);
        }

        $entity_type = (string)$this->shop_cart->getRequestParam('entity_type');

        if (!isset($entity_type[0])) {
            return $this->generateError($this->br_errors['entitytype_param_empty']);
        }

        return [
            $this->responseKeyOutput => $this->shop_cart->deleteImage(
                $entity_type,
                $this->shop_cart->getRequestParam('image_id')
            )
        ];
    }

    private function deleteFile()
    {
        if (!$this->shop_cart->issetRequestParam('path')) {
            return $this->generateError($this->br_errors['path_param_missing']);
        }

        $filepath = (string)$this->shop_cart->getRequestParam('path');

        if (empty($filepath)) {
            return $this->generateError($this->br_errors['path_param_empty']);
        }

        $filepath = $this->shop_cart->getShopRootDir().'/'.$filepath;

        if (!$this->shop_cart->fileExists($filepath)) {
            return $this->generateError($this->br_errors['delete_file_error']);
        }

        return [$this->responseKeyOutput => $this->shop_cart->deleteFile($filepath)];
    }

    private function copyImage()
    {
        if (!$this->shop_cart->issetRequestParam('entity_type')) {
            return $this->generateError($this->br_errors['entitytype_param_missing']);
        }

        if (!$this->shop_cart->issetRequestParam('image_id')) {
            return $this->generateError($this->br_errors['imageid_param_missing']);
        }

        if (!$this->shop_cart->issetRequestParam('to_image_id')) {
            return $this->generateError($this->br_errors['toimageid_param_missing']);
        }

        $entity_type   = (string)$this->shop_cart->getRequestParam('entity_type');
        $from_image_id = (int)$this->shop_cart->getRequestParam('image_id');
        $to_image_id   = (int)$this->shop_cart->getRequestParam('to_image_id');

        if (!isset($entity_type[0])) {
            return $this->generateError($this->br_errors['entitytype_param_empty']);
        }

        if ($from_image_id < 1) {
            return $this->generateError($this->br_errors['imageid_param_incorrect']);
        }

        if ($to_image_id < 1) {
            return $this->generateError($this->br_errors['toimageid_param_incorrect']);
        }

        return [$this->responseKeyOutput => $this->shop_cart->copyImage($entity_type, $from_image_id, $to_image_id)];
    }

    private function getFile()
    {
        if (!$this->shop_cart->issetRequestParam('entity_type')) {
            return $this->generateError($this->br_errors['entitytype_param_missing']);
        }

        if (!$this->shop_cart->issetRequestParam('filename')) {
            return $this->generateError($this->br_errors['filename_param_missing']);
        }

        $entity_type = (string)$this->shop_cart->getRequestParam('entity_type');
        $filename    = (string)$this->shop_cart->getRequestParam('filename');

        if (empty($entity_type)) {
            return $this->generateError($this->br_errors['entitytype_param_empty']);
        }

        if (empty($filename)) {
            return $this->generateError($this->br_errors['filename_param_empty']);
        }

        $file_path = $this->shop_cart->getFile($entity_type, $filename);
        $headers   = false;

        if ($file_path && $this->shop_cart->fileExists($file_path)) {
            $file_size = $this->shop_cart->fileSize($file_path);
            $output    = $this->shop_cart->fileRead($this->shop_cart->fileOpen($file_path, 'r'), $file_size);
            $headers   = [['name' => 'Content-Length', 'value' => $file_size]];
        } else {
            $output = 'File is missing';
        }

        return [$this->responseKeyOutput => $output, $this->responseKeyHeaders => $headers];
    }

    private function setFile()
    {
        if (!$this->shop_cart->issetRequestParam('entity_type')) {
            return $this->generateError($this->br_errors['entitytype_param_missing']);
        }

        $entity_type = (string)$this->shop_cart->getRequestParam('entity_type');
        if (empty($entity_type)) {
            return $this->generateError($this->br_errors['entitytype_param_empty']);
        }

        $uploaded_file = $this->shop_cart->getUploadedFileInfo(self::UPLOAD_FILE_NAME);
        if ($uploaded_file['error']) {
            return $this->generateError($this->br_errors['upload_file_error']);
        }

        return [
            $this->responseKeyOutput => $this->shop_cart->setFile($entity_type, self::UPLOAD_FILE_NAME)
        ];
    }

    private function getCache()
    {
        return [$this->responseKeyOutput => $this->shop_cart->getCache()];
    }

    private function clearCache()
    {
        return [$this->responseKeyOutput => $this->shop_cart->clearCache()];
    }

    private function getPaymentAndShippingMethods()
    {
        $this->shop_cart->getPaymentAndShippingMethods();
    }

    private function getStoreFileArchive()
    {
        if (!$this->shop_cart->isWritable($this->tmp_folder_path)) {
            return $this->generateError($this->br_errors['not_writeable_dir']);
        }

        if (!extension_loaded('zip')) {
            return $this->generateError($this->br_errors['zip_not_loaded']);
        }

        $result       = false;
        $file         = "$this->tmp_folder_path/emagicone_store.zip";
        $ignoreDir    = $this->shop_cart->getRequestParam('ignore_dir');
        $arrIgnoreDir = !empty($ignoreDir) ? explode(';', $ignoreDir) : [];

        if ($this->shop_cart->fileExists($file)) {
            $this->shop_cart->unlink($file);
        }

        /** @var \ZipArchive $zipObj */
        $zipObj = $this->shop_cart->getZipArchiveInstance();

        if ($zipObj->open($file, $this->shop_cart->getZipArchiveCreateValue()) === true) {
            $storeRootDir = $this->shop_cart->getShopRootDir();
            $this->generateFileArchive(
                $zipObj,
                $storeRootDir,
                $this->shop_cart->strLen($storeRootDir),
                $arrIgnoreDir
            );
            $zipObj->close();
            $result = $this->generateFileData($file, true);
        }

        return $result ? $result : $this->generateError($this->br_errors['cannot_archive_files']);
    }

    private function generateFileArchive($zipObj, $path, $storeRootDirLength, $arrIgnoreDir)
    {
        $skip = ['.', '..'];

        if ($fp = opendir($path)) {
            while (false !== ($value = readdir($fp))) {
                $item = "$path/$value";

                /** @var \ZipArchive $zipObj*/
                if ($this->shop_cart->isFile($item)) {
                    $zipObj->addFile($item, $this->shop_cart->subStr($item, $storeRootDirLength + 1));
                } elseif ($this->shop_cart->isDirectory($item)
                    && !in_array($value, $skip)
                    && !in_array($this->shop_cart->subStr($item, $storeRootDirLength), $arrIgnoreDir)
                ) {
                    $this->generateFileArchive($zipObj, $item, $storeRootDirLength, $arrIgnoreDir);
                }
            }

            closedir($fp);
        }
    }

    private function generateFileData($file, $allowCompression)
    {
        $file_size = $this->shop_cart->fileSize("$this->tmp_folder_path/" . $file);
        $output = "0\r\n" . ($allowCompression ? '1' : '0') . '|';
        $div_last = $file_size % $this->bridge_options['package_size'];
        $output .= $div_last == 0
            ? ($file_size / $this->bridge_options['package_size'])
            : (($file_size - $div_last) / $this->bridge_options['package_size'] + 1);
        $output .= "|$file_size|\r\n" . basename($file) . "\r\n" . md5_file("$this->tmp_folder_path/" . $file);

        $headers = false;
        if (!headers_sent()) {
            $headers = [
                ['name' => 'Content-Length', 'value' => $this->shop_cart->strLen($output)],
                ['name' => 'Cache-Control', 'value' => 'must-revalidate, post-check=0, pre-check=0'],
                ['name' => 'Pragma', 'value' => 'public'],
                ['name' => 'Pragma', 'value' => 'no-cache'],
            ];
        }

        return [$this->responseKeyOutput => $output, $this->responseKeyHeaders => $headers];
    }

    private function generateError($err_text = '1', $show_link = false)
    {
        if ($show_link) {
            $output = "1\r\n$err_text";
        } else {
            $output = $this->shop_cart->jsonEncode(
                [
                    $this->code_response => $this->error_code_common,
                    $this->key_message   => $err_text,
                ]
            );
        }

        return [$this->responseKeyOutput => $output];
    }

    private function putLog($data)
    {
        if (!$this->log_file_reset) {
            $log_file_handler = $this->shop_cart->fileOpen($this->tmp_folder_path.'/'.self::LOG_FILENAME, 'w');
            $this->log_file_reset = true;
        } else {
            $log_file_handler = $this->shop_cart->fileOpen($this->tmp_folder_path.'/'.self::LOG_FILENAME, 'a');
        }

        fwrite($log_file_handler, '[' . date('r') . "]$data\r\n");
        $this->shop_cart->fileClose($log_file_handler);
    }

    private function runSelfTest()
    {
        return [$this->responseKeyOutput => $this->shop_cart->jsonEncode([$this->key_message => 'test ok'])];
    }

    private function isSessionKeyValid($key)
    {
        return $this->shop_cart->isSessionKeyValid($key);
    }

    private function addFailedAttempt()
    {
        $result = $this->shop_cart->addFailedAttempt();

        if ($result) {
            self::setDelay((int)$result);
        }
    }

    private function generateSessionKey($hash)
    {
        return $this->shop_cart->generateSessionKey($hash);
    }

    private function deleteSessionKey()
    {
        if (!$this->shop_cart->issetRequestParam('key')) {
            return;
        }

        $key = (string)$this->shop_cart->getRequestParam('key');

        if (!$key) {
            return;
        }

        $this->shop_cart->deleteSessionKey($key);
    }

    private function clearOldData()
    {
        $this->shop_cart->clearOldData();
    }

    private static function sleepFor($seconds)
    {
        $start_time = time();

        while (true) {
            if ((time() - $start_time) > $seconds) {
                return;
            }
        }
    }

    private static function setDelay($count_attempts)
    {
        if ($count_attempts <= 10) {
            self::sleepFor(1);
        } elseif ($count_attempts <= 20) {
            self::sleepFor(2);
        } elseif ($count_attempts <= 50) {
            self::sleepFor(5);
        } else {
            self::sleepFor(10);
        }
    }

    /**
     * Get formatted number
     */
    private function getFormatedIntNumber($num)
    {
        return number_format($num, 0, ',', ' ');
    }

    public function getResponse()
    {
        return $this->response;
    }
}
