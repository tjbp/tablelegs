# Tablelegs

Tablelegs allows you to easily build an HTML table from a database, including support for filters, sortable columns and pagination. Tablelegs does not output HTML, it simply provides helpers for outputting a table according to a purpose-built class and can generate URLs for enabling filters and sorting.

## Installation

Tablelegs is installable [with Composer via Packagist](https://packagist.org/packages/tjbp/tablelegs).

## Usage

### Extend `Tablelegs\Table`

Each table should have its own class, though if you have tables with many similarities, you could easily create a base table with the common properties/methods and extend it for each table. Consider placing your all your table classes in a `Tables` namespace within your application.

The bare minimum required for a table is the `$columnHeaders` property. When instantiating your table class, simply pass it your database. Supported databases are simple associative arrays, a numeric array of associative arrays, a Laravel Collection, or a Laravel Eloquent builder. Supported databases are automatically detected.

You can support further databases by extending `Tablelegs\Databases\Database` and implementing `Tablelegs\Databases\DatabaseInterface`, then setting the `$dbClass` property of your table to its name.

Here is a simple example of a table class:

```php
namespace App\Tables;

use Tablelegs\Table;

class ManageUsers extends Table
{
    public $columnHeaders = [
        'user_id' => 'User ID',
        'username' => 'Username',
        'email' => 'Email',
        'joined' => 'Joined',
        'last_login' => 'Last login',
    ];

    public $filters = [
        'Type' => [
            'Administrator',
            'Moderator',
        ],
    ];

    public $presenter = FoundationFivePresenter::class;

    public function filterTypeAdministrator()
    {
        return $this->db->where('administrator', true);
    }

    public function filterTypeSeller()
    {
        return $this->db->where('moderator', true);
    }
}
```

### Column headers

Column headers are defined in the `$columnHeaders` property of a table class, with the URL-friendly name as the key, and the natural language equivalent as the value.

### Sorting

Tablelegs will allow sorting by any column and will attempt to do this itself. However the logic can be overriden using a method defined in the format `sortColumn`. The method will be passed the sort order (`"asc"` or `"desc"`) as its only argument.

### Filters

Filters are defined in the `$filters` property of a table class, as a multi-dimensional array. The keys of the first level of the array represent the natural language name for your filter (the filter key). Values on the second level represent the options for a filter, again in natural language.

Each filter should have a counterpart method defined in the format `filterNameOption`. The method should contain logic that filters the class `$db` property appropriately. The example shown above is using Laravel's Eloquent querying system; if you were using an array database you might utilise `array_filter()` instead.

Note that in URLs, filter names and options are automatically lower-cased and spaces are replaced with underscores, largely for aesthetic reasons.

### Outputting the table (vanilla PHP)

```html+php
<?php

// This is the same table definition as in the example above
$table = new ManageUsers(User::query());

$users = $table->paginate();

?>
<!-- Loop through the filters, outputting the name followed by a link to enable each option -->
<?php foreach ($table->getFilters() as $filter): ?>
    <?php echo $filter->getName() ?>:
    <?php foreach ($filter->getOptions() as $filter_option_key => $filter_option_name): ?>
        <a href="<?php echo $filter->getUrl($filter_option_key) ?>"><?php echo $filter_option_name ?></a>
    <?php endforeach; ?>
<?php endforeach; ?>
<table>
    <thead>
        <tr>
            <!-- Loop through the column headers, outputting the names as links for sorting, and an icon indicating the sort order -->
            <?php foreach ($table->getColumnHeaders() as $column_header): ?>
                <th>
                    <a href="<?php echo $column_header->getUrl() ?>"><?php echo $column_header->getName() ?></a>
                    <?php if ($column_header->isSortKey()): ?>
                        <?php echo $table->getSortOrder() == 'asc' ? '▲' : '▼' ?>
                    <?php endif; ?>
                </th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <!-- Loop through the query results -->
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user->getKey() ?></td>
                <td><?php echo $user->username ?></td>
                <td><?php echo $user->email ?></td>
                <td><?php echo date('Y/m/d', $user->joined) ?></td>
                <td><?php echo date('Y/m/d', $user->last_login) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<div>
    <?php echo $table->paginator() ?>
</div>
```

### Outputting the table (Laravel 5)

Place a method in your controller:

```php
public function getUsers()
{
    // This is the same table definition as in the example above
    $table = new ManageUsers(User::query());

    return view('manage.users', [
        'table' => $table,
        'users' => $table->paginate()
    ]);
}
```

You can use the `paginate()` method to paginate the results, or fetch the entire dataset with `get()`.

Views for use with Laravel's Blade templating system and [ZURB Foundation](http://foundation.zurb.com/) are also included, as used in the following example:

```html+php
@include('tablelegs::filter')
<table class="expand">
    @include('tablelegs::header')
    <tbody>
        @foreach ($users as $user)
            <tr>
                <td>{{ $user->userId }}</td>
                <td>{!! u($user) !!}</td>
                <td>{{ $user->email }}</td>
                <td>{{ date('Y/m/d', $user->joined) }}</td>
                <td>{{ date('Y/m/d', $user->last_login) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="row">
    <div class="medium-12 columns pagination-centered">
        {!! $table->paginator() !!}
    </div>
</div>
```

### Licence

Tablelegs is free and gratis software licensed under the [GPL3 licence](https://www.gnu.org/licenses/gpl-3.0). This allows you to use Tablelegs for commercial purposes, but any derivative works (adaptations to the code) must also be released under the same licence. Mustard is built upon the [Laravel framework](http://laravel.com), which is licensed under the [MIT licence](http://opensource.org/licenses/MIT).
