<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 07.12.2014
 * Time: 11:48
 */

namespace core\db_drivers\query_results;

/**
 * Class QueryResult
 * @package core\db_drivers\query_results
 */
abstract class QueryResult
{
    /**
     * @var \mysqli_result|resource
     */
    protected $result;

    /**
     * @param \mysqli_result|resource $result
     */
    public function __construct($result)
    {
        $this->result = $result;
    }

    abstract public function row();

    abstract public function result();

    public function __toString()
    {
        return __CLASS__;
    }
}