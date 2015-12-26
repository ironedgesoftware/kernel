<?php

/*
 * This file is part of the kernel package.
 *
 * (c) Gustavo Falco <comfortablynumb84@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IronEdge\Component\Kernel;

use IronEdge\Component\Kernel\Exception\VendorsNotInstalledException;

/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
class Kernel implements KernelInterface
{
    /**
     * A list of installed components.
     *
     * @var array
     */
    private $installedComponents;

    /**
     * Is this component a vendor?
     *
     * @var bool
     */
    private $isVendor;

    /**
     * Vendor dir path.
     *
     * @var string
     */
    private $vendorPath;


    /**
     * Returns the path to the "vendor" directory.
     *
     * @throws VendorsNotInstalledException
     *
     * @return string
     */
    public function getVendorPath()
    {
        if ($this->vendorPath === null) {
            $dir = $this->isVendor() ?
                __DIR__.'/../../../' :
                __DIR__.'/../';

            if (!($dir = realpath($dir))) {
                throw VendorsNotInstalledException::create();
            }

            $this->vendorPath = realpath($dir);
        }

        return $this->vendorPath;
    }

    /**
     * Determines if this component is a vendor, or it's a root project.
     *
     * @return bool
     */
    public function isVendor()
    {
        if ($this->isVendor === null) {
            $this->isVendor = is_dir(__DIR__.'/../vendor');
        }

        return $this->isVendor;
    }

    /**
     * Returns an array of installed component names. Example: ["ironedge/kernel", "myvendor/mycomponent"],
     *
     * @return array
     */
    public function getInstalledComponentsNames()
    {
        return array_keys($this->getInstalledComponents());
    }

    /**
     * Returns an array of installed component, each element being component-name => component-path.
     *
     * Example:
     *
     * {"ironedge/kernel" => "/path/to/ironedge/kernel", "myvendor/mycomponent" => "/path/to/myvendor/mycomponent}
     *
     * @return array
     */
    public function getInstalledComponents()
    {
        if ($this->installedComponents === null) {
            $vendorPath = $this->getVendorPath();
            $glob = glob($vendorPath.'/*/*');

            $this->installedComponents = [];

            foreach ($glob as $globElement) {
                if (!is_file($globElement.'/composer.json')) {
                    continue;
                }

                $this->installedComponents[basename(dirname($globElement)).'/'.basename($globElement)] = $globElement;
            }
        }

        return $this->installedComponents;
    }
}