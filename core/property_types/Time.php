<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 17.12.2014
 * Time: 10:42
 */

namespace core\property_types;

class Time extends DateTime
{
    /**
     * @param string $name
     * @return \core\property_types\Time
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->output_format = 'H:i:s';
        return $this;
    }

    /**
     * @param mixed|null $format
     * @return bool|string
     */
    public function asString($format = self::NOT_INITIALIZED)
    {
        if ($format === self::NOT_INITIALIZED) {
            $format = $this->output_format;
        }
        return date($format, $this->value);
    }

    /**
     * @return bool|string
     */
    public function preparedForDb()
    {
        return date('H:i:s', $this->value);
    }
}