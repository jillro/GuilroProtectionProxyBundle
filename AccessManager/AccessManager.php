<?php
/*
 * This file is part of GuilroProtectionProxyBundle.
 *
 * Copyright (C) 2013 Guillaume Royer
 *
 * GuilroProtectionProxyBundle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * GuilroProtectionProxyBundle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GuilroProtectionProxyBundle.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Guilro\ProtectionProxyBundle\AccessManager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;

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


    /**
     * Return wether current token is granted to execute the method $method_name of the proxy object $proxy.
     * Parent class of the proxy MUST be the protected class.
     *
     * @param mixed $proxy
     * @param string $method_name
     *
     * @return boolean
     */
    public function isGranted($proxy, $method_name)
    {
        if(!isset($this->protected_classes[ClassUtils::getRealClass($proxy)]['methods'][$method_name]['attribute'])) {
            return true;
        } else {
            $method_attribute = $this->protected_classes[ClassUtils::getRealClass($proxy)]['methods'][$method_name]['attribute'];

            if (!$this->services['security.context']->isGranted($method_attribute, $proxy)) {
                return false;
            }

            return true;
        }
    }

    public function isObjectGranted($object)
    {
        if(!isset($this->protected_classes[ClassUtils::getRealClass($object)]['view'])) {
            return true;
        } else {
            $view_attribute = $this->protected_classes[ClassUtils::getRealClass($object)]['view'];

            if (!$this->services['security.context']->isGranted($view_attribute, $object)) {
                return false;
            }

            return true;
        }
    }

    /**
     * Return wether object $object should be protected, that is to say if a proxy can be generated for it.
     * Does not work if $object is sub-class of a protected class.
     *
     * @param mixed $object
     *
     * @return boolean
     */
    public function isProtected($object)
    {
        if(is_object($object) && isset($this->protected_classes[ClassUtils::getRealClass($object)]) ) {
            return true;
        } else {
            return false;
        }
    }

    public function isReturningProxy($proxy, $method_name)
    {
        if(!isset($this->protected_classes[ClassUtils::getRealClass($proxy)]['methods'][$method_name]['return_proxy'])) {
            return false;
        } elseif ($this->protected_classes[ClassUtils::getRealClass($proxy)]['methods'][$method_name]['return_proxy'] == true) {
            return true;
        }
        return false;
    }

    public function getDenyValue($proxy, $method_name)
    {
        if (!isset($this->protected_classes[ClassUtils::getRealClass($proxy)]['methods'][$method_name]['deny_value'])) {
            return null;
        } else {
            return $this->protected_classes[ClassUtils::getRealClass($proxy)]['methods'][$method_name]['deny_value'];
        }
    }

    /**
     * Return a protected proxy of $object.
     *
     * @param mixed
     *
     * @return mixed
     */
    public function getProxy($object)
    {
        if ($this->isObjectGranted($object)) {
            return $this->getFactory()->getProxy($object, $this);
        } else {
            return null;
        }
    }

    public function getProxies(array $objects)
    {
        $return = array();

        foreach($objects as $object) {
            $proxy = $this->getProxy($object);
            is_null($proxy) ? null : $return[] = $proxy;
        }

        return $return;
    }

    /**
     * @return ProxyFactory
     */
    public function getFactory()
    {
        return $this->services['guilro_protection_proxy.access_manager.factory'];
    }
}
