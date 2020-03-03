<?php

namespace JasperFW\DataModel;

use Exception;
use InvalidArgumentException;
use JasperFW\DataAccess\DAO;
use JasperFW\DataModel\Exception\ItemSaveDeletedException;
use JasperFW\DataModel\Exception\ItemSaveInvalidDataException;
use JasperFW\FormBuilder\Form;
use JasperFW\Validator\Validator\Number;
use Psr\Log\LoggerInterface;

/**
 * Class Item
 *
 * Basic class to represent single items that represent single items (typically) in the database. Provided a proper
 * structure is given, little to no custom code should be necessary to load the object from the database, validate new
 * values, and save new objects and changes to the database.
 *
 * <p>Child classes should have a protected static member $dbName. If that is present, this class will maintain a
 * reference to the database object where the data for this item is stored.
 *
 * <p>Child classes should define a protected array variable called structure. The keys in this array would correspond
 * to the names of properties of the object, and the value is an array comprised of the following: validator, points to
 * an InputType class that should be used to validate the entry (or null if custom validation will be done in the __set
 * method); constraints, which is an array of constraints to pass to the validator; default, a default value for the
 * field for new objects (this is optional).
 *
 * <p>If a property of the object is not setable or editable (for example, a field that is calculated by the database
 * such as age, when date of birth is known, enter false for the validator.
 *
 * <p>Unless overridden, the __set() function checks the specified property name exists (is defined in structure) and
 * if a validator is defined calls the validator to check the value before modifying it. The set() function will return
 * false if the validation failed. If overriding the __set() method, remember to call parent::set() at the end if the
 * default functionality is desired for properties that are not handled in the overriding method.
 *
 * @package JasperFW\DataModel
 */
abstract class Item extends Model
{
    /**
     * @var string The name of the table the object is stored in. If specified the object should be able to be saved
     *             and loaded automatically wihtout needing to override the save and load methods.
     */
    protected static $table_name;
    /**
     * @var string The name of the id field in the database.
     */
    protected static $id_field_name;
    /** @var int|null The id of the item, usually set in the constructor. May be null for new items. */
    protected $id;
    /**
     * @var array A list of properties, and the input validator that should be used to validate their input.
     * This should be defined in the child class. If there is custom validation for a property, the value should
     * instead be set as null. By default, validation is done in the __set method.
     * The structure here is a sample and should be replaced with valid values.
     */
    protected $structure = [
        'first_property' => [
            'validator' => '\JasperFW\Validator\Validator\Number',
            'constraints' => [
                [
                    'class' => '\JasperFW\Validator\Constraint\MinimumValue',
                    'rule' => 5,
                ],
                [
                    'class' => '\JasperFW\Validator\Constraint\MaximumValue',
                    'rule' => 10,
                ],
            ],
            'default' => 7,
            'optional' => true // Only include if the field value is optional
        ],
    ];
    /** @var array A key/value array of the properties of the item */
    protected $properties = [];
    /** @var bool True if the object has been loaded from the database */
    protected $is_loaded;
    /** @var bool False if a property has been changed but the changes have not been saved to the db */
    protected $in_sync;
    /** @var Form The form for this object */
    protected $form;
    /** @var bool True is the object was deleted from the db, not used universally */
    protected $is_deleted = false;

    /**
     * Child methods should invoke this constructor to validate and set the provided $id.
     *
     * @param int             $id
     * @param DAO             $dao
     * @param LoggerInterface $logger
     *
     * @throws Exception
     */
    public function __construct(?int $id, DAO $dao, LoggerInterface $logger = null)
    {
        parent::__construct($dao, $logger);
        if (!is_null($id) && null === Number::quickValidate($id)) {
            throw new Exception('The specified id is not valid.');
        }
        $this->id = $id;
        $this->is_loaded = false;
        $this->in_sync = true;
        // Create the properties array from the structure definition
        foreach ($this->structure as $key => $value) {
            $this->properties[$key] = (isset($value['default'])) ? $value['default'] : '';
        }
        $this->form = Form::factory($this->structure);
        $this->buildForm();
    }

    /**
     * Returns a property of the item. If the item has not been retrieved from the database, calls the load() function
     * to load the data. If there are custom fields that need to be accessed in a special way, this method can be
     * overridden. The method must start by checking if the data is_loaded.
     *
     * @param string $name The name of the property
     *
     * @return mixed The value of the requested property or null if the property is not defined.
     */
    public function __get(string $name)
    {
        if ($name == 'form') {
            return $this->getForm();
        }
        if ($this->is_loaded == false) {
            $this->load();
        }
        if ($this->form->$name != false) {
            return $this->form->$name->getValue();
        } else {
            $this->logger->error('Non-existant property ' . $name . ' requested from ' . __CLASS__);
            return null;
        }
    }

    /**
     * Adds the value to the form object for this model. If custom validation of incoming data is needed, override this
     * class and perform the validation before calling this function.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return bool True if the value was saved successfully.
     */
    public function __set(string $name, $value): bool
    {
        if ($this->form->$name != false) {
            $this->form->$name->setUserValue($value);
            $this->in_sync = false;
            return true;
        }
        return false;
    }

    /**
     * Returns the id of the data associated with this model.
     *
     * @return int|null
     */
    public function getID(): ?int
    {
        return $this->id;
    }

    /**
     * Returns the form object associated with the model.
     *
     * @return Form
     */
    public function getForm(): Form
    {
        return $this->form;
    }

    /**
     * Return the array of properties of the object. This includes everything but the id.
     *
     * @param bool $for_database Set to true if the keys should be db column names
     *
     * @return array
     */
    public function toArray(bool $for_database = false): array
    {
        if ($this->is_loaded == false) {
            $this->load();
        }
        return $this->form->toArray($for_database);
    }

    /**
     * Return true if the object has been loaded from the database.
     *
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->is_loaded;
    }

    /**
     * Returns true if there are no changes.
     *
     * @return bool
     */
    public function isSynced(): bool
    {
        return $this->in_sync;
    }

    /**
     * Checks if this is a newly created or blank object, rather than one that was retrieved from the database.
     *
     * @return bool True if this is a new object
     */
    public function isNew(): bool
    {
        return is_null($this->id);
    }

    /**
     * Check that the submitted values are valid. By default this just uses the forms validation check. This can be
     * overridden to perform class specific checks however.
     *
     * @return bool True if the form is valid, false otherwise.
     */
    public function isValid(): bool
    {
        return $this->form->isValid();
    }

    /**
     * Check if the item has been deleted from the database.
     *
     * @return bool True if the item has been deleted
     */
    public function isDeleted(): bool
    {
        return $this->is_deleted;
    }

    /**
     * Checks if the object is missing values for required fields.
     */
    public function hasRequiredValues(): bool
    {
        $missing = [];
        if ($this->is_loaded === false) {
            $this->load();
        }
        foreach ($this->properties as $name => $value) {
            if ($value == '') {
                if ((isset($this->structure[$name]['optional']) && $this->structure[$name]['optional'] === true) ||
                    $this->structure[$name]['validator'] == false) {
                    // This is fine
                } else {
                    $missing[] = $name;
                }
            }
        }
        if (count($missing) == 0) {
            return true;
        }
        $this->logger->info('The object is missing values for: ' . implode(', ', $missing));
        return false;
    }

    /**
     * Override this function to populate the properties of the object from the database. This function MUST set
     * is_loaded to true on success.
     *
     * @return bool The success of the load
     */
    public function load(): bool
    {
        if (true == $this->isDeleted()) {
            $this->logger->warning('Attempted to load a deleted object');
            return false;
        }
        if (true === $this->is_loaded) {
            return true;
        } // Already loaded
        if ($this->isNew()) {
            return false;
        } // Can't load something that isn't in the db yet

        $table_name = $this->getTableName();
        $id_field_name = $this->getIdFieldName();
        $field_names = $this->form->getDbSelectFieldNames();
        $field_names = implode(', ', $field_names);
        $sql = "SELECT {$field_names} FROM {$table_name} WHERE {$id_field_name} = :id";
        $result = $this->dbc->query($sql, ['params' => [':id' => $this->id]])->toArray();
        if (count($result) > 0) {
            $this->getForm()->populate($result[0], false);
            $this->is_loaded = true;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Override to save the data if there have been changes.
     *
     * @return bool True if the save succeeded
     * @throws ItemSaveDeletedException if the item has been deleted
     * @throws ItemSaveInvalidDataException if the item has invalid data
     */
    public function save(): bool
    {
        $this->logger->debug('Attempting to save');
        // Make sure the item can be saved
        if (true == $this->isDeleted()) {
            throw new ItemSaveDeletedException('Attempted to save a deleted object');
        }
        if (!$this->isValid()) {
            throw new ItemSaveInvalidDataException('Required values are missing or invalid');
        }
        // Prepare the data
        $components = $this->prepareData();
        // Generate the sql query
        $query = $this->generateSaveQuery($components);
        // Save the data
        $this->executeSaveQuery($query, $components['params']);
        // Commit the data since the form data has been saved.
        $this->form->commit();
        $this->logger->debug('Item was saved and committed.');
        return true;
    }

    /**
     * Extend this method to add additional functionality to customize the form. For example, to populate a select field
     * with options.
     */
    protected function buildForm()
    {
    }

    /**
     * Gets the name of the table. This is mostly used in autogenerating queries. By default, uses the static table name
     * value set in the extending class.
     *
     * @return string
     */
    protected function getTableName(): string
    {
        if (!isset(static::$table_name)) {
            throw new InvalidArgumentException('A table name was not specified for the class ' . __CLASS__);
        }
        return static::$table_name;
    }

    /**
     * Gets the name of the id field for the table. This is mostly used for autogenerating queries. By default, returns
     * the static id field name set in the extending class.
     *
     * @return string
     */
    protected function getIdFieldName(): string
    {
        if (!isset(static::$id_field_name)) {
            throw new InvalidArgumentException('The ID field name was not specified for the class ' . __CLASS__);
        }
        return static::$id_field_name;
    }

    /**
     * Prepare the data for processing, returning an array of components that can then be used in a query.
     */
    protected function prepareData(): array
    {
        $data = $this->toArray(true);
        $id_field_name = $this->getIdFieldName();
        if (!is_null($id_field_name)) {
            foreach ($data AS $key => $value) {
                if ($key == $id_field_name) {
                    unset($data[$key]);
                    break;
                }
            }
        }
        return $this->dbc->generateParameterizedComponents($data);
    }

    /**
     * Generate the insert or update query to be run when saving this item into the database
     *
     * @param array $components The query components that will be used to generate the query
     *
     * @return string
     */
    protected function generateSaveQuery(array $components): string
    {
        $table_name = $this->getTableName();
        if ($this->isNew()) {
            $fields = $components['fields'];
            $values = $components['values'];
            $query = "insert into {$table_name} ({$fields}) values ({$values})";
        } else {
            $id_field_name = $this->getIdFieldName();
            $fields = $components['update'];
            $query = "update {$table_name} set {$fields} WHERE {$id_field_name} = :id";
        }
        return $query;
    }

    protected function executeSaveQuery(string $query, array $params): void
    {
        $this->logger->debug('Saving item');
        $this->dbc->query($query, ['params' => $params]);
        $this->in_sync = true;
        // Get the id of the inserted record if possible/necessary
        if ($this->id == null) {
            // Try to get the id of the newly created ticket
            $this->id = $this->dbc->lastInsertId();
            $this->logger->debug('Inserted item ' . $this->id);
        }
    }
}
