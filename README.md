# WP-Batcher

If you need to do a bulk change over many items in WordPress 
(with possible Out-of-Memory result) 
this library tries to help you to write less code and avoid OOM.

## How to use

```php
use \Versusbassz\WpBatcher\CallbackIterator;

$iterator = (new CallbackIterator())
	// Provide a callback that will fetch data you want to iterate over.
	// Pls, pay attention to its signature.
	->set_fetcher( function ( $paged, $items_per_page ) {
		$query = new \WP_Query( [
			'post_type' => [ 'post' ],
			'post_status' => [ 'publish' ],
			'paged' => $paged,
			'posts_per_page' => $items_per_page,
			'orderby' => 'ID',
			'order' => 'ASC',
		] );

		return $query->posts;
	} )

	// During iteration instead of fetching N (e.g.: 1000) items for one time
	// the object will fetch X (e.g.: 100) values Y (e.g.: 10) times.
	// This setting is about X value.
	// Default value: 100
	->set_items_per_page( 50 )

	// If you need to restrict the total quantity of processed items (for some reason).
	// Default value: 0 (No limit)
	->set_limit( 1000 )

	// Suspend WP cache addition before a loop
	// and unsuspend it when the loop has been finished.
	// It's disabled by default.
	->use_cache_suspending();

	foreach ( $iterator as $post ) {
		$value = wp_remote_retrieve_body( wp_remote_get( "https://example.org/api/{$post->ID}/" ) );
		$update_result = update_post_meta( $post->ID, 'test_field', $value );
	}
```

To dump the internal state of an iterator object:
```
$iterator->dump();
// returns an array of "prop name" => "prop value"
```

## Compatibility
- PHP >= 5.6 (the target version is a version [required by WordPress](https://wordpress.org/about/requirements/))

## For development
1. `make build-dev`
2. `make run`
3. To run tests:
    - `make test-in-docker`
    - OR `make shell` and inside the container `make test`
