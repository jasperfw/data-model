<?php

namespace JasperFW\DataModel;

use Exception;
use JasperFW\DataAccess\DAO;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Model
 *
 * The Model class is the base class of most data models that are linked to a database record. Generally, a model would
 * not extend this class directly, but would instead extend Item (for a single contact for example) or Grid (for a list
 * of contacts, for example).
 *
 * @package JasperFW\DataModel
 */
abstract class Model
{
    /** @var DAO The database connection this item uses */
    protected $dbc;
    /** @var LoggerInterface Reference to the logging system */
    protected $logger;
    /** @var string[] Error message generated while creating the item */
    protected $error = [];

    /**
     * Base constructor establishes basic settings for the object to power automatic functionality such as the creation
     * of database queries to populate and store the object.
     *
     * @param DAO             $dao
     * @param LoggerInterface $logger
     *
     * @throws Exception
     */
    public function __construct(DAO $dao, ?LoggerInterface $logger = null)
    {
        $this->dbc = $dao;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Allows an arbitrary data access object to be specified for the object.
     *
     * @param DAO $dao The data access object to use with objects.
     */
    public function overrideDataAccessObject(DAO $dao)
    {
        $this->dbc = $dao;
    }

    /**
     * Return a reference to the data access object
     *
     * @return DAO
     */
    public function getDataAccessObject(): DAO
    {
        return $this->dbc;
    }

    /**
     * Returns an array of errors or false if no errors were generated.
     *
     * @return string[] The arrays
     */
    public function getError(): array
    {
        return $this->error;
    }

    /**
     * Returns the errors stored in the object as a string.
     *
     * @return string The errors.
     */
    public function getErrorString()
    {
        return implode(' ', $this->error);
    }

    /**
     * Add an error to the array of errors. If a severity is entered, the error will also be logged through the logging
     * mechanism.
     *
     * @param string $message
     * @param int    $severity By default the severity is NOTICE
     * @param string $name     Optional name of the error
     */
    protected function logError($message, $severity = 0, $name = '')
    {
        if ('' == $name) {
            $this->error[] = $message;
        } else {
            $this->error[$name] = $message;
        }
        if ($severity > 0) {
            $this->logger->log($severity, $message);
        }
    }
}
