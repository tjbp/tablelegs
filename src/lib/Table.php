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

class Table
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
     * Class name for the base database model.
     *
     * @var $model
     */
    public $model;

    /**
     * Default key to sort by.
     *
     * @var $defaultSortKey
     */
    private $defaultSortKey;

    /**
     * Default sort order.
     *
     * @var $defaultSortOrder
     */
    private $defaultSortOrder;

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
     * Constructor for dependency injection.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(Request $request)
    {
        // Set the default sorting settings
        $this->defaultSortKey = array_keys($this->columnHeaders)[0];

        $this->defaultSortOrder = 'asc';

        // Instantiate the query builder
        $this->builder = call_user_func($this->model . '::query');

        $this->request = $request;

        $this->sortKey = $request->input('sort_key') ?: $this->defaultSortKey;

        $this->sortOrder = $request->input('sort_order') ?: $this->defaultSortOrder;

        // Apply the sorting for the query
        $this->builder->orderBy($this->sortKey, $this->sortOrder);

        // Instantiate column header objects
        foreach ($this->columnHeaders as $column_key => $column_name) {
            $column_headers[] = new TableColumnHeader($this->request, $column_key, $column_name);
        }

        $this->columnHeaders = $column_headers;

        // Instantiate filter objects
        foreach ($this->filters as $filter_name => $filter_options) {
            $filter = new TableFilter($this->request, $filter_name, $filter_options);

            $filters[] = $filter;

            $filter_key = $filter->getKey();

            // Execute the filter method if enabled
            if ($request->has($filter_key)) {
                $filter_option = $request->input($filter_key);

                if (!isset($this->filters[$filter_key][$request->input($filter_key)])) {
                    continue;
                }

                $this->builder = $this->${"filter$filter_key$filter_option"}($this->builder);
            }
        }

        $this->filters = $filters;
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
}
