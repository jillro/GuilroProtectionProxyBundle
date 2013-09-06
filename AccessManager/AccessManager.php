<?php
/*
 * This file is part of Redado.
 *
 * Copyright (C) 2013 Guillaume Royer
 *
 * Redado is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Redado is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Redado.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Guilro\ProtectionProxyBundle\AccessManager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Symfony\Component\Security\Core\SecurityContextInterface;

class AccessManager {
    /**
     * @var array
     */
    private $services;

    private $protected_classes;

    /**
     * Constructor
     */
    public function __construct($parameters, $services)
    {
        $this->services = $services;
        $this->protected_classes = $parameters['protected_classes'];
    }

    public function isGranted($object, $function_name)
    {
        if(!isset($this->protected_classes[get_parent_class($object)]['methods'][$function_name])) {
            return true;
        } else {
            $method_attribute = $this->protected_classes[get_parent_class($object)]['methods'][$function_name];

            if (!$this->services['security.context']->isGranted($method_attribute, $object)) {
                return false;
            }

            return true;
        }
    }

    public function isProtected($object)
    {
        if(is_object($object) && isset($this->protected_classes[get_class($object)]) ) {
            return true;
        } else {
            return false;
        }
    }

    public function getProxy($object)
    {
        return $this->getFactory()->getProxy($object, $this);
    }

    public function getFactory()
    {
        return $this->services['guilro_protection_proxy.access_manager.factory'];
    }
}
