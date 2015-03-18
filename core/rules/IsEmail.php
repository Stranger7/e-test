<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 07.10.2014
 * Time: 0:07
 */


namespace core\rules;

use core\generic\Rule;

/**
 * Class IsEmail
 * @package core\rules
 */
class IsEmail extends Rule
{
    /**
     * @return bool
     */
    public function isValid()
    {
        return filter_var($this->property->get(), FILTER_VALIDATE_EMAIL);
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->property->get() . ' is not valid E-mail';
    }
} 
