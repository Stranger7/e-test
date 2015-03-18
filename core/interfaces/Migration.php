<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 05.01.2015
 * Time: 1:45
 */

namespace core\interfaces;

use core\generic\DbDriver;

interface Migration
{
    /**
     * @param DbDriver $db
     * @return bool
     */
    public function up(DbDriver $db);

    /**
     * @param DbDriver $db
     * @return bool
     */
    public function down(DbDriver $db);
}