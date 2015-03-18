<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10.12.2014
 * Time: 20:38
 */

namespace core\property_types;

use core\generic\Property;
use core\Utils;

/**
 * Class Bool
 * @package core\property_types
 */
class Bool extends Property
{
    /**
     * @param string $name
     * @return \core\property_types\Bool
     */
    public function __construct($name)
    {
        parent::__construct($name);
        $this->output_format = ['Yes', 'No'];
        return $this;
    }

    /**
     * @return string
     */
    public function type()
    {
        return 'BOOL';
    }

    /**
     * @param array $format
     * @return string
     */
    public function asString($format = [])
    {
        if (!(is_array($format) && count($format) == 2)) {
            $format = $this->output_format;
        }
        return ($this->value ? ((string) $format[0]) : ((string) $format[1]));
    }

    /**
     * Prepare for recording to DB
     *
     * @return bool
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
        return (!$this->initialized());
    }

    /**
     * Converts to bool
     * @param mixed $value
     * @return bool
     */
    protected function cast($value)
    {
        if (is_string($value)) {
            return Utils::boolValue($value);
        }
        return boolval($value);
    }
}