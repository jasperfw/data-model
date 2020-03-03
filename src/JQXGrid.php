<?php

namespace JasperFW\DataModel;

use Exception;

/**
 * Class JQXGrid
 *
 * @package JasperFW\DataModel
 */
abstract class JQXGrid extends Grid implements ServerSideGrid
{
    /** @var string The name of the field in the db to sort on by default */
    protected $sort_field;

    /**
     * @param array $options
     *
     * @return array containing 'where' and 'params' elements.
     * @throws Exception If the column definitions are not set.
     */
    public function processFilters(array $options): array
    {
        if (!is_array($options['filtergroups'])) {
            return ['where' => [], 'params' => []];
        }
        if (!isset($this->column_definitions)) {
            throw new Exception('Column definitions have not been set!');
        }
        $filtergroups = $options['filtergroups'];
        $params = [];
        $clauses = [];
        foreach ($filtergroups as $filtergroup) {
            $field = $filtergroup['field'];
            if (!isset($this->column_definitions[$field])) {
                // Don't process this filter if its not a valid column
                continue;
            }
            $db_name = $this->column_definitions[$field]['dbname'];
            $clause = '(';
            foreach ($filtergroup['filters'] as $idx => $filter) {
                if (0 != $idx) {
                    $clause .= ($filter['operator'] == 'or') ? ' OR ' : ' AND ';
                }
                switch ($filter['condition']) {
                    case "CONTAINS":
                        $condition = " LIKE ";
                        $value = "%" . $filter['value'] . "%";
                        break;
                    case "DOES_NOT_CONTAIN":
                        $condition = " NOT LIKE ";
                        $value = "%" . $filter['value'] . "%";
                        break;
                    case "EQUAL":
                        $condition = " = ";
                        $value = $filter['value'];
                        break;
                    case "NOT_EQUAL":
                        $condition = " <> ";
                        $value = $filter['value'];
                        break;
                    case "GREATER_THAN":
                        $condition = " > ";
                        $value = $filter['value'];
                        break;
                    case "LESS_THAN":
                        $condition = " < ";
                        $value = $filter['value'];
                        break;
                    case "GREATER_THAN_OR_EQUAL":
                        $condition = " >= ";
                        $value = $filter['value'];
                        break;
                    case "LESS_THAN_OR_EQUAL":
                        $condition = " <= ";
                        $value = $filter['value'];
                        break;
                    case "STARTS_WITH":
                        $condition = " LIKE ";
                        $value = $filter['value'] . "%";
                        break;
                    case "ENDS_WITH":
                        $condition = " LIKE ";
                        $value = "%" . $filter['value'];
                        break;
                    case "NULL":
                        $condition = " IS NULL ";
                        $value = "";
                        break;
                    case "NOT_NULL":
                        $condition = " IS NOT NULL ";
                        $value = "";
                        break;
                    default:
                        // No point processing if the condition is not recognized
                        continue 2;
                }
                $clause .= $db_name . " " . $condition;
                if ($value != "") {
                    $param_name = ':p' . count($params);
                    $clause .= " " . $param_name;
                    $params[$param_name] = $value;
                }
            }
            $clause .= ')';
            $clauses[] = $clause;
        }
        return ['where' => $clauses, 'params' => $params];
    }

    public function processPaging(array $options): string
    {
        if (!isset($this->column_definitions)) {
            throw new Exception('Column definitions have not been set!');
        }
        if (isset($options['pagesize']) && isset($options['pagenum'])) {
            $pagesize = $options['pagesize'];
            $pagenumber = $options['pagenum'] + 1;
            $offset = $pagesize * ($pagenumber - 1);
            return "OFFSET {$offset} ROWS FETCH NEXT {$pagesize} ROWS ONLY";
        }
        return '';
    }

    public function processSorting(array $options): string
    {
        if (!isset($this->column_definitions)) {
            throw new Exception('Column definitions have not been set!');
        }
        if (isset($options['sortdatafield']) && isset($options['sortorder'])) {
            $field = $options['field'];
            if (!isset($this->column_definitions[$field])) {
                $field = $this->sort_field;
            }
            $sortorder = (strtoupper($options['sortorder']) === 'ASC') ? 'ASC' : 'DESC';
            return "ORDER BY {$field} {$sortorder}";
        }
        return '';
    }

    public function getConfigurationFromRequest(string $method = 'get'): array
    {
        return [];
    }

    public function getFiltersFromRequest(string $method = 'get'): array
    {
        return [];
    }

    public function getPagingFromRequest(string $method = 'get'): array
    {
        return [];
    }

    public function getSortingFromRequest(string $method = 'get'): array
    {
        return [];
    }
}