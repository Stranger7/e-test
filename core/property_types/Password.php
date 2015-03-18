<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 16.12.2014
 * Time: 22:56
 */

namespace core\property_types;

/**
 * Class Password
 * @package core\property_types
 */
class Password extends String
{
    /**
     * @var string
     */
    private $salt;

    /**
     * @param string $string
     * @param string $salt
     * @return string
     */
    public static function crypt($string, $salt)
    {
        return crypt($string, $salt);
    }

    /**
     * @param $password
     * @return bool
     */
    public function verify($password)
    {
        return ($this->get() === self::crypt($password, $this->salt));
    }

    /**
     * @param string $salt
     * @return $this
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
        return $this;
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }
}