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

use BadMethodCallException;

abstract class Database
{
    /**
     * The underlying database.
     *
     * @var
     */
    protected $db;

    /**
     * Constructor for dependency injection.
     *
     * @param mixed $db
     *
     * @return void
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Magic method for passing undeclared method calls to the underlying
     * database.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (is_callable([$this->db, $method])) {
            return call_user_func_array([$this->db, $method], $parameters);
        }

        $class_name = get_class($this);

        throw new BadMethodCallException("Call to undefined method {$class_name}::{$method}()");
    }
}
