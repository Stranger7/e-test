<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 26.09.2014
 * Time: 21:05
 */

namespace core\generic;

use core\DbManager;

/**
 * Class Crystal
 * @package core\generic
 */
abstract class Controller
{
    /**
     * @var DbDriver
     */
    private $db = null;

    public function __construct($dsn = '')
    {
        $this->setDb($dsn);
    }

    /**
     * @return DbDriver
     */
    public function db()
    {
        return $this->db;
    }

    /**
     * @param string $dsn
     */
    public function setDb($dsn = '')
    {
        $this->db = DbManager::getInstance()->getDb($dsn);
    }
}