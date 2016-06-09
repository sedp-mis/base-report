<?php

namespace SedpMis\BaseReport\ModelGridQueries;

class MgqService
{
    /**
     * Mgqs factory.
     * 
     * @var \SedpMis\BaseReport\ModelGridQueries\MgqsFactoryInterface
     */
    public static $mgqsFactory;

    protected $mgqs = [];

    /**
     * Initialize mgqs and return the created mgqs.
     *
     * @param  string $tableAlias
     * @return array
     */
    public function initMgqs($tableAlias)
    {
        if (!static::$mgqsFactory instanceof MgqsFactoryInterface) {
            throw new \Exception('Error: $mgqsFactory should implements \SedpMis\BaseReport\ModelGridQueries\MgqsFactoryInterface.');
        }

        // Instantiate all mgqs
        $mgqs = static::$mgqsFactory->makeMgqs();

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

            $parentMgq = $this->getMgq($index + 1);

            // Get the connection foreign key for the mgq and its parent mgq,
            // like centers has classification_id foreign key of classifications table
            $foreignKey = $parentMgq && method_exists($mgq, 'makeForeignKey') ?
                $mgq->makeForeignKey($parentMgq->tableAlias()) :
                null;

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
