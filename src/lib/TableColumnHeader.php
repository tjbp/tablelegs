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

class TableColumnHeader
{
    /**
     * URL-friendly key for the column header.
     *
     * @var $key
     */
    private $key;

    /**
     * Human name for the column header.
     *
     * @var $name
     */
    private $name;

    /**
     * Current request object.
     *
     * @var $request
     */
    private $request;

    /**
     * Constructor for dependency injection.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $key
     * @param array $name
     * @return void
     */
    public function __construct(Request $request, $key, $name)
    {
        $this->key = $key;

        $this->name = $name;

        $this->request = $request;
    }

    /**
     * Return the human name for the column header.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the URL parameter string to sort by this column header.
     *
     * @return string
     */
    public function getUrl()
    {
        $params = ['sort_key' => $this->key];

        if ($this->isSortKey()) {
            $params['sort_order'] = $this->request->input('sort_order') == 'desc' ? 'asc' : 'desc';
        }

        return '?' . http_build_query($params + $this->request->all());
    }

    /**
     * Check if the current request is sorted according to this column header.
     *
     * @return boolean
     */
    public function isSortKey()
    {
        return $this->request->input('sort_key') == $this->key;
    }
}
