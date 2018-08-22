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

namespace Emagicone\Bridgeconnector\Controller\Adminhtml\Settings;

use Emagicone\Bridgeconnector\Helper\Constants;
use Emagicone\Bridgeconnector\Helper\Tools;

/**
 * Class Save
 * @package Emagicone\Bridgeconnector\Controller\Adminhtml\Settings
 */
class Save extends \Magento\Backend\App\Action
{
    private function getPreparedSettingsToStore($settings)
    {
        $storedSettings = Tools::getStoredSettings();

        $dataToStore = [
            'login' => $settings['login'],
            'password' => $storedSettings['password'] == $settings['password']
                ? Tools::getDecryptedData($settings['password'])
                : $settings['password'],
            // trim '/', '\', '.' from begin and end
            'tmp_dir' => '/' . preg_replace('/^[\/|\\\|\.]*|[\/|\\\|\.]*$/', '', $settings['tmp_dir']),
            'allow_compression' => isset($settings['allow_compression']) ? 1 : 0,
            'allowed_ips' => isset($settings['allowed_ips']) ? $settings['allowed_ips'] : '',
            'compress_level' => (int)$settings['compress_level'],
            'limit_query_size' => (int)$settings['limit_query_size'],
            'package_size' => (int)$settings['package_size'],
            'last_clear_date' => $storedSettings['last_clear_date'],
            'exclude_db_tables' =>
                isset($settings['bridgeconnector_exclude_db_tables_checked'])
                    && !empty($settings['bridgeconnector_exclude_db_tables_checked'])
                ? implode(';', $settings['bridgeconnector_exclude_db_tables_checked'])
                : '',
        ];

        $dataToStore['bridge_hash'] = md5($dataToStore['login'] . $dataToStore['password']);
        $dataToStore['password'] = Tools::getEncryptedData($dataToStore['password']);

        if ($dataToStore['compress_level'] < Constants::MIN_COMPRESS_LEVEL) {
            $dataToStore['compress_level'] = Constants::MIN_COMPRESS_LEVEL;
        } elseif ($dataToStore['compress_level'] > Constants::MAX_COMPRESS_LEVEL) {
            $dataToStore['compress_level'] = Constants::MAX_COMPRESS_LEVEL;
        }

        if ($dataToStore['limit_query_size'] < Constants::MIN_LIMIT_QUERY_SIZE) {
            $dataToStore['limit_query_size'] = Constants::MIN_LIMIT_QUERY_SIZE;
        } elseif ($dataToStore['limit_query_size'] > Constants::MAX_LIMIT_QUERY_SIZE) {
            $dataToStore['limit_query_size'] = Constants::MAX_LIMIT_QUERY_SIZE;
        }

        if ($dataToStore['package_size'] < Constants::MIN_PACKAGE_SIZE) {
            $dataToStore['package_size'] = Constants::MIN_PACKAGE_SIZE;
        } elseif ($dataToStore['package_size'] > Constants::MAX_PACKAGE_SIZE) {
            $dataToStore['package_size'] = Constants::MAX_PACKAGE_SIZE;
        }

        return $dataToStore;
    }

    private function isPasswordEncryptionCorrect($password)
    {
        $data = Tools::getStoredSettings();

        return $password != $data['password'] || Tools::isPasswordEncryptedUsingBlockCipher($data['password']);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Emagicone_Bridgeconnector::edit');
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        try {
            if ($data) {
                if ($this->isPasswordEncryptionCorrect($data['password'])) {
                    Tools::saveConfigValue(
                        Constants::CONFIG_PATH_SETTINGS,
                        serialize($this->getPreparedSettingsToStore($data))
                    );

                    $this->messageManager->addSuccess(__('You saved Magento Store Manager Connector module settings'));
                } else {
                    $this->messageManager->addWarning(__('Required! Change password to encrypt it in better way!'));
                    $this->_redirect('*/settings/edit');
                    return;
                }

                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('/');
                } else {
                    $this->_redirect('*/settings/edit');
                }
                return;
            }
        } catch (\Magento\Framework\Model\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData();
        } catch (\RuntimeException $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData();
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Something went wrong while saving configuration.'));
            $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData();
        }
        $this->_redirect('*/settings/edit');
        return;
    }
}
