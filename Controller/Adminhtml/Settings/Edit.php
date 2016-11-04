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

use Magento\Backend\App\Action;
use Emagicone\Bridgeconnector\Helper\Tools;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;

/**
 * Class Edit
 * @package Emagicone\Bridgeconnector\Controller\Adminhtml\Settings
 */
class Edit extends \Magento\Backend\App\Action
{
    /**
     * @var Registry|null
     */
    public $coreRegistry = null;

    /**
     * @var PageFactory
     */
    public $resultPageFactory;

    /**
     * Edit constructor.
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $registry
     */
    public function __construct(Action\Context $context, PageFactory $resultPageFactory, Registry $registry)
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $registry;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Emagicone_Bridgeconnector::save');
    }

    /**
     * Init actions
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Emagicone_Bridgeconnector::bridgeconnector_settings')
            ->addBreadcrumb(__('BRIDGECONNECTOR'), __('BRIDGECONNECTOR'))
            ->addBreadcrumb(__('Manage Settings'), __('Manage Settings'));

        return $resultPage;
    }

    /**
     * Edit settings
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        if (Tools::isLoginPasswordDefault()) {
            $this->messageManager
                ->addWarning('Default login and password are "1". Change them because of security reasons, please!');
        }

        if (!Tools::isPasswordEncryptedUsingBlockCipher()) {
            $this->messageManager
                ->addWarning('Required! Change password to encrypt it in better way!');
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(__('Edit Settings'), __('Edit Settings'));
        $resultPage->getConfig()->getTitle()->prepend(__('Settings'));
        $resultPage->getConfig()->getTitle()
            ->prepend(__('Settings'));

        return $resultPage;
    }
}
