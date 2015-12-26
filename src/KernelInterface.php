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


/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
interface KernelInterface
{
    /**
     * Returns the path to the "vendor" directory.
     *
     * @throws \IronEdge\Component\Kernel\Exception\VendorsNotInstalledException
     *
     * @return string
     */
    public function getVendorPath();

    /**
     * Determines if this component is a vendor, or it's a root project.
     *
     * @return bool
     */
    public function isVendor();

    /**
     * Returns true if this component is the root package.
     *
     * @return bool
     */
    public function isRootPackage();

    /**
     * Returns an array of installed component names. Example: ["ironedge/kernel", "myvendor/mycomponent"],
     *
     * @return array
     */
    public function getInstalledComponentsNames();

    /**
     * Returns an array of installed component, each element being component-name => component-path.
     *
     * Example:
     *
     * {"ironedge/kernel" => "/path/to/ironedge/kernel", "myvendor/mycomponent" => "/path/to/myvendor/mycomponent}
     *
     * @return array
     */
    public function getInstalledComponents();

    /**
     * Returns the Kernel options.
     *
     * @return array
     */
    public function getOptions();
}