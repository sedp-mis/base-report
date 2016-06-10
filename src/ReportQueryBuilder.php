<?php

namespace SedpMis\Base\Report\Query;

/**
 * A builder-pattern class which builds the report query.
 */
class ReportQueryBuilder
{
    /**
     * The report gridQuery.
     *
     * @var \SedpMis\BaseReport\GridQuery\ReportGridQueryInterface
     */
    protected $gridQuery;

    /**
     * The constructed query from the gridQuery.
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;

    /**
     * If reportData is detailed.
     *
     * @var bool
     */
    protected $isDetailed = true;

    /**
     * Constructor.
     *
     * @param \SedpMis\BaseReport\GridQuery\ReportGridQueryInterface $gridQuery
     * @param string $startDate
     * @param string $endDate
     * @param string|null $columnPresentation
     */
    public function __construct($gridQuery, $startDate = null, $endDate = null, $columnPresentation = null)
    {
        $this->gridQuery = $gridQuery;

        $this->gridQuery->setDatesFilter($startDate = sql_date($startDate), $endDate = sql_date($endDate));

        if ($columnPresentation) {
            $this->gridQuery = $this->gridQuery->columnedReportGridQuery($columnPresentation);
        }

        $this->query = $this->gridQuery->makeQuery();

        $this->filterDates($startDate, $endDate);
    }

    /**
     * Filter dates in query.
     *
     * @param  string $startDate
     * @param  string $endDate
     * @return $this
     */
    protected function filterDates($startDate = null, $endDate = null)
    {
        if (is_null($startDate) && !is_null($endDate)) {
            $this->query->where($this->gridQuery->dateReferenceColumn(), '<=', $endDate);
        }

        if (!is_null($startDate) && !is_null($endDate)) {
            $this->query->whereBetween($this->gridQuery->dateReferenceColumn(), [$startDate, $endDate]);
        }

        return $this;
    }

    /**
     * Apply row data filters.
     *
     * @param  array $filters
     * @return $this
     */
    public function withFilter($filters)
    {
        if (!count($filters)) {
            return $this;
        }

        if (!array_is_assoc($filters)) {
            throw new \Exception('Invalid $filters structure.');
        }

        foreach ($this->gridQuery->getModelGridQueries() as $mgq) {
            $filterKey = $mgq->whereInFilterKey();

            if (!empty($filters[$filterKey])) {
                $filterValues = is_array($filters[$filterKey]) ? $filters[$filterKey] : [$filters[$filterKey]];

                $mgq->applyWhereInFilter($this->query, $filterValues);

                return $this;
            }
        }

        return $this;
    }

    /**
     * Apply custom row data filters.
     *
     * @param  array $filters
     * @return $this
     */
    public function withCustomFilter($filters)
    {
        foreach ($filters as $column => $values) {
            $this->gridQuery->whereIn($column, $values);
        }

        return $this;
    }

    /**
     * Create the summary columns with aggregate calls in select clause and group aggregates query using $groupByKey.
     *
     * @param  string $groupByKey
     * @return $this
     */
    public function summaryGroupBy($groupByKey)
    {
        // ReportData is in summary, so set $isDetailed to false
        $this->isDetailed = false;

        // Get the modelGridQueries starting from an mgq which it has the groupByKey column.
        $mgqs = array_start_from($this->gridQuery->getModelGridQueries(), function ($mgq) use ($groupByKey) {
            return !is_null($mgq->{$groupByKey});
        });

        // Then add the columns of those mgqs in select clause.
        foreach ($mgqs as $mgq) {
            // Do not addSelect() if mgq has a static columnsToAddSelect method, since it will be added later.
            if (!method_exists($mgq, 'columnsToAddSelect')) {
                $this->query->addSelect($mgq->makeSelect());
            }
        }

        /*
         * Start using the defaultGroupByKey() method.
         * Use the defaultGroupByKey if gridQuery has.
         */
        $defaultGroupByKeys = $this->gridQuery->defaultGroupByKey();

        // Convert to array for those single groupByKeys
        if (!is_array($defaultGroupByKeys)) {
            $defaultGroupByKeys = [$defaultGroupByKeys];
        }

        foreach ($defaultGroupByKeys as $defaultGroupByKey => $groupByColumn) {
            // Use the groupByColumn string as the defaultGroupByColumnKey
            $defaultGroupByKey = is_int($defaultGroupByKey) ? $groupByColumn : $defaultGroupByKey;

            $this->query->addSelect(DB::raw("{$groupByColumn} as {$defaultGroupByKey}"));

            // Query group by $defautlGroupByKey
            $this->query->groupBy($defaultGroupByKey);
        }

        // Finally, use the groupByKey to group query for the summary report
        $this->query->groupBy($groupByKey);

        return $this;
    }

    /**
     * Return the query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getQuery()
    {
        // If report is detailed, add all columns of modelGridQueries used by the gridQuery.
        // If report is summary, and method summaryGroupBy() is called, the columns are added
        // starting from the groupByKey which is a column of an modelGridQuery
        if ($this->isDetailed) {
            $this->query->addSelect($this->gridQuery->makeSelect($this->gridQuery->columnsToAddSelect()));
        }

        return $this->query;
    }
}
