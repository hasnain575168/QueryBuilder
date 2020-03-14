# QueryBuilder
A PHP QueryBuilder class that construct queries with easy function calls.

## Constructor
```php
$queryBuilder = new QueryBuilder(mysqli $connection);
```
Now, that you have a Query Builder instance on your hands you can start writing queries.

## Find Queries
### QueryBuilder.find()
This function will return many rows in form of array.
```php
$rows = $queryBuilder->table(string $table_name)->find();
```
### QueryBuilder.findOne()
This function will return a single row as an assoc array.
```php
$row = $queryBuilder->table(string $table_name)->findOne();
```
### QueryBuilder.where()
This function will return very specific records using where clause.
```php
$row = $queryBuilder->table(string $table_name)->where(string $key, string $value, string $case = "=")->findOne();
```
