<?php

namespace Database;

use Countable;
use InvalidArgumentException;

class QueryBuilder implements Countable
{
	/**
	 * name of the table
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * columns to be retrived
	 *
	 * @var array
	 */
	protected $columns = ['*'];

	/**
	 * limit on query result
	 * @var integer
	 */
	protected $limit;

	/**
	 * column and the direction
	 * we want to order by
	 *
	 * @var array
	 */
	protected $orderBy = [];

	/**
	 * where conditions
	 *
	 * @var array
	 */
	protected $wheres = [];

	protected $countsRow = false;

	/**
	 * sets table name to perform query
	 *
	 * @param  string $tableName
	 * @return \Database\QueryBuilder
	 */
	public function table($tableName)
	{
		$this->table = $tableName;

		return $this;
	}

	/**
	 * builds up select statement
	 *
	 * @return string
	 */
	public function get()
	{
		$limit = '';

		if ($this->limit) {
			$limit = " LIMIT {$this->limit}";
		}

		$orderBy = '';

		if ($this->orderBy) {
			$orderBy = ' ORDER BY';

			foreach ($this->orderBy as $i => $orderByClause) {
				extract($orderByClause);

				if ($i > 0) {
					$orderBy .= ',';
				}

				$direction = $direction ? " {$direction}" : '';

				$orderBy .= " `{$column}`{$direction}";
			}
		}

		$glue = '`, `';

		if ($this->countsRow) {
			$glue = ', ';
		}

		$columns = implode($glue, $this->columns);

		if (! $this->countsRow) {
			$columns = "`{$columns}`";
		}

		$where = '';

		if ($this->wheres) {
			$where = ' WHERE';

			foreach ($this->wheres as $i => $whereCond) {
				extract($whereCond);

				if ($i) {
					$where .= " {$conjuction}";
				}

				if (is_string($value)) {
					$value = "'{$value}'";
				}

				$where .= " `{$field}` {$operator} {$value}";
			}
		}

		return "SELECT {$columns} FROM `{$this->table}`{$where}{$orderBy}{$limit}";
	}

	/**
	 * count number of rows based on
	 * rest of the query
	 *
	 * @return string
	 */
	public function count()
	{
		$this->countsRow = true;

		return $this->select('count(*)')->get();
	}

	/**
	 * helper to limit 1
	 *
	 * @return string
	 */
	public function first()
	{
		return $this->limit(1)->get();
	}

	/**
	 * selects supplied columns only
	 *
	 * @param  array $columns
	 * @return \Database\QueryBuilder
	 */
	public function select(...$columns)
	{
		$this->columns = $columns;

		return $this;
	}

	/**
	 * limit on query
	 *
	 * @param  integer $limit
	 * @return \Database\QueryBuilder
	 */
	public function limit($limit)
	{
		if (! is_numeric($limit) || (int) $limit < 0) {
			throw new InvalidArgumentException("a positive numeric value was expected");
		}

		$this->limit = $limit;

		return $this;
	}

	/**
	 * order by clause
	 * @param  string $column
	 * @return \Database\QueryBuilder
	 */
	public function orderBy($column, $direction = null)
	{
		$direction = strtoupper($direction);

		if (func_num_args() === 2 && ! in_array($direction, ['ASC', 'DESC'])) {
			throw new InvalidArgumentException("Order by caluse supports only ASC & DESC");
		}

		$this->orderBy[] = compact('column', 'direction');

		return $this;
	}

	/**
	 * adds up where condition
	 *
	 * @param string $field
	 * @param string $operator
	 * @param mixed $value
	 */
	public function where(...$params)
	{
		return $this->addWhere('AND', ...$params);
	}

	/**
	 * adds up and where condition
	 *
	 * @param string $field
	 * @param string $operator
	 * @param mixed $value
	 */
	public function orWhere(...$params)
	{
		return $this->addWhere('OR', ...$params);
	}

	/**
	 * adds up or where condition
	 *
	 * @param string $conjuction AND|OR
	 * @param string $field
	 * @param string $operator
	 * @param mixed $value
	 */
	protected function addWhere($conjuction, $field, $operator = null, $value = null)
	{
		if (func_num_args() === 3) {
			list($operator, $value) = ['=', $operator];
		}

		$this->wheres[] = compact('field', 'operator', 'value', 'conjuction');

		return $this;
	}
}
