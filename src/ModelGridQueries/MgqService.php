<?php

namespace SedpMis\BaseReport\ModelGridQueries;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;

class MgqService
{
    /**
     * Mgqs factory. Should have a method name mqgs() that will return instance of mgqs.
     * 
     * @var mixed
     */
    public static $mgqsFactory;

    /**
     * The created mgqs.
     *
     * @var array
     */
    protected $mgqs = [];

    /**
     * Initialize mgqs and return the created mgqs.
     *
     * @param  string $tableAlias
     * @return array
     */
    public function initMgqs($tableAlias)
    {
        if (is_null(static::$mgqsFactory)) {
            // Resolve $mgqsFactory
            $mgqsFactoryClass = Config::get('sedp-mis_base-report.mgqs_factory');
            if (!$mgqsFactoryClass) {
                throw new \Exception('Config `sedp-mis_base-report.mgqs_factory` is not defined');
            }
            static::$mgqsFactory = App::make($mgqsFactoryClass);
        }
        
        // Instantiate all mgqs
        $mgqs = static::$mgqsFactory->mgqs();

        return $this->mgqs = array_start_from($mgqs, function ($mgq) use ($tableAlias) {
            return $mgq->tableAlias() === $tableAlias;
        });
    }

    /**
     * Start joining a query to other model grid queries and return the joined query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $tableAlias
     * @param  string $foreignTable
     * @param  string $foreignKey
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function startJoinTo($query, $tableAlias, $foreignTable = null, $foreignKey = null)
    {
        foreach ($this->initMgqs($tableAlias) as $index => $mgq) {
            $mgq->join($query, $foreignTable, $foreignKey);

            // Get the next parent table
            $parentMgq = $this->getMgq($index + 1);

            // Get the connecting foreign key between the mgq and its parent mgq
            $foreignKey = $parentMgq && method_exists($mgq, 'makeForeignKey') ?
                $mgq->makeForeignKey($parentMgq->tableAlias()) :
                null;

            // Assign the child as next foreign table to connect to the parent table
            $foreignTable = $mgq->tableAlias();
        }

        return $query;
    }

    /**
     * Start joining from a table up to a certain table only.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $fromTable
     * @param  string $uptoTable
     * @param  string $foreignTable
     * @param  string $foreignKey
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function startJoinFromUpto($query, $fromTable, $uptoTable, $foreignTable = null, $foreignKey = null)
    {
        foreach ($this->initMgqs($fromTable) as $index => $mgq) {

            $mgq->join($query, $foreignTable, $foreignKey);

            if ($uptoTable === $mgq->tableAlias()) {
                break;
            }

            // Get the next parent table
            $parentMgq = $this->getMgq($index + 1);

            // Get the connecting foreign key between the mgq and its parent mgq
            $foreignKey = $parentMgq && method_exists($mgq, 'makeForeignKey') ?
                $mgq->makeForeignKey($parentMgq->tableAlias()) :
                null;

            // Assign the child as next foreign table to connect to the parent table
            $foreignTable = $mgq->tableAlias();
        }

        return $query;
    }

    public function getModelGridQueries()
    {
        return $this->mgqs;
    }

    private function getMgq($index)
    {
        if (array_key_exists($index, $this->mgqs)) {
            return $this->mgqs[$index];
        }
    }
}
