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

class LaravelEloquent extends Database implements DatabaseInterface
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
        $this->db->orderBy($key, $order);
    }

    /**
     * Return the entire dataset of the underlying database.
     *
     * @return mixed
     */
    public function get()
    {
        return $this->db->get();
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
        return $this->db->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Return true if the underlying database is empty.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return !$this->db->exists();
    }
}
