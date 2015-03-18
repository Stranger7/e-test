<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 06.01.2015
 * Time: 14:21
 */

namespace core\rules;

/**
 * Class IsUnsigned
 * @package core\rules
 */
class IsUnsigned extends GreaterOrEqual
{
    public function __construct()
    {
        parent::__construct(0);
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->property->name() . " is negative";
    }
}