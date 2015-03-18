<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 04.10.2014
 * Time: 11:04
 */

namespace core\property_types;

use core\generic\Property;

/**
 * Class String
 * @package core\property_types
 */
class String extends Property
{
    /**
     * @return string
     */
    public function type()
    {
        return 'TEXT';
    }

    /**
     * @param mixed|null $format
     * @return mixed
     */
    public function asString($format = self::NOT_INITIALIZED)
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function preparedForDb()
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->value);
    }

    /**
     * Converts to int
     * @param mixed $value
     * @return string
     */
    protected function cast($value)
    {
        return strval($value);
    }
}