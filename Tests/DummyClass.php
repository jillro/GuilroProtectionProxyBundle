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

namespace Guilro\ProtectionProxyBundle\Tests;

class DummyClass
{
    private $name;

    /**
     * Name param to ensure each dummy object is unique.
     *
     * @param string name
     */
    public function __construct($name) {
        $this->name = $name;
    }
    /**
     * Dummy method for test.
     */
    public function dummyMethod() {
        return 'raw object';
    }

    /**
     * Dummy method for testing return_proxy parameter.
     */
    public function getBrother() {
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
