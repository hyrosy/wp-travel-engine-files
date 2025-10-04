<?php
/**
 * The container class for the plugin.
 *
 * @package WPTravelEngine
 * @since 6.0.0
 */

namespace WPTravelEngine;

use WPTravelEngine\Abstracts\PostModel;

/**
 * Class Container.
 * This class is the container for the WP Travel Engine plugin.
 *
 * @since 6.0.0
 */
final class Container {
	/**
	 * The array of registered classes.
	 *
	 * @var array
	 */
	protected static array $classes = array();

	/**
	 * The array of registered instances.
	 *
	 * @var array
	 */
	protected static array $instances = array();

	/**
	 * Register a class.
	 *
	 * @param string $key The key to register the class with.
	 * @param string $class The class to register.
	 */
	public static function register( string $key, string $class ) {
		self::$classes[ $key ] = $class;
	}

	/**
	 * Register an instance.
	 *
	 * @param string $key The key to register the instance with.
	 * @param object $instance The instance to register.
	 */
	public static function register_instance( string $key, object $instance ) {
		self::$instances[ $key ] = $instance;
	}

	/**
	 * Get a class.
	 *
	 * @param string $key The key of the class to get.
	 *
	 * @return object
	 */
	public static function get( string $key, ...$args ): ?object {
		if ( isset( self::$instances[ $key ] ) ) {
			return self::$instances[ $key ];
		}

		if ( isset( self::$classes[ $key ] ) ) {
			$class = self::$classes[ $key ];

			$instance = new $class( ...$args );

			self::$instances[ $key ] = $instance;

			return $instance;
		}

		return null;
	}

	/**
	 * @return void
	 */
	public static function post( $args, string $post_model_class ) {
		$post = get_post( $args[ 0 ] );

		$instance = Container::$instances[ $post->post_type ] ?? null;

		if ( ! is_subclass_of( $instance, PostModel::class ) || $post->ID !== $instance->get_id() ) {
			Container::$instances[ $post->post_type ] = new $post_model_class( ...$args );
		}

		return Container::$instances[ $post->post_type ];
	}

}
