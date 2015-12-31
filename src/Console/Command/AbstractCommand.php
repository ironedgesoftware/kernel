<?php
/*
 * This file is part of the frenzy-framework package.
 *
 * (c) Gustavo Falco <comfortablynumb84@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IronEdge\Component\Kernel\Console\Command;

use \IronEdge\Component\Cli\Console\Command\AbstractCommand as BaseAbstractCommand;
use IronEdge\Component\Kernel\KernelInterface;

/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
abstract class AbstractCommand extends BaseAbstractCommand
{
    /**
     * Returns the kernel instance.
     *
     * @return KernelInterface
     */
    public function getKernel()
    {
        return $this->getApplication()->getKernel();
    }
}