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

?>
<thead>
    <tr>
        @foreach ($table->getColumnHeaders() as $column_header)
            <th>
                <a href="{{ $column_header->getUrl() }}">{{ $column_header->getName() }}</a>
                @if ($column_header->isSortKey())
                    {{ $table->getSortOrder() == 'asc' ? '▲' : '▼' }}
                @endif
            </th>
        @endforeach
    </tr>
</thead>
