<?php

namespace SedpMis\BaseReport\GridQuery;

interface ReportGridQueryInterface extends GridQueryInterface
{
    /**
     * The actual column for date reference to filter.
     *
     * @return string|mixed
     */
    public function dateReferenceColumn();

    /**
     * Set the date filters for the report grid.
     *
     * @param string $startDate
     * @param string $endDate
     */
    public function setDatesFilter($startDate, $endDate);
}
