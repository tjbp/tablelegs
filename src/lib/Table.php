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

use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class Table
{
    /**
     * Column headers for the table. URL-friendly keys with human values.
     *
     * @var $columnHeaders
     */
    public $columnHeaders = [];

    /**
     * Array of arrays; filter names containing available options.
     *
     * @var $filters
     */
    public $filters = [];

    /**
     * Array of relationships to eager load.
     *
     * @var $eagerLoad
     */
    public $eagerLoad = [];

    /**
     * Class name for the paginator presenter.
     *
     * @var $presenter
     */
    public $presenter;

    /**
     * Default key to sort by.
     *
     * @var $defaultSortKey
     */
    public $defaultSortKey;

    /**
     * Default sort order.
     *
     * @var $defaultSortOrder
     */
    public $defaultSortOrder;

    /**
     * Key the current request will be sorted by.
     *
     * @var $sortKey
     */
    private $sortKey;

    /**
     * Sort order for the current request.
     *
     * @var $sortOrder
     */
    private $sortOrder;

    /**
     * Query builder for the model.
     *
     * @var $builder
     */
    private $builder;

    /**
     * Current request object.
     *
     * @var $request
     */
    private $request;

    /**
     * Cache of the query results.
     *
     * @var $rows
     */
    private $rows;

    /**
     * Current table page.
     *
     * @var $page
     */
    private $page;

    /**
     * Constructor for dependency injection.
     *
     * @param mixed $builder
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct($builder, Request $request)
    {
        // Set the default sorting settings
        if (is_null($this->defaultSortKey)) {
            $this->defaultSortKey = array_keys($this->columnHeaders)[0];
        }

        if (is_null($this->defaultSortOrder)) {
            $this->defaultSortOrder = 'asc';
        }

        $this->builder = $builder;

        $this->request = $request;

        $this->sortKey = $request->input('sort_key') ?: $this->defaultSortKey;

        $this->sortOrder = $request->input('sort_order') ?: $this->defaultSortOrder;

        $sort_method = 'sort' . studly_case($this->sortKey);

        // Apply the eager loading for the query
        foreach ($this->eagerLoad as $relationship) {
            $this->builder->with($relationship);
        }

        // Apply the sorting for the query
        if (method_exists($this, $sort_method)) {
            $this->$sort_method($this->builder, $this->sortOrder);
        } else {
            $this->builder->orderBy($this->sortKey, $this->sortOrder);
        }

        $column_headers = [];

        // Instantiate column header objects
        foreach ($this->columnHeaders as $column_name => $column_key) {
            $column_headers[] = new TableColumnHeader($this, $this->request, $column_key, $column_name);
        }

        $this->columnHeaders = $column_headers;

        $filters = [];

        // Instantiate filter objects
        foreach ($this->filters as $filter_name => $filter_options) {
            $filter = new TableFilter($this->request, $filter_name, $filter_options);

            $filters[] = $filter;

            $filter_key = $filter->getKey();

            // Execute the filter method if enabled
            if ($request->has($filter_key)) {
                $filter_option = ucfirst(preg_replace('/[^a-z0-9]/i', ' ', $request->input($filter_key)));

                $filter_key = ucfirst(preg_replace('/[^a-z0-9]/i', ' ', $filter_key));

                if (!in_array($filter_option, $this->filters[$filter_key])) {
                    continue;
                }

                $filter_method = 'filter' . Str::studly($filter_key) . Str::studly($filter_option);

                if (method_exists($this, $filter_method)) {
                    $this->$filter_method($this->builder);
                }
            }
        }

        $this->filters = $filters;

        $this->page = $this->request->input('page') ?: 1;
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
        return $this->filters;
    }

    /**
     * Return an array of TableColumnHeader objects.
     *
     * @return array
     */
    public function getColumnHeaders()
    {
        return $this->columnHeaders;
    }

    /**
     * Return a collection of query results.
     *
     * @param boolean $paginate
     * @return \Illuminate\Support\Collection
     */
    public function getRows($paginate = true)
    {
        if ($this->rows) {
            return $this->rows;
        }

        $per_page = $this->request->input('per_page') ?: null;

        return $this->rows = ($paginate ? $this->builder->paginate($per_page) : $this->builder->get());
    }

    /**
     * Return true if the query has records.
     *
     * @return boolean
     */
    public function hasRows()
    {
        return (bool) $this->builder->exists();
    }

    /**
     * Return custom paginator configured to append to existing URL parameters.
     *
     * @return array
     */
    public function getPaginator()
    {
        return new $this->presenter($this->getRows()->appends($this->request->all()));
    }

    /**
     * Return true if the passed argument is the current table's sort order.
     *
     * @return boolean
     */
    public function isSortOrder($sortOrder)
    {
        return $sortOrder == $this->sortOrder;
    }
}
