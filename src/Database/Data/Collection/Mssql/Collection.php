<?php
/**
 * This class provides MSSQL specific implementation for a database collection.
 */
declare (strict_types=1);

namespace Maleficarum\Database\Data\Collection\Mssql;

abstract class Collection extends \Maleficarum\Database\Data\Collection\AbstractCollection {
    /* ------------------------------------ Class Methods START ---------------------------------------- */

    /**
     * @see \Maleficarum\Database\Data\Collection\AbstractCollection::populate_prependSection()
     */
    protected function populate_prependSection(\stdClass $dto): string {
        return '';
    }

    /**
     * @see \Maleficarum\Database\Data\Collection\AbstractCollection::populate_basicQuery()
     */
    protected function populate_basicQuery(string $query, \stdClass $dto): string {
        // add basic select clause
        $query .= 'SELECT ';

        // add distinct values if requested
        if (array_key_exists('__distinct', $dto->data)) {
            // validate input
            is_array($dto->data['__distinct']) && count($dto->data['__distinct']) or $this->respondToInvalidArgument('Incorrect __distinct data. \%s::populate()');

            // proceed
            $query .= 'DISTINCT "';
            $query .= implode('", "', $dto->data['__distinct']);
            $query .= '" ';
        }

        // make sure the count and sum options are not used together
        if (array_key_exists('__count', $dto->data) && array_key_exists('__sum', $dto->data)) {
            $this->respondToInvalidArgument('__count and __sum are mutually exclusive. \%s::populate()');
        }

        // add grouping/counting clauses
        if (array_key_exists('__count', $dto->data)) {
            is_array($dto->data['__count']) && count($dto->data['__count']) or $this->respondToInvalidArgument('Incorrect __count data. \%s::populate()');

            // add count
            if (!array_key_exists('__distinct', $dto->data['__count'])) {
                $query .= 'COUNT("' . array_shift($dto->data['__count']) . '") AS "__count" ';
            } else {
                unset($dto->data['__count']['__distinct']);
                $query .= 'COUNT(DISTINCT "' . array_shift($dto->data['__count']) . '") AS "__count" ';
            }

            // add group columns
            count($dto->data['__count']) and $query .= ', "' . implode('", "', $dto->data['__count']) . '" ';
        } elseif (array_key_exists('__sum', $dto->data)) {
            is_array($dto->data['__sum']) && count($dto->data['__sum']) or $this->respondToInvalidArgument('Incorrect __sum data. \%s::populate()');

            // add sum
            $query .= 'SUM("' . array_shift($dto->data['__sum']) . '") AS "__sum" ';

            // add group columns
            count($dto->data['__sum']) and $query .= ', "' . implode('", "', $dto->data['__sum']) . '" ';
        } elseif (array_key_exists('__distinct', $dto->data)) {
            unset($dto->data['__distinct']);
        } else {
            $query .= '* ';
        }

        // add basic FROM clause
        $query .= 'FROM "' . $this->getTable() . '" WHERE ';

        return $query;
    }

    /**
     * @see \Maleficarum\Database\Data\Collection\AbstractCollection::populate_attachFilter()
     */
    protected function populate_attachFilter(string $query, \stdClass $dto): string {
        foreach ($dto->data as $key => $data) {
            // skip any special tokens
            if (in_array($key, self::$specialTokens)) {
                continue;
            }

            // validate token data
            is_array($data) && count($data) or $this->respondToInvalidArgument('Incorrect filter param provided [' . $key . '], non-empty array expected. \%s::populate()');

            // parse key for filters
            $structure = $this->parseFilter($key);

            // create a list of statement tokens and matching values
            $temp = [];
            $hax_values = [false, 0, "0"]; // PHP sucks and empty(any of these values) returns true...
            foreach ($data as $elK => $elV) {
                (empty($elV) && !in_array($elV, $hax_values)) and $this->respondToInvalidArgument('Incorrect filter value [' . $key . '] - non empty value expected. \%s::populate()');
                $dto->params[$structure['prefix'] . $elK] = $elV;
                $temp[] = $structure['value_prefix'] . $structure['prefix'] . $elK . $structure['value_suffix'];
            }

            // attach filter to the query
            $query .= '' . $structure['column'] . ' ' . $structure['operator'] . ' (' . implode(', ', $temp) . ') AND ';
        }

        return $query;
    }

    /**
     * @see \Maleficarum\Database\Data\Collection\AbstractCollection::populate_grouping()
     */
    protected function populate_grouping(string $query, \stdClass $dto): string {
        if (array_key_exists('__count', $dto->data) && is_array($dto->data['__count']) && count($dto->data['__count'])) {
            $query .= 'GROUP BY "' . implode('", "', $dto->data['__count']) . '" ';
        }

        if (array_key_exists('__sum', $dto->data) && is_array($dto->data['__sum']) && count($dto->data['__sum'])) {
            $query .= 'GROUP BY "' . implode('", "', $dto->data['__sum']) . '" ';
        }

        return $query;
    }

    /**
     * @see \Maleficarum\Database\Data\Collection\AbstractCollection::populate_lock()
     */
    protected function populate_lock(string $query, \stdClass $dto): string {
        throw new \Maleficarum\Database\Exception\Exception('Not implemented yet.');
    }

    /**
     * @see \Maleficarum\Database\Data\Collection\AbstractCollection::populate_sorting()
     */
    protected function populate_sorting(string $query, \stdClass $dto): string {
        $query .= 'ORDER BY ';
        $fields = [];
        foreach ($dto->data['__sorting'] as $val) {
            $fields[] = "\"$val[0]\" $val[1]";
        }
        $query .= implode(', ', $fields) . ' ';

        return $query;
    }

    /**
     * @see \Maleficarum\Database\Data\Collection\AbstractCollection::populate_subset()
     */
    protected function populate_subset(string $query, \stdClass $dto): string {
        throw new \Maleficarum\Database\Exception\Exception('Not implemented yet.');
    }

    /**
     * @see \Maleficarum\Database\Data\Collection\AbstractCollection::insertAll()
     */
    public function insertAll(): \Maleficarum\Database\Data\Collection\AbstractCollection {
        // test database connection
        $this->populate_testDb();

        // check if there are any entries to insert
        if (!count($this->data)) {
            return $this;
        }

        // setup data set
        $data = $this->prepareElements('INSERT');

        $columnCount = count(array_keys($data[0]));
        $rowCount = count($data);
        $paramsRequired = $columnCount * $rowCount;
        if ($this->getShard()->hasNoStmtParamCountLimit() || $paramsRequired <= $this->getShard()->getStmtParamCountLimit()) {
            // it's enough to fetch all in single batch
            // setup containers
            $sets = [];
            $params = [];

            // generate basic query
            $sql = 'INSERT INTO "' . $this->getTable() . '" ("' . implode('", "', array_keys($data[0])) . '") OUTPUT inserted.* VALUES ';

            // attach params to the query
            foreach ($data as $key => $val) {
                $result = [];
                array_walk($val, function ($local_value, $local_key) use (&$result, $key) {
                    $result[':' . $local_key . '_token_' . $key] = $local_value;
                });

                // append sets
                $sets[] = "(" . implode(', ', array_keys($result)) . ")";

                // append bind params
                $params = array_merge($params, $result);
            }
            $sql .= implode(', ', $sets);

            $st = $this->prepareStatement($sql, $params);

            // execute the query
            $st->execute();

            // replace current data by returned data if returning was requested
            $this->setData($st->fetchAll(\PDO::FETCH_ASSOC))->format();
        } else {
            $batchSize = (int)floor($this->getShard()->getStmtParamCountLimit() / $columnCount);
            $batches = array_chunk($data, $batchSize);
            $allBatchesData = [];
            foreach ($batches as $batch) {
                $this->setData($batch);
                $this->insertAll();
                $allBatchesData = array_merge($allBatchesData, $this->data);
            }

            $this->setData($allBatchesData);
        }

        $this->format();

        return $this;
    }

    /**
     * @see \Maleficarum\Database\Data\Collection\AbstractCollection::deleteAll()
     */
    public function deleteAll(): \Maleficarum\Database\Data\Collection\AbstractCollection {
        // test database connection
        $this->populate_testDb();

        // check if there are any entries to delete
        if (!count($this->data)) {
            return $this;
        }

        // setup data set
        $data = $this->prepareElements('DELETE');

        $columnCount = count(array_keys($data[0]));
        $rowCount = count($data);
        $paramsRequired = $columnCount * $rowCount;
        if ($this->getShard()->hasNoStmtParamCountLimit() || $paramsRequired <= $this->getShard()->getStmtParamCountLimit()) {
            // setup containers
            $sets = [];
            $params = [];

            // generate basic query
            $sql = 'DELETE FROM "' . $this->getTable() . '" WHERE ';
            // attach params to the query
            foreach ($data as $key => $val) {
                $values = [];
                $names = [];
                array_walk($val, function ($local_value, $local_key) use (&$values, &$names, $key) {
                    $values[':' . $local_key . '_token_' . $key] = $local_value;
                    $names[] = '"' . $local_key . '" = :' . $local_key . '_token_' . $key;
                });

                // append sets
                $sets[] = '(' . implode(' AND ', $names) . ')';

                // append bind params
                $params = array_merge($params, $values);
            }
            $sql .= implode(' OR ', $sets);

            $st = $this->prepareStatement($sql, $params);

            // execute the query
            $st->execute();
        } else {
            $batchSize = (int)floor($this->getShard()->getStmtParamCountLimit() / $columnCount);
            $batches = array_chunk($data, $batchSize);
            foreach ($batches as $batch) {
                $this->setData($batch);
                $this->deleteAll();
            }
        }

        return $this;
    }

    /**
     * Parse provided key param into a set of filtering data.
     *
     * @param string $key
     *
     * @return array
     */
    private function parseFilter(string $key): array {
        $result = ['column' => '"' . $key . '"', 'operator' => 'IN', 'prefix' => ':' . $key . '_', 'value_prefix' => '', 'value_suffix' => ''];

        // attempt to recover filters
        $data = explode('/', $key);

        // filters detected
        if (count($data) > 1) {
            // fetch filters
            $filter = array_shift($data);

            // establish a new basic result structure
            $result = ['column' => '"' . $data[0] . '"', 'operator' => $result['operator'], 'prefix' => ':' . $data[0] . '_', 'value_prefix' => $result['value_prefix'], 'value_suffix' => $result['value_suffix']];

            // apply filters
            for ($index = 0; $index < mb_strlen($filter); $index++) {
                // exclude filter
                $filter[$index] === '~' and $result = [
                    'column' => $result['column'],
                    'operator' => 'NOT IN',
                    'prefix' => $result['prefix'] . 'exclude_',
                    'value_prefix' => $result['value_prefix'],
                    'value_suffix' => $result['value_suffix'],
                ];

                // case-insensitive filter
                $filter[$index] === 'i' and $result = [
                    'column' => 'LOWER(' . $result['column'] . ')',
                    'operator' => $result['operator'],
                    'prefix' => $result['prefix'] . 'case_insensitive_',
                    'value_prefix' => 'LOWER(' . $result['value_prefix'],
                    'value_suffix' => $result['value_suffix'] . ')',
                ];
            }
        }

        return $result;
    }

    /* ------------------------------------ Class Methods END ------------------------------------------ */
}
