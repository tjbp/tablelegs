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

class TableFilter
{
    /**
     * URL-friendly key for the filter.
     *
     * @var string
     */
    private $key;

    /**
     * Human name for the filter.
     *
     * @var string
     */
    private $name;

    /**
     * Array of possible filter options.
     *
     * @var array
     */
    private $options;

    /**
     * Current request object.
     *
     * @var \Illuminate\Http\Request
     */
    private $request;

    /**
     * Constructor for dependency injection and to create option keys.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $name
     * @param array $options
     * @return void
     */
    public function __construct(Request $request, $name, $options)
    {
        $this->name = $name;

        $this->key = strtolower(preg_replace('/[^\w]/i', '_', $this->name));

        foreach ($options as $option) {
            $option_key = strtolower(preg_replace('/[^\w]/i', '_', $option));

            $this->options[$option_key] = $option;
        }

        $this->request = $request;
    }

    /**
     * Return the human name for the filter.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the URL parameter string to enable this filter.
     *
     * @return string
     */
    public function getUrl($option)
    {
        if ($this->isActive($option)) {
            $params = $this->request->all();

            unset($params[$this->getKey()]);

            return '?' . http_build_query($params);
        }

        return '?' . http_build_query([$this->key => $option] + $this->request->all());
    }

    /**
     * Return the available options for this filter.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Return the URL-friendly key for this filter.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Check if a filter option is active for the current request.
     *
     * @param string $option
     * @return boolean
     */
    public function isActive($option)
    {
        return $this->request->input($this->key) == $option;
    }
}
