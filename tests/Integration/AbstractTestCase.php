<?php

/*
 * This file is part of the kernel package.
 *
 * (c) Gustavo Falco <comfortablynumb84@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IronEdge\Component\Kernel\Test\Integration;


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
            $this->_isVendor = is_dir(__DIR__.'/../../../../ironedge/kernel');
        }

        return $this->_isVendor;
    }

    protected function getTestInstalledComponentNames()
    {
        return array_keys($this->getTestInstalledComponents());
    }

    protected function getTestInstalledComponents()
    {
        $glob = glob($this->getTestVendorPath().'/*/*');
        $expectedInstalledComponents = [];

        foreach ($glob as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $expectedInstalledComponents[basename(dirname($dir)).'/'.basename($dir)] = realpath($dir);
        }

        $expectedInstalledComponents['ROOT/PACKAGE'] = $this->getTestRootPath();

        return $expectedInstalledComponents;
    }

    protected function getTestRootPath()
    {
        return realpath($this->getTestHelperDirsPath().'/root');
    }

    protected function getTestVendorPath()
    {
        return realpath($this->getTestHelperDirsPath().'/vendor');
    }

    protected function getTestVendorPath2()
    {
        return realpath($this->getTestHelperDirsPath().'/vendor_2');
    }

    protected function getTestVendorPath3()
    {
        return realpath($this->getTestHelperDirsPath().'/vendor_3');
    }

    protected function getTestVendorPath4()
    {
        return realpath($this->getTestHelperDirsPath().'/vendor_4');
    }

    protected function getTestVendorPath5()
    {
        return realpath($this->getTestHelperDirsPath().'/vendor_5');
    }

    protected function getTestConfigPath()
    {
        return realpath($this->getTestHelperDirsPath().'/config');
    }

    protected function getTestHelperDirsPath()
    {
        return realpath(__DIR__.'/../helper_dirs');
    }

    protected function cleanUp()
    {
        $glob = glob($this->getTestRootPath().'/*') + [
            $this->getRootPath().'/var',
            $this->getRootPath().'/etc',
            $this->getRootPath().'/bin'
        ];

        foreach ($glob as $element) {
            $this->removeElement($element);
        }
    }

    protected function getRootPath()
    {
        $path = realpath(__DIR__.'/../../');

        // Just want to play safe here...

        if (!$path) {
            throw new \RuntimeException(
                'Method "getRootPath" couldn\'t determine the path to the root of this component.'
            );
        }

        return $path;
    }

    protected function removeElement($element)
    {
        if (is_dir($element)) {
            $dirIterator = new \DirectoryIterator($element);

            /** @var \DirectoryIterator $el */
            foreach ($dirIterator as $el) {
                if ($el->isDot()) {
                    continue;
                }

                if (strpos($el->getPathname(), $this->getRootPath()) !== 0) {
                    throw new \RuntimeException(
                        'Can\'t remove file / dir "'.$el->getPathname().'"! We can only remove files / dirs '.
                        'inside directory "'.$this->getRootPath().'".'
                    );
                }

                $this->removeElement($el->getPathname());
            }

            @rmdir($element);
        } else if (file_exists($element)) {
            if (strpos($element, $this->getRootPath()) !== 0) {
                throw new \RuntimeException(
                    'Can\'t remove file / dir "'.$element.'"! We can only remove files / dirs '.
                    'inside directory "'.$this->getRootPath().'".'
                );
            }

            @unlink($element);
        }
    }
}