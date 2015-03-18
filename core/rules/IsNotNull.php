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
 * Time: 13:37
 */

namespace core\rules;

use core\generic\Rule;

/**
 * Class IsNotNull
 * @package core\rules
 */
class IsNotNull extends Rule
{
    /**
     * @return bool
     */
    public function isValid()
    {
        return ($this->property->initialized());
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->property->title() . ' can\'t be null';
    }
}