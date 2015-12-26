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
    public function test_getInstalledComponents_shouldReturnCorrectData()
    {
        $kernel = $this->createInstance();

        $installedComponents = $kernel->getInstalledComponents();

        $this->assertInternalType('array', $installedComponents);
    }


    // Helper Methods

    protected function createInstance(array $options = array())
    {
        return new Kernel($options);
    }
}