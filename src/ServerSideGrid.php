<?php

namespace JasperFW\DataModel;

use Exception;

/**
 * Interface ServerSideGrid
 *
 * The ServerSideGrid Interface promises functionality required for server side paging, sorting and filtering.
 *
 * @package JasperFW\DataModel
 */
interface ServerSideGrid
{
    /**
     * Gets the data query to execute to retrieve the records from the database.
     *
     * @param array $where   Strings to include in the where clause
     * @param array $options Additional options including paging and filtering
     *
     * @return string
     * @throws Exception If the column definitions are not set.
     */
    public function getDataQuery(array $where, array &$options): string;

    /**
     * Creates the required filters based on the filter information provided.
     *
     * @param array $options
     *
     * @return array containing 'where' and 'params' elements.
     * @throws Exception If the column definitions are not set.
     */
    public function processFilters(array $options): array;

    /**
     * Handles the paging by creating an offset requirement
     *
     * @param array $options
     *
     * @return string The paging query snippet
     * @throws Exception If the column definitions are not set.
     */
    public function processPaging(array $options): string;

    /**
     * Handles sorting by creating the ORDER BY clause
     *
     * @param array $options
     *
     * @return string The order by clause to insert into the query
     * @throws Exception If the column definitions are not set.
     */
    public function processSorting(array $options): string;

    /**
     * Get the configuration elements (Paging, Sorting and Filtering) from the request.
     *
     * @param string $method The request method
     *
     * @return array The configuration elements
     */
    public function getConfigurationFromRequest(string $method = 'get'): array;

    /**
     * Get the filter information from the query
     *
     * @param string $method The request method
     *
     * @return array
     */
    public function getFiltersFromRequest(string $method = 'get'): array;

    /**
     * Get the paging settings from the query
     *
     * @param string $method
     *
     * @return array
     */
    public function getPagingFromRequest(string $method = 'get'): array;

    /**
     * Get the sorting settings from the query
     *
     * @param string $method
     *
     * @return array
     */
    public function getSortingFromRequest(string $method = 'get'): array;
}