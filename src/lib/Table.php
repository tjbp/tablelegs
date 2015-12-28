<?php

/*

This file is part of Tablelegs.

Tablelegs is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Tablelegs is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Tablelegs.  If not, see <http://www.gnu.org/licenses/>.

*/

namespace Tablelegs;

use BadMethodCallException;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

abstract class Table
{
    /**
     * Data source for the rows.
     *
     * @var \Tablelegs\Databases\Databases
     */
    protected $db;

    /**
     * Current request object.
     *
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * Column headers for the table. URL-friendly keys with human values.
     *
     * @var array
     */
    public $columnHeaders = [];

    /**
     * Array of TableColumnHeader objects.
     *
     * @var array
     */
    public $columnHeaderObjects = [];

    /**
     * Array of filter names containing available options and their keys.
     *
     * @var array
     */
    public $filters = [];

    /**
     * Array of TableFilter objects.
     *
     * @var array
     */
    public $filterObjects = [];

    /**
     * Class name for the data source wrapper.
     *
     * @var string
     */
    public $dbClass;

    /**
     * Class name for the paginator presenter.
     *
     * @var string
     */
    public $presenter;

    /**
     * Default key to sort by.
     *
     * @var string
     */
    public $defaultSortKey;

    /**
     * Default sort order.
     *
     * @var string
     */
    public $defaultSortOrder;

    /**
     * Key the current request will be sorted by.
     *
     * @var string
     */
    private $sortKey;

    /**
     * Sort order for the current request.
     *
     * @var string
     */
    private $sortOrder;

    /**
     * Constructor for populating various properties.
     *
     * @param mixed                         $db
     * @param \Illuminate\Http\Request|null $request
     *
     * @return void
     */
    public function __construct($db, $request = null)
    {
        // Set the default sorting settings
        if (is_null($this->defaultSortKey)) {
            $this->defaultSortKey = array_values($this->columnHeaders)[0];
        }

        if (is_null($this->defaultSortOrder)) {
            $this->defaultSortOrder = 'asc';
        }

        $this->constructDatabase($db);

        $this->request = is_null($request) ? Request::capture() : $request;

        $this->sortKey = $this->request->input('sort_key') ?: $this->defaultSortKey;

        $this->sortOrder = $this->request->input('sort_order') ?: $this->defaultSortOrder;

        $this->constructColumnHeaders();

        $this->constructFilters();

        $this->runFilters();

        $this->runSorting();
    }

    /**
     * Instantiate the database.
     *
     * @param mixed $db
     *
     * @return void
     */
    private function constructDatabase($db)
    {
        if (is_null($this->dbClass)) {
            if (is_array($db)) {
                if (array_keys($db) !== range(0, count($db) - 1)) {
                    $this->db = new Databases\AssociativeArray($db);
                } else {
                    $this->db = new Databases\NumericArray($db);
                }
            } elseif ($db instanceof \Illuminate\Support\Collection) {
                $this->db = new Databases\LaravelCollection($db);
            } elseif ($db instanceof \Illuminate\Database\Eloquent\Builder
                || $db instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                $this->db = new Databases\LaravelEloquent($db);
            } elseif (!is_object($db)) {
                throw new InvalidArgumentException('Database must be an object or array');
            } else {
                $class_name = get_class($db);

                throw new DomainException("Please add database class for handling $class_name objects");
            }
        } else {
            $this->db = new $dbClass($db);
        }
    }

    /**
     * Instantiate the column headers.
     *
     * @return void
     */
    private function constructColumnHeaders()
    {
        foreach ($this->columnHeaders as $column_name => $column_key) {
            $this->columnHeaderObjects[] = new TableColumnHeader(
                $this,
                $this->request,
                $column_key,
                $column_name
            );
        }
    }

    /**
     * Instantiate the filters.
     *
     * @return void
     */
    private function constructFilters()
    {
        foreach ($this->filters as $filter_name => $filter_options) {
            $this->filterObjects[] = new TableFilter(
                $this->request,
                $filter_name,
                $filter_options
            );
        }
    }

    /**
     * Run the filters.
     *
     * @return void
     */
    private function runFilters()
    {
        foreach ($this->filterObjects as $filter) {
            $filter_key = $filter->getKey();

            // Execute the filter method if enabled
            if ($this->request->has($filter_key)) {
                $filter_option = ucfirst(preg_replace('/[^a-z0-9]/i', ' ', $this->request->input($filter_key)));

                $filter_key = ucfirst(preg_replace('/[^a-z0-9]/i', ' ', $filter_key));

                if (!in_array($filter_option, $this->filters[$filter_key])) {
                    continue;
                }

                $filter_method = 'filter'.Str::studly($filter_key).Str::studly($filter_option);

                if (method_exists($this, $filter_method)) {
                    $this->$filter_method();
                }
            }
        }
    }

    /**
     * Run the sorting.
     *
     * @return void
     */
    private function runSorting()
    {
        $sort_method = 'sort'.studly_case($this->sortKey);

        // Apply the sorting for the query
        if (method_exists($this, $sort_method)) {
            $this->$sort_method($this->sortOrder);
        } else {
            $this->db->sort($this->sortKey, $this->sortOrder);
        }
    }

    /**
     * Return the sorting key.
     *
     * @return string
     */
    public function getSortKey()
    {
        return $this->sortKey;
    }

    /**
     * Return the sorting order.
     *
     * @return string
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * Return an array of TableFilter objects.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filterObjects;
    }

    /**
     * Return an array of TableColumnHeader objects.
     *
     * @return array
     */
    public function getColumnHeaders()
    {
        return $this->columnHeaderObjects;
    }

    /**
     * Return paginated rows.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $per_page = ($this->request->has('per_page'))
            ? $this->request->get('per_page')
            : $perPage;

        $paginator = $this->db->paginate($per_page, $columns, $pageName, $page);

        $paginator->appends($this->request->all());

        return $paginator;
    }

    /**
     * Return the paginator presenter markup.
     *
     * @return string
     */
    public function paginator()
    {
        $paginator = $this->paginate();

        if (!is_null($this->presenter)) {
            return (new $this->presenter($paginator))->render();
        }

        return $paginator->render();
    }

    /**
     * Return true if the passed argument is the current table's sort order.
     *
     * @return bool
     */
    public function isSortOrder($sortOrder)
    {
        return $sortOrder == $this->sortOrder;
    }

    public function __call($method, $parameters)
    {
        if (is_callable([$this->db, $method])) {
            return call_user_func_array([$this->db, $method], $parameters);
        }

        $class_name = get_class($this);

        throw new BadMethodCallException("Call to undefined method {$class_name}::{$method}()");
    }
}
