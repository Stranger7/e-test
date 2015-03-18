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
 * Time: 10:32
 */

namespace core\property_types;

use core\App;
use core\Config;

/**
 * Class Date
 * @package core\property_types
 */
class Date extends DateTime
{
    /**
     * @param string $name
     * @return \core\property_types\Date
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->output_format = App::config()->get(Config::GLOBAL_SECTION, 'default_date_format');
        return $this;
    }

    /**
     * @return string
     */
    public function type()
    {
        return 'DATE';
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
        return date('Y-m-d', $this->value);
    }
}