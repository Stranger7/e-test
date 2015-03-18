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
 * Time: 0:50
 */

namespace core\rules;

use core\generic\Property;
use core\generic\Rule;

/**
 * Class MatchedWith
 * @package core\rules
 *
 * Matching the value of the current property value of the specified property
 */
class MatchedWith extends Rule
{
    /**
     * @var Property
     */
    private $to_compare;

    public function __construct(Property $to_compare)
    {
        parent::__construct();
        $this->to_compare = $to_compare;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return ($this->property->get() === $this->to_compare->get());
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->property->name() . ' is not matched with ' . $this->to_compare->name();
    }
}