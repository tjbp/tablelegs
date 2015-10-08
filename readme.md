## Tablelegs

Tablelegs allows you to easily build an HTML table from a database model, including support for filters, sortable columns and pagination. Tablelegs does not output HTML, it simply provides helpers for outputting a table according to a purpose-built class and can generate URLs for enabling filters and sorting. It is dependent upon the [Laravel framework](http://laravel.com)'s database and HTTP components.

### Installation

Tablelegs is installable [with Composer via Packagist](https://packagist.org/packages/tjbp/tablelegs).

### Usage

Extend the Tablelegs\Table class for each table you want to build:

```php
use App\User;
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

    public $model = User::class;

    public function filterTypeAdministrator($builder)
    {
        $builder->where('administrator', true);
    }

    public function filterTypeSeller($builder)
    {
        $builder->where('moderator', true);
    }
}
```

Place a method in your controller:

```php
public function getUsers(Request $request)
{
    $table = new ManageUsers($request);

    return view('manage.users', [
        'table' => $table,
    ]);
}
```

Finally use the Table object in the view:

```html+php
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
        <?php foreach ($table->getRows() as $user): ?>
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
    <?php echo $table->getRows()->render() ?>
</div>
```

Views for use with Laravel's Blade templating system and [ZURB Foundation](http://foundation.zurb.com/) are also included, as used in the following example:

```html+php
@include('tablelegs::filter')
<table class="expand">
    @include('tablelegs::header')
    <tbody>
        @foreach ($table->getRows() as $user)
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
        {!! $table->getRows()->render() !!}
    </div>
</div>
```

### Licence

Tablelegs is free and gratis software licensed under the [GPL3 licence](https://www.gnu.org/licenses/gpl-3.0). This allows you to use Tablelegs for commercial purposes, but any derivative works (adaptations to the code) must also be released under the same licence. Mustard is built upon the [Laravel framework](http://laravel.com), which is licensed under the [MIT licence](http://opensource.org/licenses/MIT).
