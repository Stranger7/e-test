<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 03.10.2014
 * Time: 23:01
 */

namespace core\generic;

/**
 * Class Rule
 * @package core\generic
 */
abstract class Rule
{
    /**
     * @var Property
     */
    protected $property;

    public function __construct() {}

    public function forProperty(Property &$property)
    {
        $this->property = $property;
    }

    /**
     * Validates value of property
     * @return bool
     */
    abstract public function isValid();

    /**
     * Returns string with error message
     * @return string
     */
    abstract public function getMessage();
}
