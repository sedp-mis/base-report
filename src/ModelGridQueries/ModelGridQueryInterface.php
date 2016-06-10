<?php

namespace SedpMis\BaseReport\ModelGridQueries;

interface ModelGridQueryInterface
{
    public function name();
    public function table();
    public function tableAlias();
    public function tableToJoin();
    public function primaryKey();
    public function foreignKey();
    public function model();
    public function join($query, $foreignTable, $foreignKey = null);
    public function whereInFilterKey();
    public function applyWhereInFilter($query, $values);
}
