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
 * Time: 13:14
 */

namespace core\generic;

use core\DbManager;
use core\interfaces\CanCreateSchema;
use core\rules\IsUniqueComposite;

/**
 * Class Model
 * @package core\generic
 */
abstract class Model implements CanCreateSchema
{
    /**
     * @var DbDriver
     */
    protected $db;

    /**
     * @var string
     */
    private $table_name;

    /**
     * @var Property
     */
    public $id = Property::NOT_INITIALIZED;

    /**
     * @var array of Property
     */
    protected $properties = [];

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $indexes = [];

    /*===============================================================*/
    /*                  I N I T I A L I Z A T I O N                  */
    /*===============================================================*/

    /**
     * Typical use case
     *
     * Specify the identifier
     *
     * $this->setTableName('customers');
     * $this->id = new Integer('id')->title('Identifier')->rule(new isRequired());
     * $this->property('name', 'String')->title('Customer')->rule(new isRequired());
     * $this->property('email', 'String')->title('E-mail')
     *      ->rule(new isRequired())
     *      ->rule(new isEmail());
     *
     * @param string $dsn
     */
    public function __construct($dsn = '')
    {
        $this->db = DbManager::getInstance()->getDb($dsn);
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->table_name;
    }

    /**
     * Specifies table name
     * @param string $table_name
     */
    public function setTableName($table_name)
    {
        $this->table_name = $table_name;
    }

    /**
     * Create a property and add into the internal array
     * @param string $name
     * @param string $type
     * @return Property
     */
    protected function property($name, $type)
    {
        $class_name = $this->makePropertyClass($type);
        $this->$name = new $class_name($name);
        $this->properties[$name] = &$this->$name;
        return $this->$name;
    }

    /**
     * Create identifier property
     * @param string $name
     * @param string $type
     * @return Property
     */
    protected function identifier($name, $type = 'Serial')
    {
        $class_name = $this->makePropertyClass($type);
        $this->id = new $class_name($name);
        return $this->id;
    }

    /**
     * Checks and prepare class name for property
     * @param string $type
     * @return string
     */
    private function makePropertyClass($type)
    {
        $class_name = '\\core\\property_types\\' . $type;
        if (!class_exists($class_name)) {
            throw new \RuntimeException("Invalid property type: {$type}");
        }
        return $class_name;
    }

    /**
     * Add simple index
     *
     * @param array $fields
     */
    protected function index($fields)
    {
        $this->addIndex($fields, DbDriver::INDEX);
    }

    /**
     * Add unique index
     *
     * @param array $fields
     */
    protected function uniqueIndex($fields)
    {
        $this->addIndex($fields, DbDriver::UNIQUE_INDEX);
    }

    /**
     * Add unique index
     *
     * @param array $fields
     */
    protected function primaryKey($fields)
    {
        foreach($this->indexes as $index)
        {
            if ($index['type'] == DbDriver::PRIMARY_KEY) {
                throw new \RuntimeException('PRIMARY KEY already defined');
            }
        }
        $this->addIndex($fields, DbDriver::PRIMARY_KEY);
    }

    /**
     * @param array $fields
     * @param string $type: INDEX | UNIQUE INDEX | PRIMARY KEY
     */
    private function addIndex($fields, $type)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        if (!$this->instanceOfProperty($fields)) {
            throw new \RuntimeException('Fields of index does not correspond'
                .' to the type of \core\generic\Property');
        }
        $this->indexes[] = [
            'type'   => $type,
            'fields' => $fields
        ];
    }

    /**
     * @param array $properties
     * @return bool
     */
    private function instanceOfProperty($properties)
    {
        foreach($properties as $property)
        {
            if (!($property instanceof Property)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Typical use case in controller: $model->setValues($this->request->post());
     * @param $values
     * @return bool
     */
    public function setValues($values)
    {
        if (is_array($values) || is_object($values))
        {
            foreach ($values as $property_name => $value)
            {
                if (isset($this->properties[$property_name])) {
                    $this->setPropertyValue($this->properties[$property_name], $value);
                } else {
                    if ($property_name == $this->id->name()) {
                        $this->id->set($value);
                    }
                }
            }
            return true;
        }
        return false;
    }

    private function setPropertyValue(Property $property, $value, $with_cast = true)
    {
        $property->set($value, $with_cast);
    }

    /*===============================================================*/
    /*                        C R E A T E                            */
    /*===============================================================*/
    protected function beforeCreate()
    {
        $this->idInitializationCheck();
        $this->setDefaults();
        return $this->validation('create');
    }

    /**
     * @return bool
     */
    public function create()
    {
        if ($this->beforeCreate())
        {
            $this->id->set(
                $this->db->insert(
                    $this->getTableName(),
                    $this->createParamArray(),
                    $this->id->name()
                )
            );
            return $this->afterCreate();
        }
        return false;
    }

    protected function afterCreate()
    {
        return true;
    }

    /*===============================================================*/
    /*                        U P D A T E                            */
    /*===============================================================*/
    protected function beforeUpdate()
    {
        $this->idInitializationCheck(true);
        return $this->validation('update');
    }

    public function update()
    {
        if ($this->beforeUpdate()) {
            $this->db
                ->update($this->getTableName(), $this->createParamArray())
                ->where("{$this->id->name()} = ?", $this->id->get())->run();
            return $this->afterUpdate();
        }
        return false;
    }

    protected function afterUpdate()
    {
        return true;
    }

    /*===============================================================*/
    /*                        D E L E T E                            */
    /*===============================================================*/
    protected function beforeDelete()
    {
        $this->idInitializationCheck(true);
        return true;
    }

    public function delete()
    {
        if ($this->beforeDelete()) {
            $this->db
                ->delete($this->getTableName())
                ->where("{$this->id->name()} = ?", $this->id->get())
                ->run();
            return $this->afterDelete();
        }
        return false;
    }

    protected function afterDelete()
    {
        return true;
    }

    /*===============================================================*/
    /*                         S E L E C T                           */
    /*===============================================================*/

    /**
     * @param mixed $id
     * @return bool|\core\db_drivers\query_results\QueryResult
     */
    public function findById($id = Property::NOT_INITIALIZED)
    {
        if ($id !== Property::NOT_INITIALIZED) {
            $this->id->set($id);
        }
        if (!$this->id->initialized()) {
            return false;
        }
        $row = $this->db
            ->select()
            ->from($this->getTableName())
            ->where($this->id->name() . ' = ? ', $this->id->get())
            ->run()->row();
        if ($row) {
            $this->setValues($row);
        } else {
            $this->id->clear();
        }
        return $row;
    }

    /**
     * Checking the existence of records with specified identifier
     *
     * @param mixed $id
     * @return bool
     */
    public function existWithId($id = Property::NOT_INITIALIZED)
    {
        if ($id === Property::NOT_INITIALIZED) {
            if ($this->id->initialized() && !$this->id->isEmpty()) {
                $id = $this->id->get();
            } else {
                return false;
            }
        }
        return $this->db
            ->select()
            ->from($this->getTableName())
            ->where($this->id->name() . ' = ? ', $id)
            ->run()->row();
    }

    /**
     * Typical uses: for serial model initialization values select query
     *
     * @param mixed $data_source
     * @return \Generator
     */
    public function iterator($data_source)
    {
        if (is_array($data_source) || is_object($data_source))
        {
            foreach($data_source as $row)
            {
                $this->setValues($row);
                yield $this;
            }
        }
    }

    /*===============================================================*/
    /*                           S A V E                             */
    /*===============================================================*/

    /**
     * If a record exists, it is updated, otherwise the record is created
     *
     * @return bool
     */
    public function save()
    {
        if ($this->existWithId()) {
            return $this->update();
        } else {
            return $this->create();
        }
    }

    /*===============================================================*/
    /*                     V A L I D A T I O N                       */
    /*===============================================================*/
    /**
     * Do validation before create and update.
     * May be overridden in child class
     * @param string $operation : 'create' or 'update'
     * @return bool
     */
    protected function validation($operation = 'create')
    {
        foreach($this->properties as $name => $property)
        {
            /** @var Property $property */
            $do_validation = ($operation == 'create') ? true : $property->initialized();
            if (!$property->isReadOnly() && $do_validation)
            {
                if (!$property->isValid())
                {
                    $this->addError($property->name(), $property->getErrors());
                }
            }
        }

        // check composite unique indexes
        foreach($this->indexes as $index)
        {
            if ($index['type'] == DbDriver::UNIQUE_INDEX) {
                $validator = new IsUniqueComposite(
                    $this->db,
                    $this->getTableName(),
                    $this->id,
                    $index['fields']
                );
                if (!$validator->isValid()) {
                    $this->addError($validator->getFields(), $validator->getMessage());
                }
                $validator = null;
            }
        }
        return empty($this->errors);
    }

    /**
     * Add error to internal array
     * @param string $name
     * @param string|array $errors
     */
    protected function addError($name, $errors)
    {
        $this->errors[$name] = $errors;
    }

    /**
     * @return array of errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Clears error array
     */
    public function clearErrors()
    {
        $this->errors = [];
    }

    /*===============================================================*/
    /*                   M I S C E L L A N E O U S                   */
    /*===============================================================*/

    /**
     * Assign default values for properties if they are not initialized
     */
    private function setDefaults()
    {
        /** @var Property $property */
        foreach($this->properties as $name => $property)
        {
            $property->applyDefault();
        }
    }

    /**
     * Create parameters array for create and update operations.
     * @return array
     */
    private function createParamArray()
    {
        $data = [];
        if ($this->id->initialized() && !$this->id->isEmpty()) {
            $data[$this->id->name()] = $this->id->preparedForDb();
        }
        /** @var Property $property */
        foreach($this->properties as $name => $property)
        {
            if ($property->initialized() && !$property->isReadOnly())
            {
                $data[$property->name()] = $property->preparedForDb();
            }
        }
        return $data;
    }

    /**
     * @param bool $id_value_check
     */
    private function idInitializationCheck($id_value_check = false)
    {
        if (is_null($this->id)) {
            throw new \LogicException('Id field not defined', 501);
        }
        if ($id_value_check && !$this->id->initialized()) {
            throw new \LogicException('Id is not set', 501);
        }
    }

    /*===============================================================*/
    /*                S C H E M A    F U N C T I O N S               */
    /*===============================================================*/

    /**
     * @param DbDriver|null $db
     * @return bool
     */
    public function createSchema(DbDriver $db = null)
    {
        // Create table. Also create primary key, unique indexes and references
        $this->db->createTable($this->getTableName(), [
            'fields'       => $this->makeFieldDescriptors(),
            'primary_key'  => $this->makePrimaryKeyDescriptor(),
            'unique'       => $this->makeUniqueKeyDescriptors(),
            'foreign_keys' => $this->makeForeignKeyDescriptors()
        ]);

        // Create indexes
        foreach($this->indexes as $index)
        {
            if ($index['type'] == DbDriver::INDEX) {
                $fields = [];
                array_walk($index['fields'], function(Property $property) use (&$fields) {
                    $fields[] = $property->name();
                });
                $this->db->createIndex($this->getTableName(), $fields);
            }
        }
    }

    /**
     * @param DbDriver|null $db
     * @return bool
     */
    public function dropSchema(DbDriver $db = null)
    {
        $this->db->dropTable($this->getTableName());
    }

    /**
     * @return array
     */
    private function makeFieldDescriptors()
    {
        $descriptors = [];
        if (!empty($this->id) && !$this->id->isReadOnly()) {
            $descriptors[$this->id->name()] = $this->makeFieldDescriptor($this->id);
        }
        /** @var Property $property */
        foreach($this->properties as $name => $property)
        {
            if ($property->isReadOnly()) continue;
            $descriptors[$name] = $this->makeFieldDescriptor($property);
        }
        return $descriptors;
    }

    /**
     * @param Property $property
     * @return array
     */
    private function makeFieldDescriptor(Property $property)
    {
        $descriptor = [];
        $descriptor['type'] = $property->type();

        // Automatically assign the primary key when the primary key is not defined clearly
        if ($descriptor['type'] == 'SERIAL')
        {
            $primary_key_specified = false;
            foreach($this->indexes as $index)
            {
                if ($index['type'] == DbDriver::PRIMARY_KEY) {
                    $primary_key_specified = true;
                    break;
                }
            }
            if (!$primary_key_specified) {
                $descriptor['primary_key'] = true;
            }
        } else {
            if ($property->hasRule('\core\rules\IsUnique')) {
                $descriptor['unique'] = true;
            }
        }
        if ($rule = $property->hasRule('\core\rules\MaxLen')) {
            /** @var \core\rules\MaxLen $rule */
            $descriptor['size'] = $rule->getMaxLen();
        }
        if ($property->hasRule('\core\rules\IsNotNull')
            || $property->hasRule('\core\rules\IsRequired')
            || $property->hasRule('\core\rules\IsNotZero')) {
            $descriptor['not_null'] = true;
        }
        if ($property->hasRule('\core\rules\IsUnsigned')) {
            $descriptor['unsigned'] = true;
        }
        if (!empty($property->dbDefault())) {
            $descriptor['default'] = $property->dbDefault();
        }
        return $descriptor;
    }

    /**
     * @return array
     */
    private function makePrimaryKeyDescriptor()
    {
        $result = [];
        foreach($this->indexes as $index)
        {
            if ($index['type'] == DbDriver::PRIMARY_KEY) {
                array_walk($index['fields'], function(Property $property) use (&$result) {
                    $result[] = $property->name();
                });
                break;
            }
        }
        return $result;
    }

    /**
     * @return array
     */
    private function makeUniqueKeyDescriptors()
    {
        $result = [];
        foreach($this->indexes as $index)
        {
            if ($index['type'] == DbDriver::UNIQUE_INDEX) {
                $fields = [];
                array_walk($index['fields'], function(Property $property) use (&$fields) {
                    $fields[] = $property->name();
                });
                $result[] = $fields;
            }
        }
        return $result;
    }

    /**
     * @return array
     */
    private function makeForeignKeyDescriptors()
    {
        $result = [];
        /** @var Property $property */
        foreach($this->properties as $name => $property)
        {
            if ($property->isReadOnly()) continue;
            /** @var \core\rules\BelongsTo $rule */
            if ($rule = $property->hasRule('\core\rules\BelongsTo')) {
                $foreign_key['columns'] = [$property->name()];
                $foreign_key['ref_table'] = $rule->getReferencedTable();
                $foreign_key['ref_columns'] = [$rule->getReferencedColumn()];
                $foreign_key['on_update'] = $rule->getOnUpdate();
                $foreign_key['on_delete'] = $rule->getOnDelete();
                $result[] = $foreign_key;
            }
        }
        return $result;
    }
}
