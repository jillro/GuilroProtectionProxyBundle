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


namespace Guilro\ProtectionProxyBundle\Tests\AccessManager;

use Guilro\ProtectionProxyBundle\AccessManager\AccessManager;
use Guilro\ProtectionProxyBundle\AccessManager\ProxyFactory;
use Guilro\ProtectionProxyBundle\Tests\DummyClass;
use Symfony\Component\Security\Core\SecurityContextInterface;

class AccessManagerTest extends \PHPUnit_Framework_TestCase
{
    public function __construct() {
        $this->mockObject = new DummyClass();
        $this->securityContext = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->accessManager = new AccessManager(
            array(
                'protected_classes' => array(
                    'Guilro\ProtectionProxyBundle\Tests\DummyClass' => array(
                        'methods' => array(
                            'dummyMethod' => array(
                                'attribute' => 'ROLE_USER',
                                'deny_value' => 'proxy'
                            ),
                            'getBrother' => array(
                                'expression' => 'hasRole(ROLE_USER)',
                                'return_proxy' => true
                            ),
                            'getSister' => array(
                                'expression' => 'hasRole(ROLE_USER)',
                                'return_proxy' => false
                            )
                        )
                    )
                )
            ),
            array(
                'security.context' => $this->securityContext,
                'guilro_protection_proxy.access_manager.factory' => new ProxyFactory(null, null)
            )
        );
    }

    public function testAccessManagerDeny() {
        $this->securityContext
            ->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(false));

        $proxy = $this->accessManager->getProxy($this->mockObject);
        $this->assertTrue($proxy->dummyMethod() == 'proxy');
        $this->assertTrue($proxy->getBrother() == null);
        $this->assertTrue($proxy->getSister() == null);
    }

    public function testAccessManagerAllow() {
        $this->securityContext
            ->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $proxy = $this->accessManager->getProxy($this->mockObject);
        $this->assertTrue($proxy->dummyMethod() == 'raw object');
    }

    public function testAccessManagerReturnProxy() {
        $this->securityContext
            ->expects($this->any())
            ->method('isGranted')
            ->will($this->returnCallback(function ($attribute, $object) {
                if (is_a($attribute, 'Symfony\Component\ExpressionLanguage\Expression')) {
                    return true;
                } else {
                    return false;
                }
            }));
        $proxy = $this->accessManager->getProxy($this->mockObject);
        $this->assertTrue($proxy->dummyMethod() == 'proxy');
        $this->assertTrue($proxy->getBrother()->dummyMethod() == 'proxy');
        $this->assertTrue($proxy->getSister()->dummyMethod() == 'raw object');
    }
}
