<?php

/*
 * This file is part of the frenzy-framework package.
 *
 * (c) Gustavo Falco <comfortablynumb84@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IronEdge\Component\Kernel\Config;


use IronEdge\Component\Config\ConfigInterface;
use IronEdge\Component\Kernel\Kernel;

/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
interface ProcessorInterface
{
    /**
     * This method process the configuration after being loaded and merged.
     *
     * @param Kernel          $kernel - Kernel.
     * @param ConfigInterface $config - Config object.
     *
     * @return void
     */
    public function process(Kernel $kernel, ConfigInterface $config);
}