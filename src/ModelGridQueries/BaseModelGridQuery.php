<?php

namespace SedpMis\BaseReport\ModelGridQueries;

use SedpMis\BaseGridQuery\BaseGridQuery;
use SedpMis\BaseGridQuery\GridQueryInterface;
use Illuminate\Database\DatabaseManager as DB;

abstract class BaseModelGridQuery extends  BaseGridQuery implements GridQueryInterface, ModelGridQueryInterface
{
    protected $name;

    protected $primaryKey = 'id';

    protected $foreignKey;

    protected $table;

    protected $tableAlias;

    protected $model;

    // protected $parentGridQuery;

    /**
     * Setup models and class properties.
     */
    protected function setUpModel($model)
    {
        if (!is_null($model)) {
            $this->primaryKey = $this->primaryKey ?: $model->getKeyName();
            $this->foreignKey = $this->foreignKey ?: $model->getForeignKey();
            $this->table      = $this->table ?: $model->getTable();
            $this->tableAlias = $this->tableAlias ?: $this->table;
            $this->name       = $this->name ?: str_plural($this->table);
            $this->model      = $model;
        }
    }

    /**
     * Disable the initQuery.
     *
     * @return void
     */
    public function initQuery()
    {
        return;
    }

    /**
     * Apply join query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $foreignTable
     * @param  string  $foreignKey
     * @param  bool $joinToParent
     * @return void
     */
    public function join($query, $foreignTable = null, $foreignKey = null)
    {
        $foreignTable = $foreignTable ?: $query->getModel()->getTable();

        $foreignKey = $foreignKey ?: $this->foreignKey;

        $query->leftJoin($this->tableToJoin(), "{$this->tableAlias}.{$this->primaryKey}", '=', "{$foreignTable}.{$foreignKey}");
    }

    /**
     * Return the table to join.
     *
     * @return string|mixed
     */
    public function tableToJoin()
    {
        $table = $this->table;

        if ($this->table !== $this->tableAlias) {
            $table = DB::raw("{$this->table} as {$this->tableAlias}");
        }

        return $table;
    }

    public function whereInFilterKey()
    {
        return "{$this->name}_ids";
    }

    public function applyWhereInFilter($query, $values)
    {
        return $query->whereIn("{$this->tableAlias}.{$this->primaryKey}", $values);
    }

    public function name()
    {
        return $this->name;
    }

    public function table()
    {
        return $this->table;
    }

    public function tableAlias()
    {
        return $this->tableAlias;
    }

    public function primaryKey()
    {
        return $this->primaryKey;
    }

    public function foreignKey()
    {
        return $this->foreignKey;
    }

    public function model()
    {
        return $this->model;
    }
}
