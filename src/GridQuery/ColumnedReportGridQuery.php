<?php

namespace SedpMis\BaseReport\GridQuery;

class ColumnedReportGridQuery extends BaseReportGridQuery
{
    protected $columns = [];

    public function __construct($query, array $columns, $modelGridQueries)
    {
        $this->query            = $query;
        $this->columns          = $columns;
        $this->modelGridQueries = $modelGridQueries;
    }

    public function columns()
    {
        return $this->columns;
    }
}