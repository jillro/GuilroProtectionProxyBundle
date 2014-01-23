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

namespace Guilro\ProtectionProxyBundle\Tests\AccessManager;

use Guilro\ProtectionProxyBundle\AccessManager\AccessManager;
use Guilro\ProtectionProxyBundle\Tests\DummyClass;
use Symfony\Component\Security\Core\SecurityContextInterface;

class AccessManagerTest extends \PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $this->mockObject = new DummyClass('master');
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
                            ),
                            'getParents' => array(
                                'expression' => 'hasRole(ROLE_USER)',
                                'return_proxy' => true
                            ),
                            'getSecret' => array(
                                'attribute' => 'ROLE_USER',
                                'expression' => 'hasRole(ROLE_USER)',
                                'deny_value' => 'proxy'
                            )
                        )
                    )
                )
            ),
            array(
                'security.context' => $this->securityContext,
            )
        );
    }

    public function testAccessManagerDeny()
    {
        $this->securityContext
            ->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(false));

        $proxy = $this->accessManager->getProxy($this->mockObject);
        $this->assertTrue($proxy->dummyMethod() == 'proxy');
        $this->assertTrue($proxy->getBrother() == null);
        $this->assertTrue($proxy->getSister() == null);
    }

    public function testAccessManagerAllow()
    {
        $this->securityContext
            ->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $proxy = $this->accessManager->getProxy($this->mockObject);
        $this->assertTrue($proxy->dummyMethod() == 'raw object');
    }

    public function testAccessManagerAllowReturnProxy()
    {
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

    public function testAccessManagerReturnArrayProxies()
    {
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
        foreach($proxy->getParents() as $parents) {
            $this->assertTrue($parents->dummyMethod() == 'proxy');
        }
    }

    public function testAccessManagerAttributeDenyAndExpressionAllow()
    {
        $this->securityContext
            ->expects($this->any())
            ->method('isGranted')
            ->will($this->returnCallback(function ($attribute, $object)
            {
                if (is_a($attribute, 'Symfony\Component\ExpressionLanguage\Expression')) {
                    return true;
                } else {
                    return false;
                }
            }));
        $proxy = $this->accessManager->getProxy($this->mockObject);
        $this->assertTrue($proxy->getSecret() == 'proxy');
    }

    public function testAccessManagerAttributeAllowAndExpressionDeny()
    {
        $this->securityContext
            ->expects($this->any())
            ->method('isGranted')
            ->will($this->returnCallback(function ($attribute, $object) {
                if (is_a($attribute, 'Symfony\Component\ExpressionLanguage\Expression')) {
                    return false;
                } else {
                    return true;
                }
            }));
        $proxy = $this->accessManager->getProxy($this->mockObject);
        $this->assertTrue($proxy->getSecret() == 'proxy');
    }

    public function testAccessManagerAttibuteAllowAndExpressionAllow()
    {
        $this->securityContext
            ->expects($this->any())
            ->method('isGranted')
            ->will($this->returnCallback(function ($attribute, $object) {
                if (is_a($attribute, 'Symfony\Component\ExpressionLanguage\Expression')) {
                    return true;
                } else {
                    return true;
                }
            }));
        $proxy = $this->accessManager->getProxy($this->mockObject);
        $this->assertTrue($proxy->getSecret() == 'secret');
    }
}
