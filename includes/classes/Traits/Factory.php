<?php
/**
 * Factory Trait Class.
 *
 * @package WPTravelEngine\Traits
 * @since 6.0.0
 */

namespace WPTravelEngine\Traits;

use WPTravelEngine\Abstracts\PostModel;
use WPTravelEngine\Container;

/**
 * Factory Trait Class.
 *
 * @since 6.0.0
 */
trait Factory {
	/**
	 * Create a new instance of the class.
	 *
	 * @param array $args The arguments to pass to the constructor.
	 *
	 * @return object
	 */
	public static function make( ...$args ): object {
		$className = static::class;

		if ( is_subclass_of( $className, PostModel::class ) ) {
			return Container::post( $args, $className );
		}

		Container::register( $className, $className );

		return Container::get( $className, ...$args );
	}
}
