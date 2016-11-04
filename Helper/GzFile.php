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
 * Class GzFile
 * @package Emagicone\Bridgeconnector\Helper
 */
class GzFile extends \Magento\Framework\Archive\Helper\File\Gz
{
    public function gzOpen($mode)
    {
        $this->_open($mode);
    }

    public function gzWrite($data)
    {
        $this->_write($data);
    }

    public function gzClose()
    {
        $this->_close();
        $this->_fileHandler = null;
    }
}
