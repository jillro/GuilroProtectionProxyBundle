<?php
/*
 * This file is part of GuilroProtectionProxyBundle.
 *
 * Copyright (c) 2013 Guillaume Royer
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Guilro\ProtectionProxyBundle\AccessManager;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\ExpressionLanguage\Expression;

use ProxyManager\Factory\AccessInterceptorValueHolderFactory as Factory;

class AccessManager
{
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
    private function doCreateProxy($object)
    {
        $proxy = $this->factory->createProxy($object);
        /* $this in closures does not work with PHP 5.3
         * so we have to pass the object explicitely
         * with its private properties */
        $_this = $this;
        $securityContext = $this->services['security.context'];

        foreach($this->protected_classes[ClassUtils::getRealClass($object)]['methods'] as $method => $options) {
            $attribute = isset($options['attribute']) ? $options['attribute'] : false;
            $expression = isset($options['expression']) ? $options['expression'] : false;
            $returnProxy = isset($options['return_proxy']) ? $options['return_proxy'] : false;
            $denyValue = isset($options['deny_value']) ? $options['deny_value'] : null;

            if($attribute != false && $expression != false) {
                $proxy->setMethodPrefixInterceptor($method,
                    function($proxy, $instance, $methodName, $params, & $returnEarly) use ($expression, $attribute, $denyValue, $securityContext)
                    {
                        if (!($securityContext->isGranted(new Expression($expression), $instance) && $securityContext->isGranted($attribute, $instance)))  {
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
                    function($proxy, $instance, $methodName, $params, & $returnEarly) use ($attribute, $denyValue, $securityContext)
                    {
                        if (!$securityContext->isGranted($attribute, $instance)) {
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
                    function($proxy, $instance, $methodName, $params, & $returnEarly) use ($expression, $denyValue, $securityContext)
                    {
                        if (!$securityContext->isGranted(new Expression($expression), $instance)) {
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
                    function ($proxy, $instance, $methodName, $params, $returnValue, & $returnEarly) use ($_this)
                    {
                        if(is_object($returnValue) && $returnValue === $proxy) {
                            $returnEarly = false;
                            return;
                        } else if ($_this->isProtected($returnValue)) {
                            $returnEarly = true;
                            return $_this->getProxy($returnValue);
                        } else if (is_array($returnValue)
                            || $returnValue instanceof \Traversable
                            || $returnValue instanceof \ArrayAccess
                        ) {
                            $return = array();
                            foreach($returnValue as $element) {
                                if($_this->isProtected($element)) {
                                    $return[] = $_this->getProxy($element);
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
}
