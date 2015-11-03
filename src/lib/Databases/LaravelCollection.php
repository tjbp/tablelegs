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

class LaravelCollection extends Database implements DatabaseInterface
{
    /**
     * Logic for sorting the underlying database.
     *
     * @param string $key
     * @param string $order
     * @return void
     */
    public function sort($key, $order)
    {
        $this->db->sortBy($key, $order == 'desc');
    }

    /**
     * Return the entire dataset of the underlying database.
     *
     * @return mixed
     */
    public function get()
    {
        return $this->db->all();
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
        $total = $this->db->count();

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $results = $this->db->forPage($page, $per_page);

        return new LengthAwarePaginator($results, $total, $per_page, $page, [
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
        return $this->db->isEmpty();
    }
}
