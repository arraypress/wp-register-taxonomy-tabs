<?php
/**
 * Taxonomy Tabs Registration Helper
 *
 * @package     ArrayPress/WP/Register/TaxonomyTabs
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

use ArrayPress\WP\Register\TaxonomyTabs;

if ( ! function_exists( 'register_taxonomy_tabs' ) ):
	/**
	 * Helper function to create a new TaxonomyTabs instance
	 *
	 * @param string $post_type Post type to manage
	 * @param array  $config    Optional configuration settings
	 *
	 * @return TaxonomyTabs
	 */
	function register_taxonomy_tabs( string $post_type, array $config = [] ): TaxonomyTabs {
		return new TaxonomyTabs( $post_type, $config );
	}
endif;