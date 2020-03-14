# QueryBuilder
A PHP QueryBuilder class that construct queries with easy function calls.

## Constructor
```php
$queryBuilder = new QueryBuilder(mysqli $connection);
```
Now, that you have a Query Builder instance on your hands you can start writing queries.

## Find Queries
### QueryBuilder.find()
```php
$rows = $queryBuilder->table("table-name")->find();
```
