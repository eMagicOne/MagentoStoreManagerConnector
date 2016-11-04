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

namespace Emagicone\Bridgeconnector\Model;

use Emagicone\Bridgeconnector\Api\Data\SessionKeyInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

/**
 * Class SessionKey
 * @package Emagicone\Bridgeconnector\Model
 */
class SessionKey extends \Magento\Framework\Model\AbstractModel implements SessionKeyInterface
{
    public function _construct()
    {
        $this->_init('Emagicone\Bridgeconnector\Model\ResourceModel\SessionKey');
    }

    public function getSessionKey()
    {
        return $this->getData(self::SESSION_KEY);
    }

    public function getDateAdded()
    {
        return $this->getData(self::DATE_ADDED);
    }

    public function getLastActivity()
    {
        return $this->getData(self::LAST_ACTIVITY);
    }

    public function setSessionKey($sessionKey)
    {
        return $this->setData(self::SESSION_KEY, $sessionKey);
    }

    public function setDateAdded($dateAdded)
    {
        return $this->setData(self::DATE_ADDED, $dateAdded);
    }

    public function setLastActivity($lastActivity)
    {
        return $this->setData(self::LAST_ACTIVITY, $lastActivity);
    }
}
