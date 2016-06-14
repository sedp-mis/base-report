<?php

namespace SedpMis\BaseReport\GridQuery;

use SedpMis\BaseReport\ModelGridQueries\MgqService;
use SedpMis\BaseGridQuery\BaseGridQuery;

abstract class BaseReportGridQuery extends BaseGridQuery
{
    /**
     * Start date. Mysql Date string format.
     *
     * @var string
     */
    protected $startDate;

    /**
     * End date. Mysql Date string format.
     *
     * @var string
     */
    protected $endDate;

    /**
     * Array of modelGridQueries.
     *
     * @var array[\SedpMis\BaseReport\ModelGridQueries\ModelGridQueryInterface]
     */
    protected $modelGridQueries = [];

    /**
     * Array of column presenation.
     *
     * @var array
     */
    protected $columnPresentations = [
        'member'  => 'memberColumns',
        'summary' => 'summaryColumns'
    ];

    /**
     * Set the date filters for the report grid.
     *
     * @param string $startDate
     * @param string $endDate
     * @return $this
     */
    public function setDatesFilter($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;

        return $this;
    }

    /**
     * Return a columnedReportGridQuery instance to query the appropriate columns for a specific columnPresentation.
     * 
     * @param  string $columnPresentation
     * @return \SedpMis\BaseReport\GridQuery\MemberReportGridQuery|$this
     */
    public function columnedReportGridQuery($columnPresentation)
    {
        return new ColumnedReportGridQuery($this, $this->getColumnsToDisplay($columnPresentation), $this->getModelGridQueries());
    }

    /**
     * Get the columns declaration for a specific columnPresentation.
     * 
     * @param  string $columnPresentation
     * @return array
     */
    public function getColumnsToDisplay($columnPresentation)
    {
        if (!$this->hasColumnPresentation($columnPresentation)) {
            throw new \Exception("Invalid column presentation `{$columnPresentation}`.");
        }

        $method = $this->columnPresentations[$columnPresentation];

        return $this->{$method}();
    }

    /**
     * Return true if reportGridQuery has the columnPresentation.
     * 
     * @param  string  $columnPresentation
     * @return boolean
     */
    public function hasColumnPresentation($columnPresentation)
    {
        return array_key_exists($columnPresentation, $this->columnPresentations);
    }

    /**
     * Apply modelGridQueries' joins.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $tableAlias
     * @param  string|null $foreignTable
     * @param  string|null $foreignKey
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function startJoinTo($query, $tableAlias, $foreignTable = null, $foreignKey = null)
    {
        $mgqService = new MgqService;

        $query                  = $mgqService->startJoinTo($query, $tableAlias, $foreignTable, $foreignKey);
        $this->modelGridQueries = array_merge($this->modelGridQueries, $mgqService->getModelGridQueries());

        return $query;
    }

    /**
     * Return the columns to be added in select, base from the starting tableAlias.
     *
     * @param  string $tableAlias
     * @return array
     */
    public function columnsToAddSelect($tableAlias = null)
    {
        $columns = [];

        $mgqs = is_null($tableAlias) ? $this->getModelGridQueries() : array_start_from($this->getModelGridQueries(), function ($mgq) use ($tableAlias) {
            return $mgq->tableAlias() === $tableAlias;
        });

        foreach ($mgqs as $mgq) {
            $columns = array_merge($columns, $mgq->columns());
        }

        return $columns;
    }

    /**
     * Return all modelGridQueries joined to the query.
     *
     * @return array
     */
    public function getModelGridQueries()
    {
        return $this->modelGridQueries;
    }

    /**
     * Get the summary column sql query.
     *
     * @param  string $columnName
     * @return string|mixed
     */
    public function getSummaryColumn($columnName)
    {
        if (!method_exists($this, 'summarizableColumns')) {
            throw new \Exception("Invalid Method Call: Calling getSummaryColumn() method on non-summarizable grid query is invalid.");
        }

        $columns = $this->summarizableColumns();

        if (array_key_exists($columnName, $columns)) {
            return $columns[$columnName];
        }

        foreach ($columns as $column) {
            if ($column === $columnName || ends_with($column, ".{$columnName}")) {
                return $column;
            }
        }
    }

    /**
     * Optional. This is used as the default or the first groupBy key to be used when calling or presenting summary report.
     * 
     * @return string|array|mixed
     * @example
     * return [
     *     'age' => $this->age,   // a groupByKey with a sql query 
     *     member_id              // a natural column key
     * ];
     */
    public function defaultGroupByKey()
    {
        return [];
    }
}
