<?php
/*
 * This file is part of the frenzy-framework package.
 *
 * (c) Gustavo Falco <comfortablynumb84@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace IronEdge\Component\Kernel\Logger\Formatter;

use Monolog\Formatter\LineFormatter as BaseLineFormatter;

/*
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 */
class CustomLineFormatter extends BaseLineFormatter
{
    const SIMPLE_FORMAT = "[%level_name%] [%datetime%] [%unique_id%] %channel%: %message% %context% %extra%\n";


    /**
     * Field _uniqueId.
     *
     * @var string
     */
    private $_uniqueId;


    /**
     * CustomLineFormatter constructor.
     *
     * @param null|string $format
     * @param null|string $dateFormat
     * @param bool $allowInlineLineBreaks
     * @param bool $ignoreEmptyContextAndExtra
     */
    public function __construct()
    {
        $format = self::SIMPLE_FORMAT;
        $format = str_replace('%unique_id%', $this->getUniqueId(), $format);

        parent::__construct($format);
    }

    public function getUniqueId()
    {
        return isset($_SERVER['REQUEST_ID']) ?
            $_SERVER['REQUEST_ID'] :
            md5(uniqid('log-uniq-id', true).time().rand(0, 99999));
    }
}