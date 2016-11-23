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

namespace Emagicone\Bridgeconnector\Block\Adminhtml\Settings\Edit\Tab;

use Emagicone\Bridgeconnector\Helper\Constants;
use Emagicone\Bridgeconnector\Helper\Tools;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

/**
 * Settings edit form main tab
 */
class Main extends Generic implements TabInterface
{
    /**
     * Prepare form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function _prepareForm()
    {
        /*
         * Checking if user have permissions to save information
         */
        if ($this->_isAllowedAction('Emagicone_Bridgeconnector::edit')) {
            $isElementDisabled = false;
        } else {
            $isElementDisabled = true;
        }

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('settings_');
        $fieldset = $form->addFieldset('fieldset_access', ['legend' => __('Access Settings')]);

        $storedSettings = Tools::getStoredSettings();

        $fieldset->addField(
            'login',
            'text',
            [
                'name'     => 'login',
                'label'    => __('Login'),
                'title'    => __('Login'),
                'note'     => __(
                    'Login for accessing Magento Store Manager Connector from eMagicOne Store Manager for Magento'
                ),
                'value'    => $storedSettings['login'],
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'password',
            'password',
            [
                'name'     => 'password',
                'label'    => __('Password'),
                'title'    => __('Password'),
                'note'     => __(
                    'Password for accessing Magento Store Manager Connector from eMagicOne Store Manager for Magento'
                ),
                'value'    => $storedSettings['password'],
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset = $form->addFieldset('fieldset_sm_settings', ['legend' => __('Store Manager Bridge Settings')]);

        $fieldset->addField(
            'tmp_dir',
            'text',
            [
                'name'     => 'tmp_dir',
                'label'    => __('Directory for Module Operations'),
                'title'    => __('Directory for Module Operations'),
                'value'    => $storedSettings['tmp_dir'],
                'note'     => __('Enter temporary folder path. It should be writable'),
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $contentField = $fieldset->addField(
            'allow_compression',
            'hidden',
            [
                'name'     => 'allow_compression',
                'disabled' => $isElementDisabled,
            ]
        );
        $renderer = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element')
            ->setTemplate('Emagicone_Bridgeconnector::settings/edit/form/renderer/allow_compression.phtml');
        $contentField->setRenderer($renderer);

        $fieldset->addField(
            'compress_level',
            'text',
            [
                'name'     => 'compress_level',
                'label'    => __('Compress Level'),
                'title'    => __('Compress Level'),
                'value'    => $storedSettings['compress_level'],
                'note'     => __(
                    'Values between ' . Constants::MIN_COMPRESS_LEVEL . ' and ' . Constants::MAX_COMPRESS_LEVEL
                    . ' will trade off speed and efficiency. The 1 flag means "fast but less efficient" compression,
                    and 9 means "slow but most efficient" compression'
                ),
                'class'    => 'validate-number-range number-range-' . Constants::MIN_COMPRESS_LEVEL . '-'
                    . Constants::MAX_COMPRESS_LEVEL,
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'limit_query_size',
            'text',
            [
                'name'     => 'limit_query_size',
                'label'    => __('Selection Query Size'),
                'title'    => __('Selection Query Size'),
                'value'    => $storedSettings['limit_query_size'],
                'note'     => __(
                    'Restrict capacity of queries per one request (kB). Values between '
                    . Constants::MIN_LIMIT_QUERY_SIZE . ' and ' . Constants::MAX_LIMIT_QUERY_SIZE
                ),
                'class'    => 'validate-number-range number-range-' . Constants::MIN_LIMIT_QUERY_SIZE . '-'
                    . Constants::MAX_LIMIT_QUERY_SIZE,
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $fieldset->addField(
            'package_size',
            'text',
            [
                'name'     => 'package_size',
                'label'    => __('Package Size'),
                'title'    => __('Package Size'),
                'value'    => $storedSettings['package_size'],
                'note'     => __(
                    'Size of parts for getting dump file (kB). Values between '
                    . Constants::MIN_PACKAGE_SIZE . ' and ' . Constants::MAX_PACKAGE_SIZE
                ),
                'class'    => 'validate-number-range number-range-' . Constants::MIN_PACKAGE_SIZE . '-'
                    . Constants::MAX_PACKAGE_SIZE,
                'required' => true,
                'disabled' => $isElementDisabled
            ]
        );

        $contentField = $fieldset->addField(
            'exclude_db_tables',
            'hidden',
            [
                'name'     => 'exclude_db_tables',
                'disabled' => $isElementDisabled,
            ]
        );
        $renderer = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element')
            ->setTemplate('Emagicone_Bridgeconnector::settings/edit/form/renderer/exclude_db_tables.phtml');
        $contentField->setRenderer($renderer);

        $fieldset->addField(
            'allowed_ips',
            'text',
            [
                'name'     => 'allowed_ips',
                'label'    => __('Allowed IPs'),
                'title'    => __('Allowed IPs'),
                'value'    => $storedSettings['allowed_ips'],
                'note'     => __(
                    'In order to allow module using only from specific IP address you should add IP address here
                    (for example, 48.78.88.98 - only one IP address; 48.78.88.98, 15.25.35.45 - two IP addresses;
                    48.78.x.x - all IP addresses which begin from 48.78.)'
                ),
                'disabled' => $isElementDisabled
            ]
        );

        $form->addValues($storedSettings);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Settings');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Settings');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    public function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    public function isAjaxLoaded()
    {
        return false;
    }
}
