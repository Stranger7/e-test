<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 01.10.2014
 * Time: 0:38
 */

namespace core\exceptions;

use core\Utils;

/**
 * Class CException
 * @package core\exceptions
 */
class CException extends \Exception
{
    protected $context = [];

    /**
     * @param string $message
     * @param array $context
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($message = "" , $context = [], $code = 0, \Exception $previous = NULL)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Return content array
     * @return array
     */
    public function getContextArray()
    {
        return $this->context;
    }

    /**
     * Converts context array to string and returns
     * @return string
     */
    public function getContextStr()
    {
        return Utils::arrayToString($this->context, PHP_EOL);
    }

    /**
     * Creates message string with context
     * @return string
     */
    public function getError()
    {
        $context_str = $this->getContextStr();
        return $this->getMessage() . ((!empty($context_str)) ? (PHP_EOL . $context_str) : '');
    }
}
