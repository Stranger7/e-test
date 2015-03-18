<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 05.12.2014
 * Time: 1:26
 */

namespace core\generic;

/**
 * Class Property
 * @package core\generic
 */
abstract class Property
{
    const NOT_INITIALIZED = null;

    /**
     * @var string
     */
    protected $name = self::NOT_INITIALIZED;

    /**
     * @var string
     */
    protected $title = self::NOT_INITIALIZED;

    /**
     * @var mixed
     */
    protected $value = self::NOT_INITIALIZED;

    /**
     * @var mixed
     */
    protected $default = self::NOT_INITIALIZED;

    /**
     * Used in schema create function
     *
     * @var string|null
     */
    protected $db_default = self::NOT_INITIALIZED;

    /**
     * if the read-only property, it is not used in the recording operations of the database
     * @var bool
     */
    protected $read_only = false;

    /**
     * @var mixed|null
     */
    protected $output_format = self::NOT_INITIALIZED;

    /**
     * @var array
     */
    private $rules = [];

    /**
     * @var array
     */
    private $errors = [];

    /*===============================================================*/
    /*                        M E T H O D S                          */
    /*===============================================================*/

    /**
     * @param string $name
     * @return \core\generic\Property
     */
    public function __construct($name)
    {
        $this->name($name);
        return $this;
    }

    /**
     * @return string
     */
    abstract public function type();

    /**
     * @param mixed $value
     * @param bool $with_cast
     * @return \core\generic\Property
     */
    public function set($value, $with_cast = true)
    {
        $this->value = ($with_cast ? $this->cast($value) : $value);
        return $this;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * @param mixed $output_format
     */
    public function setOutputFormat($output_format)
    {
        $this->output_format = $output_format;
    }

    /**
     * @param mixed $format
     * @return string
     */
    abstract public function asString($format = self::NOT_INITIALIZED);

    /**
     * This method returns the data prepared for "insert" and "update" operations in the database
     * @return string
     */
    abstract public function preparedForDb();

    /**
     * Sets for property self::NOT_INITIALIZED
     * @return \core\generic\Property
     */
    public function clear()
    {
        $this->value = self::NOT_INITIALIZED;
        return $this;
    }

    /**
     * Checks whether the property is initialized
     * @return bool
     */
    public function initialized()
    {
        return ($this->value !== self::NOT_INITIALIZED);
    }

    /**
     * @param mixed $default
     * @return \core\generic\Property
     */
    public function useAsDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @param null|string $db_default
     * @return \core\generic\Property|string
     */
    public function dbDefault($db_default = self::NOT_INITIALIZED)
    {
        if ($db_default === self::NOT_INITIALIZED) {
            return $this->db_default;
        } else {
            $this->db_default = strval($db_default);
            return $this;
        }
    }

    /**
     * Assigns default value to property if property not initialized
     */
    public function applyDefault()
    {
        if (!$this->initialized() && $this->default !== self::NOT_INITIALIZED) {
            $this->value = $this->default;
        }
        return $this->get();
    }

    /**
     * Return true if property is empty, otherwise false
     * @return bool
     */
    abstract public function isEmpty();

    /**
     * @param null|string $name
     * @return \core\generic\Property|string
     */
    public function name($name = self::NOT_INITIALIZED)
    {
        if ($name === self::NOT_INITIALIZED) {
            return $this->name;
        } else {
            $this->name = $name;
            return $this;
        }
    }

    /**
     * @param null|string $title
     * @return \core\generic\Property|string
     */
    public function title($title = self::NOT_INITIALIZED)
    {
        if ($title === self::NOT_INITIALIZED) {
            return $this->title;
        } else {
            $this->title = $title;
            return $this;
        }
    }

    /**
     * Cast data to the appropriate type
     * @param mixed $value
     * @return mixed
     */
    abstract protected function cast($value);

    /**
     * Setter
     * @param bool $read_only
     * @return \core\generic\Property
     */
    public function readOnly($read_only = true)
    {
        $this->read_only = $read_only;
        return $this;
    }

    /**
     * Getter
     * @return bool
     */
    public function isReadOnly()
    {
        return $this->read_only;
    }

    /**
     * Add rule to internal array
     * @param Rule $rule
     * @return \core\generic\Property
     */
    public function rule(Rule $rule)
    {
        $type = '\\' . get_class($rule);
        if ($this->hasRule($type)) {
            throw new \RuntimeException('Rule ' . $type
                . ' already assigned to property ' . $this->name());
        }
        $rule->forProperty($this);
        $this->rules[] = $rule;
        return $this;
    }

    /**
     * Validates field value
     * @return bool
     */
    public function isValid()
    {
        $valid = true;
        $this->errors = [];
        foreach ($this->rules as $rule) {
            $valid &= $this->applyRule($rule);
        }
        return $valid;
    }

    /**
     * Perform validation for specified rule
     * @param Rule $rule
     * @return bool
     */
    private function applyRule(Rule $rule)
    {
        if (!$rule->isValid()) {
            $this->addError($rule->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Add error to internal array
     * @param string $message
     */
    public function addError($message)
    {
        $this->errors[] = $message;
    }

    /**
     * Get errors with array
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Checks whether the property has a specified rule
     *
     * @param string $type
     * @return bool|Rule
     */
    public function hasRule($type)
    {
        foreach($this->rules as $rule)
        {
            if ($rule instanceof $type) {
                return $rule;
            }
        }
        return false;
    }
}