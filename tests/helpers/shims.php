<?php
/**
 *  test/shims.php
 *
 * @description: Purpose of this file is to include functions that are in trunk unit testing suite, but not yet in all versions of WordPress core. We'll need to periodly make sure these shims are in parity with the core functions.
 * @copyright  : http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      : 1.0.0
 * @created    : 9/16/2015
 * @author     : DevinWalker
 * @see From: https://github.com/WordImpress/Give/blob/9884fa73ca994800a7aede08309319d16e9621e4/tests/helpers/shims.php
 */
if ( ! function_exists( 'is_post_type_viewable' ) ) {
	/**
	 * Determines whether a post type is considered "viewable".
	 *
	 * For built-in post types such as posts and pages, the 'public' value will be evaluated.
	 * For all others, the 'publicly_queryable' value will be used.
	 *
	 * @since 4.4.0
	 *
	 * @param object $post_type_object Post type object.
	 *
	 * @return bool Whether the post type should be considered viewable.
	 */
	function is_post_type_viewable( $post_type_object ) {
		return $post_type_object->publicly_queryable || ( $post_type_object->_builtin && $post_type_object->public );
	}
}