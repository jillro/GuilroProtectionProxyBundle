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

namespace Guilro\ProtectionProxyBundle\Tests;

class DummyClass
{
    private $name;

    /**
     * Name param to ensure each dummy object is unique.
     *
     * @param string name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }
    /**
     * Dummy method for test.
     */
    public function dummyMethod()
    {
        return 'raw object';
    }

    /**
     * Dummy method for testing return_proxy parameter.
     */
    public function getBrother()
    {
        return new DummyClass('brother');
    }

    /**
     * Dummy method for testing return_proxy parameter.
     */
    public function getSister()
    {
        return new DummyClass('sister');
    }

    /**
     * Dummy method for testing with both attributes and expression condition set.
     */
    public function getSecret()
    {
        return 'secret';
    }

    /**
    * Dummy method for testing return_proxy parameters with arrays.
     */
    public function getParents()
    {
        return array(new DummyClass('Mum'), new DummyClass('Dad'));
    }
}
