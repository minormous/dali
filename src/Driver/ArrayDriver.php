<?php

namespace Minormous\Dali\Driver;

use Generator;
use Doctrine\DBAL\Query\QueryBuilder;
use Minormous\Dali\Config\DriverConfig;
use Minormous\Dali\Driver\Interfaces\QueryResultInterface;
use Minormous\Dali\Exceptions\InvalidDriverException;
use function is_array;
use function count;

/**
 * An array driver designed for mocking database access in tests.
 */
final class ArrayDriver extends AbstractDriver
{
    /** @psalm-var array<string,array<int,non-empty-array<string,mixed>>> $database */
    private array $database = [
        '__example__' => [
            ['id' => 1, 'name' => 'row1'],
        ],
    ];

    protected function setup(DriverConfig $driverConfig): void
    {
        // Unset
    }

    public function find(string $table, array $where, array $options = []): QueryResultInterface
    {
        return new QueryResult(
            count(iterator_to_array($this->iterator($table, $where, []))),
            $this->iterator($table, $where, $options),
        );
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

    public function insert(string $table, array $data): string|int
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

        /** @var string|int */
        $id = $data['id'];

        return $id;
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

    /**
     * @return never
     */
    public function findFromQueryBuilder(QueryBuilder $queryBuilder): QueryResultInterface
    {
        throw InvalidDriverException::notImplemented(__METHOD__, 'Query Builder doesn\'t work with array driver.');
    }

    /**
     * @return never
     */
    public function deleteFromQueryBuilder(QueryBuilder $queryBuilder): int
    {
        throw InvalidDriverException::notImplemented(__METHOD__, 'Query Builder doesn\'t work with array driver.');
    }

    /** @psalm-suppress MixedInferredReturnType */
    public function convertValueForDriver(string $type, mixed $value): mixed
    {
        /** @psalm-suppress MixedReturnStatement */
        return $value;
    }

    public function getIterator(?string $table = null)
    {
        $table = $table ?? array_keys($this->database)[0];

        return $this->iterator($table, [], []);
    }

    /**
     * @param array<string,array<int,array<string,mixed>>> $database An array of tables, containing rows.
     * @psalm-param array<string,array<int,non-empty-array<string,mixed>>> $database
     */
    public function setData(array $database): self
    {
        $this->database = $database;

        return $this;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @psalm-param array<int,non-empty-array<string,mixed>> $rows
     */
    public function addTable(string $table, array $rows = []): self
    {
        $this->database[$table] = $rows;

        return $this;
    }

    /**
     * @param array<string,array{0:string,1?:mixed}|mixed> $where
     * @param array{offset?:int,sort?:array{0:string,1?:string},limit?:int} $options
     * @psalm-param array{offset?:positive-int,sort?:array{0:string,1?:'ASC'|'DESC'},limit?:positive-int} $options
     * @return Generator<int,non-empty-array<string,mixed>,mixed,void>
     */
    private function iterator(string $table, array $where, array $options): Generator
    {
        $yieldCount = 0;
        /** @var array<string,array<string,mixed>> $data */
        $data = $this->database[$table];
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if (array_key_exists('offset', $options) && $options['offset']) {
            /** @psalm-suppress RedundantCastGivenDocblockType */
            $data = array_slice($data, (int) $options['offset']);
        }
        if (array_key_exists('sort', $options)) {
            $sort = $options['sort'];
            usort($data, function (array $a, array $b) use ($sort) {
                $ascending = strtoupper($sort[1] ?? 'ASC') === 'ASC';
                if ($ascending) {
                    return $a[$sort[0]] <=> $b[$sort[0]];
                } else {
                    return $b[$sort[0]] <=> $a[$sort[0]];
                }
            });
        }
        /** @psalm-var non-empty-array<string,mixed> $row */
        foreach ($data as $row) {
            /** @psalm-suppress RedundantConditionGivenDocblockType */
            if (array_key_exists('limit', $options) && $options['limit'] && $yieldCount >= $options['limit']) {
                break;
            }
            if (empty($where) || $this->checkIfFound($row, $where)) {
                $yieldCount++;
                yield $row;
            }
        }
    }

    /**
     * @param array<string,mixed> $row
     * @param array<string,array{0:string,1?:mixed}|mixed> $where
     */
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

    /**
     * @psalm-param array{0:string,1?:mixed}|mixed $value
     */
    private function compare(mixed $rowValue, mixed $value): bool
    {
        $operator = '=';
        if (is_array($value)) {
            $operator = $value[0];
            $value = $value[1] ?? null;
        }

        return match ($operator) {
            default => throw InvalidDriverException::notImplemented(__METHOD__, "Operator [{$operator}] not valid!"),
            '=', '==', '===' => $rowValue === $value,
            '<>', '!=' => $rowValue !== $value,
            '>' => $rowValue > $value,
            '<' => $rowValue < $value,
            '>=' => $rowValue >= $value,
            '<=' => $rowValue <= $value,
            'LIKE' => (bool) preg_match('/' . str_replace('%', '.*', (string) $value) . '/i', (string) $rowValue),
            'NOT LIKE' => ! $this->compare($rowValue, ['LIKE', $value]),
            'SIMILAR', '~=' => (bool) preg_match('/' . (string) $value . '/i', (string) $rowValue),
            'NOT NULL' => isset($rowValue),
        };
    }
}
