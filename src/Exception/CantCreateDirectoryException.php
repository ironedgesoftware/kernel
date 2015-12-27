<?php

/*
 * This file is part of the kernel package.
 *
 * (c) Gustavo Falco <comfortablynumb84@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IronEdge\Component\Kernel\Exception;


/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
class CantCreateDirectoryException extends BaseException
{
    public static function create($name, $dir)
    {
        return new self(
            'Directory "'.$name.'" on path "'.$dir.'" could not be created. Last PHP Error: '.
            print_r(error_get_last(), true)
        );
    }
}