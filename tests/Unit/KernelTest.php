<?php

/*
 * This file is part of the kernel package.
 *
 * (c) Gustavo Falco <comfortablynumb84@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IronEdge\Component\Kernel\Test\Unit;

use IronEdge\Component\Kernel\Kernel;

/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
class KernelTest extends AbstractTestCase
{
    public function test_isVendor_shouldReturnTrueIfThisComponentIsAVendor()
    {
        $kernel = $this->createInstance();

        $this->assertEquals($this->isVendor(), $kernel->isVendor());
    }

    public function test_isRootPackage_shouldReturnTrueIfThisComponentIsTheRootPackage()
    {
        $kernel = $this->createInstance();

        $this->assertEquals(!$this->isVendor(), $kernel->isRootPackage());
    }

    public function test_getInstalledComponents_shouldReturnCorrectData()
    {
        $kernel = $this->createInstance();
        $installedComponents = $kernel->getInstalledComponents();
        $expectedInstalledComponents = $this->getInstalledComponents();

        $this->assertEquals($expectedInstalledComponents, $installedComponents);
    }

    public function test_getInstalledComponentsNames_returnsAnArrayOfInstalledComponentsNames()
    {
        $kernel = $this->createInstance();

        $this->assertEquals($this->getInstalledComponentsNames(), $kernel->getInstalledComponentsNames());
    }


    // Helper Methods

    protected function createInstance(array $options = array())
    {
        return new Kernel($options);
    }
}