<?php

namespace SedpMis\BaseReport\GridQuery;

class ColumnedReportGridQuery extends BaseReportGridQuery implements ReportGridQueryInterface
{
    protected $columns = [];

    protected $gridQuery;

    public function __construct($gridQuery, array $columns, $modelGridQueries)
    {
        $this->gridQuery        = $gridQuery;
        $this->query            = $gridQuery->query();
        $this->columns          = $columns;
        $this->modelGridQueries = $modelGridQueries;
    }

    public function columns()
    {
        return $this->columns;
    }

    public function dateReferenceColumn()
    {
        return $this->gridQuery->dateReferenceColumn();
    }
}