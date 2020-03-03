<?php

namespace JasperFW\DataModel\GridElements;

use InvalidArgumentException;

/**
 * Class Sorting
 *
 * Contains the sort field and the sort order
 *
 * @package JasperFW\DataModel\GridElements
 */
class Sorting
{
    /** @var string */
    protected $sort_field;
    /** @var string */
    protected $sort_order;

    /**
     * Sorting constructor.
     *
     * @param string $sort_field
     * @param string $sort_order
     *
     * @throws InvalidArgumentException If one of the values is not valid
     */
    public function __construct(string $sort_field = '', string $sort_order = '')
    {
        $this->setSortField($sort_field);
        $this->setSortOrder($sort_order);
    }

    public function getSortField(): string
    {
        return $this->sort_field;
    }

    public function setSortField(string $sort_field): void
    {
        $this->sort_field = $sort_field;
    }

    public function getSortOrder(): string
    {
        return $this->sort_order;
    }

    public function setSortOrder(string $sort_order): void
    {
        $sort_order = strtoupper($sort_order);
        if (!in_array($sort_order, ['ASC', 'DESC', ''])) {
            throw new InvalidArgumentException('The sort order provided is not valid.');
        }
        $this->sort_order = $sort_order;
    }
}