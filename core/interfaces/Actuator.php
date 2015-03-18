<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 08.12.2014
 * Time: 14:36
 */

namespace core\interfaces;

interface Actuator
{
    /**
     * @return mixed
     */
    public static function run();
}