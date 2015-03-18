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
 * Time: 23:24
 */

namespace core\rules;

use core\generic\Rule;

/**
 * Class IsRequired
 * @package core\rules
 */
class IsRequired extends Rule
{
    /**
     * @return bool
     */
    public function isValid()
    {
        // for compatibility with PHP version less then 5.5
        $value = $this->property->get();
        return (!empty($value));
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->property->title() . ' is required';
    }
}
