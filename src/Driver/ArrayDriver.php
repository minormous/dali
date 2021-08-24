<?php

namespace Minormous\Dali\Driver;

use Generator;
use Doctrine\DBAL\Query\QueryBuilder;
use Minormous\Dali\Config\DriverConfig;
use Minormous\Dali\Driver\Interfaces\QueryResultInterface;
use Minormous\Dali\Exceptions\InvalidDriverException;

/**
 * An array driver designed for mocking database access in tests.
 */
final class ArrayDriver extends AbstractDriver
{
    private array $database = [
        '__example__' => [
            ['id' => 1, 'name' => 'row1'],
        ],
    ];

    protected function setup(DriverConfig $driverConfig)
    {
        // Unset
    }

    public function find(string $table, array $where, array $options = []): QueryResultInterface
    {
        return new QueryResult(count($this->database[$table]), $this->iterator($table, $where, $options));
    }

    public function findOne(string $table, array $where): ?array
    {
        foreach ($this->database[$table] as $row) {
            if ($this->checkIfFound($row, $where)) {
                return $row;
            }
        }

        return null;
    }

    public function insert(string $table, array $data): int
    {
        foreach ($this->database[$table] as $row) {
            if ($row['id'] === ($data['id'] ?? '__UNSET__')) {
                $data['id'] = 0;
                break;
            }
        }

        if (($data['id'] ?? 0) === 0) {
            $data['id'] = count($this->database[$table]) + 1;
        }

        $this->database[$table][] = $data;

        return $data['id'];
    }

    public function update(string $table, array $where, array $data): int
    {
        $count = 0;
        foreach ($this->database[$table] as &$row) {
            if ($this->checkIfFound($row, $where)) {
                if (isset($data['id'])) {
                    unset($data['id']);
                }
                $row = array_merge($row, $data);
                $count++;
            }
        }

        return $count;
    }

    public function delete(string $table, array $where): int
    {
        $count = 0;
        foreach ($this->database[$table] as $key => $row) {
            if ($this->checkIfFound($row, $where)) {
                unset($this->database[$table][$key]);
                $count++;
            }
        }

        return $count;
    }

    public function getConnection()
    {
        throw InvalidDriverException::notImplemented(__METHOD__, 'Connection doesn\'t exist with array driver.');
    }

    public function findFromQueryBuilder(QueryBuilder $queryBuilder): QueryResultInterface
    {
        throw InvalidDriverException::notImplemented(__METHOD__, 'Query Builder doesn\'t work with array driver.');
    }

    public function deleteFromQueryBuilder(QueryBuilder $queryBuilder)
    {
        throw InvalidDriverException::notImplemented(__METHOD__, 'Query Builder doesn\'t work with array driver.');
    }

    public function convertValueForDriver(string $type, mixed $value): mixed
    {
        return $value;
    }

    public function getIterator()
    {
        $table = array_keys($this->database)[0];

        return $this->iterator($table, [], []);
    }

    /**
     * @param array<string,array<int,array<string,mixed>>> $database An array of tables, containing rows.
     */
    public function setData(array $database): self
    {
        $this->database = $database;

        return $this;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    public function addTable(string $table, array $rows = []): self
    {
        $this->database[$table] = $rows;

        return $this;
    }

    private function iterator(string $table, array $where, array $options): Generator
    {
        $yieldCount = 0;
        $data = $this->database[$table];
        if (array_key_exists('offset', $options) && $options['offset']) {
            $data = array_slice($data, (int) $options['offset']);
        }
        if (array_key_exists('sort', $options)) {
            $sort = $options['sort'];
            usort($data, function ($a, $b) use ($sort) {
                $ascending = (strtoupper($sort[1]) ?? 'ASC') === 'ASC';
                if ($ascending) {
                    return $a[$sort[0]] <=> $b[$sort[0]];
                } else {
                    return $b[$sort[0]] <=> $a[$sort[0]];
                }
            });
        }
        foreach ($data as $row) {
            if (array_key_exists('limit', $options) && $options['limit'] && $yieldCount >= $options['limit']) {
                break;
            }
            if (count($where) === 0 || $this->checkIfFound($row, $where)) {
                $yieldCount++;
                yield $row;
            }
        }
    }

    private function checkIfFound(array $row, array $where): bool
    {
        $foundCount = 0;
        foreach ($where as $key => $value) {
            if ($this->compare($row[$key], $value)) {
                $foundCount++;
            }
        }

        return $foundCount === count($where);
    }

    private function compare($rowValue, $value): bool
    {
        $operator = '=';
        if (is_array($value)) {
            if (count($value) > 1) {
                [$operator, $value] = $value;
            } else {
                [$operator] = $value;
            }
        }

        return match ($operator) {
            '=', '==', '===' => $rowValue === $value,
            '<>', '!=' => $rowValue !== $value,
            '>' => $rowValue > $value,
            '<' => $rowValue < $value,
            '>=' => $rowValue >= $value,
            '<=' => $rowValue <= $value,
            'LIKE' => preg_match('/' . str_replace('%', '.*', $value) . '/i', $rowValue),
            'NOT LIKE' => ! $this->compare($rowValue, ['LIKE', $value]),
            'SIMILAR', '~=' => preg_match("/{$value}/i", $rowValue),
            'NOT NULL' => isset($rowValue),
        };
    }
}
