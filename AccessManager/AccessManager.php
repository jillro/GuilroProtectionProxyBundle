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
use Symfony\Component\ExpressionLanguage\Expression;

use ProxyManager\Factory\AccessInterceptorValueHolderFactory as Factory;

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
        $this->factory = new Factory();
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
        if ($this->isProtected($object)) {
            return $this->doCreateProxy($object);
        } else {
            return $object;
        }
    }

    /**
     * Return an array of proxies given an array of Objects.
     *
     * @param array
     *
     * @return array
     */
    public function getProxies(array $objects)
    {
        $return = array();

        foreach($objects as $object) {
            $proxy = $this->getProxy($object);
        }

        return $return;
    }


    /**
     * Generate the proxy thanks to OcramiusProxyManager library.
     *
     * @param mixed
     *
     * @return mixed
     */
    private function doCreateProxy($object) {
        $proxy = $this->factory->createProxy($object);
        foreach($this->protected_classes[get_class($object)]['methods'] as $method => $options) {
            $attribute = isset($options['attribute']) ? $options['attribute'] : false;
            $expression = isset($options['expression']) ? $options['expression'] : false;
            $returnProxy = isset($options['return_proxy']) ? $options['return_proxy'] : false;
            $denyValue = isset($options['deny_value']) ? $options['deny_value'] : null;

            if($attribute != false && $expression != false) {
                $proxy->setMethodPrefixInterceptor($method,
                    function($proxy, $instance, $methodName, $params, & $returnEarly) use ($expression, $attribute, $denyValue)
                    {
                        if (!($this->services['security.context']->isGranted(new Expression($expression), $instance) && $this->services['security.context']->isGranted($attribute, $instance)))  {
                            $returnEarly = true;
                            return $denyValue;
                        } else {
                            $returnEarly = false;
                            return;
                        }
                    }
                );
                continue;
            }

            if($attribute != false) {
                $proxy->setMethodPrefixInterceptor($method,
                    function($proxy, $instance, $methodName, $params, & $returnEarly) use ($attribute, $denyValue)
                    {
                        if (!$this->services['security.context']->isGranted($attribute, $instance)) {
                            $returnEarly = true;
                            return $denyValue;
                        } else {
                            $returnEarly = false;
                            return;
                        }
                    }
                );
                continue;
            }

            if($expression != false) {
                $proxy->setMethodPrefixInterceptor($method,
                    function($proxy, $instance, $methodName, $params, & $returnEarly) use ($expression, $denyValue)
                    {
                        if (!$this->services['security.context']->isGranted(new Expression($expression), $instance)) {
                            $returnEarly = true;
                            return $denyValue;
                        } else {
                            $returnEarly = false;
                            return;
                        }
                    }
                );
            }

            if($returnProxy) {
                $proxy->setMethodSuffixInterceptor($method,
                    function ($proxy, $instance, $methodName, $params, $returnValue, & $returnEarly)
                    {
                        if(is_object($returnValue) && $returnValue === $proxy) {
                            $returnEarly = false;
                            return;
                        } else if ($this->isProtected($returnValue)) {
                            $returnEarly = true;
                            return $this->getProxy($returnValue);
                        } else if (is_array($returnValue)
                            || $returnValue instanceof \Traversable
                            || $returnValue instanceof \ArrayAccess
                        ) {
                            $return = array();
                            foreach($returnValue as $element) {
                                if($this->isProtected($element)) {
                                    $return[] = $this->getProxy($element);
                                } else {
                                    $return[] = $element;
                                }
                            }
                            $returnEarly = true;
                            return $return;
                        }
                    }
                );
            }
        } // end foreach

        return $proxy;
    } // end doCreateProxy

    /**
     * Return wether object $object should be protected, that is to say if a proxy can be generated for it.
     * Does not work if $object is sub-class of a protected class.
     *
     * @param mixed $object
     *
     * @return boolean
     */
    private function isProtected($object)
    {
        if(is_object($object) && isset($this->protected_classes[get_class($object)]) ) {
            return true;
        } else {
            return false;
        }
    }
}
