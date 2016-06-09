<?php

namespace SedpMis\BaseReport\GridQuery;

interface SummarizableInterface
{
    /**
     * Return the summarizable columns of the query.
     *
     * @return array
     * @example
     * return [
     *     'principal'    => 'SUM(loans.principal)',
     *     'clip'         => 'SUM(loans.clip)',
     *     'clip_rebate'  => 'SUM(loan_due_payments.clip_rebate)',
     *     'source'       => 'IF(loan_due_payments.loan_id, "COLLECTION", "DEDUCTION")'
     * ];
     */
    public function summaryColumns();
}
