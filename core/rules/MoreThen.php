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
 * Time: 1:33
 */

namespace core\rules;

use core\generic\Rule;

/**
 * Class MoreThen
 * @package core\rules
 */
class MoreThen extends Rule
{
    /**
     * @var mixed
     */
    protected $min;

    /**
     * @param mixed $min
     */
    public function __construct($min)
    {
        parent::__construct();
        $this->min = $min;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return ($this->property->get() > $this->min);
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->property->name() . " less then {$this->min} or equal";
    }
}