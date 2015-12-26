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
class VendorsNotInstalledException extends BaseException
{
    public static function create()
    {
        return new self('Vendors are not installed. Please, execute "composer install" and try again!');
    }
}