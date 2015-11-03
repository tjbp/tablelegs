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

namespace Tablelegs\Databases;

use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class AssociativeArray extends Database implements DatabaseInterface
{
    /**
     * Logic for sorting the underlying database.
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function sort($key, $order)
    {
        if ($key == 'key') {
            if ($order == 'asc') {
                ksort($this->db, SORT_NATURAL);
            } else {
                krsort($this->db, SORT_NATURAL);
            }
        } else {
            uasort($this->db, function ($a, $b) use ($order) {
                if (!is_scalar($a) || !is_scalar($b)) {
                    throw new InvalidArgumentException('Cannot not sort non-scalar values');
                }

                return $order == 'asc' ? strnatcasecmp($a, $b) : -strnatcasecmp($a, $b);
            });
        }
    }

    /**
     * Return the entire dataset of the underlying database.
     *
     * @return mixed
     */
    public function get()
    {
        $rows = [];

        foreach ($this->db as $key => $value) {
            $rows[] = (object) [
                'key' => $key,
                'value' => is_string($value) ? $value : print_r($value, true)
            ];
        }

        return $rows;
    }

    /**
     * Return the paginated dataset of the underlying database.
     *
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @param int $page
     * @return \Illuminate\Pagination\Paginator
     */
    public function paginate($perPage, $columns, $pageName, $page)
    {
        $total = count($this->db);

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $results = array_slice($this->get(), ($page - 1) * $perPage, $perPage);

        return new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Return true if the underlying database is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->db);
    }
}
