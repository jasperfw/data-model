<?php

namespace JasperFW\DataModel;

use Exception;
use JasperFW\DataAccess\DAO;
use JasperFW\DataAccess\Exception\DatabaseQueryException;
use JasperFW\QueryBuilder\Query;
use Psr\Log\LoggerInterface;

/**
 * Class Grid
 *
 * Grid is a class to model tabular data. If a collection is needed, use the Collection utility class. Generally, a list
 * will be more appropriate in the context of a web application.
 *
 * @package JasperFW\DataModel
 */
abstract class Grid extends Model
{
    /** @var null|string The name of the field used to indentify unique rows - generally the primary key */
    protected static $id_column = null;
    /** @var null|string The name of the table in the database */
    protected static $table_name = null;
    /** @var null|array Nested array that defines the structure of the site. Defined by getGridStructure */
    protected $structure = null;

    /**
     * Base constructor establishes the grid
     *
     * @param DAO             $dao
     * @param LoggerInterface $logger
     *
     * @throws Exception
     */
    public function __construct(DAO $dao, ?LoggerInterface $logger = null)
    {
        parent::__construct($dao, $logger);
    }

    /**
     * Add a row to the table represented by this grid. If a table name, id column or structure for the table is not
     * defined above, this function will not work.
     *
     * @param string[] $data The parameters to be added to the row
     *
     * @return int The id of the generated row
     * @throws Exception If required values have not been set on the child class.
     */
    public function addRow(array $data = []): int
    {
        $this->crudEnabled();

        $table_name = static::$table_name;

        if (0 == count($data)) {
            // Insert a blank row
            $sql = "INSERT INTO {$table_name} DEFAULT VALUES"; // TODO: Move this to the DAO class
            $this->dbc->query($sql);
        } else {
            // Generate the values
            $components = $this->dbc->generateParameterizedComponents($data);
            $fields = $components['fields'];
            $values = $components['values'];
            $params = $components['params'];

            // Execute the query
            $sql = "INSERT INTO {$table_name} ({$fields}) VALUES ({$values})";
            $this->dbc->query($sql, ['params' => $params]);
        }
        // Return the id of the new field
        return $this->dbc->lastInsertId();
    }

    /**
     * Delete the row specified by the passed identifier. If the necessary crud fields have not been identified in the
     * child class, this function will throw an exception.
     *
     * @param int $id
     *
     * @throws Exception
     */
    public function deleteRow(int $id): void
    {
        $this->crudEnabled();

        // Generate the values
        $table_name = static::$table_name;
        $id_column = static::$id_column;

        $sql = "DELETE FROM {$table_name} WHERE {$id_column} = :key";
        $this->dbc->query($sql, ['params' => [':key' => $id]]);
    }

    /**
     * Updates an existing row, based on the passed identifier. If the necessary crud fields have not been identified
     * in the child class, this function will throw an exception.
     *
     * @param mixed $id   The id of the row to update
     * @param array $data The new data to enter
     *
     * @throws Exception
     */
    public function updateRow($id, array $data): void
    {
        $this->crudEnabled();

        // Generate the values
        $components = $this->dbc->generateParameterizedComponents($data);
        $table_name = static::$table_name;
        $id_column = static::$id_column;
        $fields = $components['update'];
        $params = $components['params'];
        $params[':key'] = $id;

        // Execute the query
        $sql = "UPDATE {$table_name} SET {$fields} WHERE {$id_column} = :key";
        $this->dbc->query($sql, ['params' => $params]);
    }

    /**
     * Get the data from the database
     *
     * @param Query|null $query The query object containing any sort, paging, or parameters
     *
     * @return array|null An array of data or null if an error occurred
     * @throws DatabaseQueryException
     */
    public function getData(?Query $query = null): array
    {
        Query::check($query, $this->dbc);
        return $query
            ->template($this->generateQuery())
            ->execute()
            ->toArray();
    }

    /**
     * Generate the query.
     *
     * @return string
     */
    abstract public function generateQuery(): string;
    // public function getData(QueryComponents $queryComponents) : array
    // {
    //     return $this->dbc->getStatement($this->generateQuery($queryComponents))
    //         ->execute($queryComponents->getParameters($this->dbc))->toArray();
    // }

    /**
     * Return true if the required information has been set to allow crud functionality to work. This function is
     * automatically called by the built in crud functions addRow, deleteRow and updateRow.
     *
     * @throws Exception
     */
    protected function crudEnabled(): void
    {
        if (null === static::$id_column || null === static::$table_name) {
            throw new Exception('Crud functionality is not enabled in class ' . __CLASS__ . '.');
        }
    }
}
