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
 * Time: 2:19
 */

namespace core\property_types;

use core\App;
use core\Config;
use core\generic\Property;

/**
 * Class DateTime
 * @package core\property_types
 */
class DateTime extends Property
{
    /**
     * @param string $name
     * @return \core\property_types\DateTime
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->output_format = App::config()->get(Config::GLOBAL_SECTION, 'default_date_format')
            . ' H:i:s';
        return $this;
    }

    /**
     * @return string
     */
    public function type()
    {
        return 'DATETIME';
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
     * @return string
     */
    public function preparedForDb()
    {
        return date('Y-m-d H:i:s', $this->value);
    }

    public function isEmpty()
    {
        return empty($this->value);
    }

    /**
     * Converts to int
     * @param mixed $value
     * @return int|null
     */
    protected function cast($value)
    {
        if (empty($value))
        {
            return self::NOT_INITIALIZED;
        }
        return is_numeric($value) ? intval($value) : strtotime($value);
    }
}