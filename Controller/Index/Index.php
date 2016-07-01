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

namespace Emagicone\Bridgeconnector\Controller\Index;

use Emagicone\Bridgeconnector\Helper\MagentoOverrider;
use Magento\Framework\App\ResponseInterface;
use Emagicone\Bridgeconnector\Helper\BridgeCommon;

class Index extends \Magento\Framework\App\Action\Action
{

    const MODULE_VERSION = '1.0.0';
    const REVISION       = 1;
    const MODULE_NAME    = 'Emagicone_Bridgeconnector';
    const OPTIONS_NAME   = 'emagicone/bridgeconnector/settings';

    private $shop_cart_overrider;

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $this->shop_cart_overrider = new MagentoOverrider(self::MODULE_NAME, self::OPTIONS_NAME);
        new BridgeCommon($this->shop_cart_overrider, self::MODULE_VERSION, self::REVISION);
    }

}