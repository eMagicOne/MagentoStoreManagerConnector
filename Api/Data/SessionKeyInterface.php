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

namespace Emagicone\Bridgeconnector\Api\Data;

/**
 * Interface SessionKeyInterface
 * @package Emagicone\Bridgeconnector\Api\Data
 */
interface SessionKeyInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const ID            = 'id';
    const SESSION_KEY   = 'session_key';
    const DATE_ADDED    = 'date_added';
    const LAST_ACTIVITY = 'last_activity';

    public function getId();
    public function getSessionKey();
    public function getDateAdded();
    public function getLastActivity();

    public function setId($id);
    public function setSessionKey($sessionKey);
    public function setDateAdded($dateAdded);
    public function setLastActivity($lastActivity);
}
