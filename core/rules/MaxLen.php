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
 * Time: 14:28
 */

namespace core\rules;

use core\generic\Rule;

/**
 * Class MaxLen
 * @package core\rules
 */
class MaxLen extends Rule
{
    /**
     * @var int
     */
    protected $max_len;

    /**
     * @param int $max_len
     */
    public function __construct($max_len = 0)
    {
        parent::__construct();
        $this->max_len = intval($max_len);
    }

    /**
     * Truncate string if its length is greater than the allowable
     *
     * @return true
     */
    public function isValid()
    {
        $value = $this->property->get();
        if (is_string($value)) {
            if (strlen($value) > $this->max_len) {
                $this->property->set(substr($value, 0, $this->max_len));
            }
        }
        return true;
    }

    /**
     * @return int
     */
    public function getMaxLen()
    {
        return $this->max_len;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return '';
    }
}