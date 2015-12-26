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


/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Field _vendorPath.
     *
     * @var string
     */
    private $_vendorPath;

    /**
     * Field _isVendor.
     *
     * @var bool
     */
    private $_isVendor;


    // Helper Methods

    protected function getVendorPath()
    {
        if ($this->_vendorPath === null) {
            $this->_vendorPath = $this->isVendor() ?
                __DIR__.'/../../../../' :
                __DIR__.'/../../vendor';
        }

        return $this->_vendorPath;
    }

    protected function isVendor()
    {
        if ($this->_isVendor === null) {
            $this->_isVendor = !is_dir(__DIR__.'/../../vendor');
        }

        return $this->_isVendor;
    }

    protected function getInstalledComponents()
    {
        $installedComponents = array();

        foreach (glob($this->getVendorPath().'/*/*') as $glob) {
            if (!is_file($glob.'/composer.json')) {
                continue;
            }

            $installedComponents[basename(dirname($glob)).'/'.basename($glob)] = realpath($glob);
        }

        ksort($installedComponents);

        return $installedComponents;
    }

    protected function getInstalledComponentsNames()
    {
        return array_keys($this->getInstalledComponents());
    }
}