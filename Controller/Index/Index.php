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

namespace Emagicone\Bridgeconnector\Controller\Index;

use Emagicone\Bridgeconnector\Helper\Constants;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\Action\Action;

/**
 * Class Index
 * @package Emagicone\Bridgeconnector\Controller\Index
 */
class Index extends Action
{
    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $shopCartOverrider = $this->_objectManager->create(
            'Emagicone\Bridgeconnector\Helper\MagentoOverrider',
            [
                'module_name'    => Constants::MODULE_NAME,
                'options_name'   => Constants::OPTIONS_NAME,
                'request'        => $this->_request,
                'object_manager' => $this->_objectManager
            ]
        );

        $common_bridge = $this->_objectManager->create(
            'Emagicone\Bridgeconnector\Helper\BridgeCommon',
            [
                'shop_cart_overrider' => $shopCartOverrider,
                'module_version'      => Constants::MODULE_VERSION,
                'revision'            => Constants::REVISION,
                'responseKeyOutput'   => Constants::RESPONSE_KEY_OUTPUT,
                'responseKeyHeaders'  => Constants::RESPONSE_KEY_HEADERS,
                'maxKeyLifetime'      => Constants::MAX_KEY_LIFETIME,
            ]
        );

        $response = $common_bridge->getResponse();

        if ($response && is_array($response)) {
            if (isset($response[Constants::RESPONSE_KEY_HEADERS])
                && is_array($response[Constants::RESPONSE_KEY_HEADERS])
            ) {
                $count = count($response[Constants::RESPONSE_KEY_HEADERS]);

                for ($i = 0; $i < $count; $i++) {
                    $this->_response->setHeader(
                        $response[Constants::RESPONSE_KEY_HEADERS][$i]['name'],
                        $response[Constants::RESPONSE_KEY_HEADERS][$i]['value']
                    );
                }

                $this->_response->sendHeaders();
            }

            $this->_response->setContent(
                isset($response[Constants::RESPONSE_KEY_OUTPUT]) ? $response[Constants::RESPONSE_KEY_OUTPUT] : ''
            );
        }

        return $this->_response;
    }
}
