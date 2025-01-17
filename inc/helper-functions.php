<?php
/**
 * Helper Functions
 *
 * @package      EAStarter
 * @author       Bill Erickson
 * @since        1.0.0
 * @license      GPL-2.0+
**/

// Duplicate 'the_content' filters
global $wp_embed;
add_filter( 'ea_the_content', array( $wp_embed, 'run_shortcode' ), 8 );
add_filter( 'ea_the_content', array( $wp_embed, 'autoembed'     ), 8 );
add_filter( 'ea_the_content', 'wptexturize'        );
add_filter( 'ea_the_content', 'convert_chars'      );
add_filter( 'ea_the_content', 'wpautop'            );
add_filter( 'ea_the_content', 'shortcode_unautop'  );
add_filter( 'ea_the_content', 'do_shortcode'       );

/**
 * Get the first term attached to post
 *
 * @param string $taxonomy
 * @param string/int $field, pass false to return object
 * @param int $post_id
 * @return string/object
 */
function ea_first_term( $taxonomy = 'category', $field = false, $post_id = false ) {

	$post_id = $post_id ? $post_id : get_the_ID();
	$term = false;

	// Use WP SEO Primary Term
	// from https://github.com/Yoast/wordpress-seo/issues/4038
	if( class_exists( 'WPSEO_Primary_Term' ) ) {
		$term = get_term( ( new WPSEO_Primary_Term( $taxonomy,  $post_id ) )->get_primary_term(), $taxonomy );
	}

	// Fallback on term with highest post count
	if( ! $term || is_wp_error( $term ) ) {

		$terms = get_the_terms( $post_id, $taxonomy );

		if( empty( $terms ) || is_wp_error( $terms ) )
			return false;

		// If there's only one term, use that
		if( 1 == count( $terms ) ) {
			$term = array_shift( $terms );

		// If there's more than one...
		} else {

			// Sort by term order if available
			// @uses WP Term Order plugin
			if( isset( $terms[0]->order ) ) {
				$list = array();
				foreach( $terms as $term )
					$list[$term->order] = $term;
				ksort( $list, SORT_NUMERIC );

			// Or sort by post count
			} else {
				$list = array();
				foreach( $terms as $term )
					$list[$term->count] = $term;
				ksort( $list, SORT_NUMERIC );
				$list = array_reverse( $list );
			}

			$term = array_shift( $list );
		}
	}

	// Output
	if( $field && isset( $term->$field ) )
		return $term->$field;

	else
		return $term;
}

/**
 * Conditional CSS Classes
 *
 * @param string $base_classes, classes always applied
 * @param string $optional_class, additional class applied if $conditional is true
 * @param bool $conditional, whether to add $optional_class or not
 * @return string $classes
 */
function ea_class( $base_classes, $optional_class, $conditional ) {
	return $conditional ? $base_classes . ' ' . $optional_class : $base_classes;
}

/**
 * Column Classes
 *
 * Adds "-first" classes when appropriate for clearing float
 * @see /assets/scss/partials/layout.scss
 *
 * @param array $classes, bootstrap-style classes, ex: array( 'col-lg-4', 'col-md-6' )
 * @param int $current, current post in loop
 * @param bool $join, whether to join classes (return string) or not (return array)
 * @return string/array $classes
 */
function ea_column_class( $classes = array(), $current = false, $join = true ) {

	if( false === $current )
		return $classes;

	$columns = array( 2, 3, 4, 6 );
	foreach( $columns as $column ) {
		if( 0 == $current % $column ) {

			$col = 12 / $column;
			foreach( $classes as $class ) {
				if( false != strstr( $class, (string) $col ) && false == strstr( $class, '12' ) ) {
					$classes[] = str_replace( $col, 'first', $class );
				}
			}
		}
	}

	if( $join ) {
		return join( ' ', $classes );
	} else {
		return $classes;
	}
}

/**
 *  Background Image Style
 *
 * @param int $image_id
 * @return string $output
 */
function ea_bg_image_style( $image_id = false, $image_size = 'full' ) {
	if( !empty( $image_id ) )
		return ' style="background-image: url(' . wp_get_attachment_image_url( $image_id, $image_size ) . ');"';
}

/**
 * Get Icon
 * This function is in charge of displaying SVG icons across the site.
 *
 * Place each <svg> source in the /assets/icons/{group}/ directory, without adding
 * both `width` and `height` attributes, since these are added dynamically,
 * before rendering the SVG code.
 *
 * All icons are assumed to have equal width and height, hence the option
 * to only specify a `$size` parameter in the svg methods.
 *
 */
function ea_icon( $atts = array() ) {

	$atts = shortcode_atts( array(
		'icon'	=> false,
		'group'	=> 'utility',
		'size'	=> 16,
		'class'	=> false,
		'label'	=> false,
	), $atts );

	if( empty( $atts['icon'] ) )
		return;

	$icon_path = get_theme_file_path( '/assets/icons/' . $atts['group'] . '/' . $atts['icon'] . '.svg' );
	if( ! file_exists( $icon_path ) )
		return;

	$icon = file_get_contents( $icon_path );

	$class = 'svg-icon';
	if( !empty( $atts['class'] ) )
		$class .= ' ' . esc_attr( $atts['class'] );

	$repl = sprintf( '<svg class="' . $class . '" width="%d" height="%d" aria-hidden="true" role="img" focusable="false" ', $atts['size'], $atts['size'] );
	$svg  = preg_replace( '/^<svg /', $repl, trim( $icon ) ); // Add extra attributes to SVG code.
	$svg  = preg_replace( "/([\n\t]+)/", ' ', $svg ); // Remove newlines & tabs.
	$svg  = preg_replace( '/>\s*</', '><', $svg ); // Remove white space between SVG tags.

	if( !empty( $atts['label'] ) ) {
		$svg = str_replace( '<svg class', '<svg aria-label="' . esc_attr( $atts['label'] ) . '" class', $svg );
		$svg = str_replace( 'aria-hidden="true"', '', $svg );
	}

	return $svg;
}

/**
 * Has Action
 *
 */
function ea_has_action( $hook ) {
	ob_start();
	do_action( $hook );
	$output = ob_get_clean();
	return !empty( $output );
}
