# WP-Batcher

If you need to change many items of the same type (posts, users, etc.) in WordPress 
(with possible Out-of-Memory result) 
this library helps you to write less code and avoid OOM.

## Installation
```shell
composer require versusbassz/wp-batcher 0.1.*
```

## How to use
Imagine you have 100000 posts in a database, and you need to iterate over them and change somehow.

Of course, you can't just use `get_posts( [ 'nopaging' => true ] )`, 
because you'll get `Fatal error: memory limit has been exceeded bla bla bla... `.  

So to do the job you need to handle you posts consequentially chunk by chunk (e.g. 100 posts at a time).

The example of code without using the library:

```php
$paged = 1;

wp_suspend_cache_addition( true );

while ( true ) {
	$items = get_posts( [
		'posts_per_page' => 100,
		'paged' => $paged,
		'orderby' => 'ID',
		'order' => 'ASC',
	] );

	if ( ! count( $items ) ) {
		break;
	}

	foreach ( $items as $item ) {
		// Payload
	}

	++$paged;
}

wp_suspend_cache_addition( false );
```

With using the library the code above turns into to:

```php
use \Versusbassz\WpBatcher\WpBatcher;

$iterator = WpBatcher::get_posts();

foreach ( $iterator as $item ) {
	// Payload
}
```
And the library does more than just `wp_suspend_cache_addition()` under the hood.

## Documentation
See [Wiki](https://github.com/versusbassz/wp-batcher/wiki)

## Compatibility
- PHP >= 5.6 (the target version is a version [required by WordPress](https://wordpress.org/about/requirements/))
- WordPress 5.7+

## Versioning and stability
The project follows https://semver.org/

## License
The license of the project is GPL v2 (or later)
