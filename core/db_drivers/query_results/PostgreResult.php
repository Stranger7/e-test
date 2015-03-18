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
 * Time: 16:24
 */

namespace core\db_drivers\query_results;

/**
 * Class PostgreResult
 * @package core\db_drivers\query_results
 */
class PostgreResult extends QueryResult
{
    /**
     * @return object
     */
    public function row()
    {
        return pg_fetch_object($this->result);
    }

    /**
     * @return array
     */
    public function result()
    {
        $fetched = pg_fetch_all($this->result);
        return ($fetched ? $fetched : []);
    }
}