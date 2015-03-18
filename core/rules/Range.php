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
 * Time: 1:14
 */

namespace core\rules;

use core\generic\Rule;

class Range extends Rule
{
    /**
     * @var mixed
     */
    private $min;

    /**
     * @var mixed
     */
    private $max;

    /**
     * @param mixed $min
     * @param mixed $max
     */
    public function __construct($min, $max)
    {
        parent::__construct();
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return ($this->property->get() >= $this->min && $this->property->get() <= $this->max);
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->property->name() . " is not in range [{$this->min}..{$this->max}]";
    }
}