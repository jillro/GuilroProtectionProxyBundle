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

class ProxyFactory
{
    /**
     * @var array
     */
    private $services;

    /**
     * @var array $protected_classes
     */
    private $protected_classes;

    /**
     * Constructor
     */
    public function __construct($parameters, $services)
    {
        $this->services = $services;
    }

    /**
     * @param mixed $entity
     * @return mixed
     */
    public function getProxy($entity, $access_manager)
    {
        $full_class_name = get_class($entity);
        $tmp = explode('\\', $full_class_name);
        $class_name = end($tmp);

        $full_proxy_class_name = 'Guilro\ProtectionProxyBundle\Proxy\\' . $full_class_name . '\\' . $class_name . 'ProtectionProxy';
        if (!class_exists($full_proxy_class_name, false)) {
            $methods = $this->_generateMethods($full_class_name);
            eval ('
namespace Guilro\ProtectionProxyBundle\Proxy\\' . $full_class_name . ';

use ' . $full_class_name . ';

class ' . $class_name . 'ProtectionProxy extends ' . $class_name . '

{
    public function __construct($real, $access_manager)
    {
        $this->real = $real;
        $this->access_manager = $access_manager;
    }

    public function __sleep()
    {
        return array(\'real\');
    }

    public function __clone()
    {
        $this->real = clone $this->real;
    }

    ' .$methods . '
}
            ');
        }

        $proxy = new $full_proxy_class_name($entity, $access_manager);

        assert(is_subclass_of($proxy, $full_class_name));

        return $proxy;
    }

    private function _generateMethods($class) //copied from doctrine ORM
    {
        $methods = '';

        $reflection_class = new \ReflectionClass($class);

        $methodNames = array();
        foreach ($reflection_class->getMethods() as $method) {
            /* @var $method ReflectionMethod */
            if (
                $method->isConstructor()
                || in_array(strtolower($method->getName()), array("__sleep", "__clone"))
                || isset($methodNames[$method->getName()])
            ) {
                continue;
            }

            $methodNames[$method->getName()] = true;

            if ($method->isPublic() && ! $method->isFinal() && ! $method->isStatic()) {
                $methods .= "\n" . '        public function ';
                if ($method->returnsReference()) {
                    $methods .= '&';
                }
                $methods .= $method->getName() . '(';
                $firstParam = true;
                $parameterString = $argumentString = '';

                foreach ($method->getParameters() as $param) {
                    if ($firstParam) {
                        $firstParam = false;
                    } else {
                        $parameterString .= ', ';
                        $argumentString  .= ', ';
                    }

                    // We need to pick the type hint class too
                    if (($paramClass = $param->getClass()) !== null) {
                        $parameterString .= '\\' . $paramClass->getName() . ' ';
                    } else if ($param->isArray()) {
                        $parameterString .= 'array ';
                    }

                    if ($param->isPassedByReference()) {
                        $parameterString .= '&';
                    }

                    $parameterString .= '$' . $param->getName();
                    $argumentString  .= '$' . $param->getName();

                    if ($param->isDefaultValueAvailable()) {
                        $parameterString .= ' = ' . var_export($param->getDefaultValue(), true);
                    }
                }

                $methods .= $parameterString . ')';
                $methods .= "\n" . '        {' . "\n";
                $methods .= '
            if($this->access_manager->isGranted($this, \'' . $method->getName() . '\') ) {
                $return = $this->real->' . $method->getName() . '(' . $argumentString . ');
            } else {
                $return = null;
            }

            if(is_object($return) && $return == $this->real) {
                return $this;
            } else if ($this->access_manager->isProtected($return)) {
                return $this->access_manager->getProxy($return);
            } else if ( is_array($return)
                    || $return instanceof \Traversable
                    || $return instanceof \ArrayAccess) {
                $new_return = array();
                foreach($return as $element) {
                    if($this->access_manager->isProtected($element)) {
                        if($this->access_manager->isObjectGranted($element)) {
                            $new_return[] = $this->access_manager->getProxy($element);
                        }
                    } else {
                        $new_return[] = $element;
                    }
                }
                $return = $new_return;
                return $return;
            } else {
                return $return;
            }
        }'
                            ;
            }
        }

        return $methods;
    }


    /**
     * @param mixed array $entities
     * @return Doctrine\Common\Collections\Collection
     */
    public function getProxies(array $entities)
    {
        foreach($entities as $entity) {
            $results[] = $this->getProxy($entity);
        }

        return $results;
    }
}
