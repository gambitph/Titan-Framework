<?php

// @see	http://bavotasan.com/2011/convert-hex-color-to-rgb-using-php/
if ( ! function_exists( 'tf_hex2rgb' ) ) {
	function tf_hex2rgb( $hex ) {
		$hex = str_replace( '#', '', $hex );

		if ( strlen( $hex ) == 3 ) {
			$r = hexdec( substr( $hex,0,1 ).substr( $hex,0,1 ) );
			$g = hexdec( substr( $hex,1,1 ).substr( $hex,1,1 ) );
			$b = hexdec( substr( $hex,2,1 ).substr( $hex,2,1 ) );
		} else {
			$r = hexdec( substr( $hex,0,2 ) );
			$g = hexdec( substr( $hex,2,2 ) );
			$b = hexdec( substr( $hex,4,2 ) );
		}
		$rgb = array( $r, $g, $b );
		// return implode(",", $rgb); // returns the rgb values separated by commas
		return $rgb; // returns an array with the rgb values
	}
}


/**
 * Performs an add_action only once. Helpful for factory constructors where an action only
 * needs to be added once. Because of this, there will be no need to do a static variable that
 * will be set to true after the first run, ala $firstLoad
 *
 * @since 1.9
 *
 * @param string   $tag             The name of the action to which the $function_to_add is hooked.
 * @param callback $function_to_add The name of the function you wish to be called.
 * @param int      $priority        Optional. Used to specify the order in which the functions
 *                                  associated with a particular action are executed. Default 10.
 *                                  Lower numbers correspond with earlier execution,
 *                                  and functions with the same priority are executed
 *                                  in the order in which they were added to the action.
 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
 *
 * @return true Will always return true.
 */
function tf_add_action_once( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
	global $_gambitFiltersRan;

	if ( ! isset( $_gambitFiltersRan ) ) {
		$_gambitFiltersRan = array();
	}

	// Since references to $this produces a unique id, just use the class for identification purposes
	$idxFunc = $function_to_add;
	if ( is_array( $function_to_add ) ) {
		if ( ! is_string( $function_to_add[0] ) ) {
			$idxFunc[0] = get_class( $function_to_add[0] );
		}
	}
	$idx = $tag . ':' . _wp_filter_build_unique_id( $tag, $idxFunc, $priority );

	if ( ! in_array( $idx, $_gambitFiltersRan ) ) {
		add_action( $tag, $function_to_add, $priority, $accepted_args );
	}

	$_gambitFiltersRan[] = $idx;

	return true;
}


/**
 * Performs an add_filter only once. Helpful for factory constructors where an action only
 * needs to be added once. Because of this, there will be no need to do a static variable that
 * will be set to true after the first run, ala $firstLoad
 *
 * @since 1.9
 *
 * @param string   $tag             The name of the filter to hook the $function_to_add callback to.
 * @param callback $function_to_add The callback to be run when the filter is applied.
 * @param int      $priority        Optional. Used to specify the order in which the functions
 *                                  associated with a particular action are executed. Default 10.
 *                                  Lower numbers correspond with earlier execution,
 *                                  and functions with the same priority are executed
 *                                  in the order in which they were added to the action.
 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
 *
 * @return true
 */
function tf_add_filter_once( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
	global $_gambitFiltersRan;

	if ( ! isset( $_gambitFiltersRan ) ) {
		$_gambitFiltersRan = array();
	}

	// Since references to $this produces a unique id, just use the class for identification purposes
	$idxFunc = $function_to_add;
	if ( is_array( $function_to_add ) ) {
		if ( ! is_string( $function_to_add[0] ) ) {
			$idxFunc[0] = get_class( $function_to_add[0] );
		}
	}
	$idx = $tag . ':' . _wp_filter_build_unique_id( $tag, $idxFunc, $priority );

	if ( ! in_array( $idx, $_gambitFiltersRan ) ) {
		add_filter( $tag, $function_to_add, $priority, $accepted_args );
	}

	$_gambitFiltersRan[] = $idx;

	return true;
}


/**
 * Fetches post types. Based on helper functions developed inhouse.
 *
 * @since 1.11
 *
 * @param boolean $public - Queries the get_post_types to fetch publicly-available post types.
 * @param string $value - Fetches post types that are builtin, custom, or both. Values can be 'builtin', 'custom', or the default value, 'all'.
 */
function tf_get_post_types( $public = true, $value = 'all' ) {

	// Fetch builtin post types.
	$args_builtin = array(
		'public' => $public,
		'_builtin' => true,
	);

	$post_types_builtin = get_post_types( $args_builtin, 'objects' );

	// Fetch custom post types.
	$args_custom = array(
		'public' => $public,
		'_builtin' => false,
	);

	$post_types_custom = get_post_types( $args_custom, 'objects' );

	// Converge or pick post types based on selection.
	switch ( $value ) {
		case 'builtin' :
			$post_types = $post_types_builtin;
		break;

		case 'custom' :
			$post_types = $post_types_custom;
		break;

		default :
			$post_types = array_merge( $post_types_builtin, $post_types_custom );
		break;
	}

	return $post_types;
}
