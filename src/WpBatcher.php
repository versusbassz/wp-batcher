<?php

namespace Versusbassz\WpBatcher;

use Exception;

use Versusbassz\WpBatcher\Feature\CacheCleaner;
use Versusbassz\WpBatcher\Feature\WpActionsRestorer;
use Versusbassz\WpBatcher\Feature\WpdbQueriesLogCleaner;
use Versusbassz\WpBatcher\Feature\WpQueryGcProblemsFixer;

class WpBatcher {
	/**
	 * @param array $args
	 *
	 * @return CallbackIterator
	 * @throws Exception
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_posts/
	 * @see \get_posts()
	 * @see \WP_Query::parse_query()
	 */
	public static function get_posts( $args = [] ) {
		return self::callback( function ( $paged, $items_per_page ) use ( $args ) {
				$default_args = [
					'orderby' => 'ID',
					'order' => 'ASC',
				];

				$_args = $args;

				$required_args = [
					'paged' => $paged,
					'posts_per_page' => $items_per_page,
				];

				$query_args = array_merge( $default_args, $_args, $required_args );

				return get_posts( $query_args );
			} );
	}

	/**
	 * @param array $args
	 *
	 * @return CallbackIterator
	 * @throws Exception
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_users/
	 * @see \get_users()
	 * @see \WP_User_Query::prepare_query()
	 */
	public static function get_users( $args = [] ) {
		return self::callback( function ( $paged, $items_per_page ) use ( $args ) {
				$default_args = [
					'orderby' => 'ID',
					'order' => 'ASC',
				];

				$_args = $args;

				$required_args = [
					'paged' => $paged,
					'number' => $items_per_page,
				];

				$query_args = array_merge( $default_args, $_args, $required_args );

				return get_users( $query_args );
			} );
	}

	/**
	 * @param array $args
	 *
	 * @return CallbackIterator
	 * @throws Exception
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_terms/
	 * @see \get_terms()
	 * @see \WP_Term_Query::__construct()
	 */
	public static function get_terms( $args = [] ) {
		return self::callback( function ( $paged, $items_per_page ) use ( $args ) {
				$default_args = [
					'orderby' => 'term_id',
					'order' => 'ASC',
				];

				$_args = $args;

				$required_args = [
					'offset' => ( $paged - 1 ) * $items_per_page,
					'number' => $items_per_page,
				];

				$query_args = array_merge( $default_args, $_args, $required_args );

				return get_terms( $query_args );
			} );
	}

	/**
	 * @param $args
	 *
	 * @return CallbackIterator
	 * @throws Exception
	 *
	 * @see https://developer.wordpress.org/reference/functions/get_comments/
	 * @see \get_comments()
	 * @see \WP_Comment_Query::__construct()
	 */
	public static function get_comments( $args = [] ) {
		return self::callback( function ( $paged, $items_per_page ) use ( $args ) {
				$default_args = [
					'orderby' => 'comment_ID',
					'order' => 'ASC',
				];

				$_args = $args;

				$required_args = [
					'paged' => $paged,
					'number' => $items_per_page,
				];

				$query_args = array_merge( $default_args, $_args, $required_args );

				return get_comments( $query_args );
			} );
	}

	/**
	 * @param $callable
	 *
	 * @return CallbackIterator
	 * @throws Exception
	 */
	public static function callback( $callable ) {
		return ( new CallbackIterator())
			->set_fetcher( $callable )
			->add_feature( new CacheCleaner() )
			->add_feature( new WpQueryGcProblemsFixer() )
			->add_feature( new WpdbQueriesLogCleaner() )
			->add_feature( new WpActionsRestorer() );
	}
}
