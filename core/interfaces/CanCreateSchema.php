<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 04.01.2015
 * Time: 22:40
 */

namespace core\interfaces;

use core\generic\DbDriver;

interface CanCreateSchema
{
    /**
     * @param DbDriver|null $db
     * @return bool
     */
    public function createSchema(DbDriver $db = null);

    /**
     * @param DbDriver|null $db
     * @return bool
     */
    public function dropSchema(DbDriver $db = null);
}