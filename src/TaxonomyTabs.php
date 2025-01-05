<?php
/**
 * Post Type Taxonomy Tabs Manager
 *
 * @package     ArrayPress/Utils/TaxonomyTabs
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      ArrayPress
 */

declare( strict_types=1 );

namespace ArrayPress\WP\Register;

// Exit if accessed directly
use InvalidArgumentException;

defined( 'ABSPATH' ) || exit;

/**
 * Class TaxonomyTabs
 *
 * @since 1.0.0
 */
class TaxonomyTabs {

	/**
	 * Default settings
	 */
	protected const DEFAULTS = [
		// Core functionality
		'taxonomies'             => [], // If empty, uses all; otherwise just the specified ones
		'custom_tabs'            => [],

		'legacy_classes'      => true,

		// Menu settings
		'menu_priority'       => 999,
		'parent_menu_file'    => '',
		'highlight_parent'    => true,

		// Display settings
		'screen_base'         => [ 'edit', 'edit-tags', 'post-new', 'post' ],
		'hide_heading'        => true,
		'hide_menu_add_new'   => true,
		'modify_title'        => true,

		// Navigation settings
		'page_id'             => '',
		'navigation_class'    => SecondaryNavigation::class,
	];

	/**
	 * Post type to manage
	 *
	 * @var string
	 */
	protected string $post_type;

	/**
	 * Configuration settings
	 *
	 * @var array
	 */
	protected array $config;

	/**
	 * Stored taxonomies
	 *
	 * @var array
	 */
	protected array $taxonomies = [];

	/**
	 * Initialize the Taxonomy Tabs Manager
	 *
	 * @param string $post_type Post type to manage
	 * @param array  $config    Configuration settings
	 *
	 * @throws InvalidArgumentException When post type is invalid
	 */
	public function __construct( string $post_type, array $config = [] ) {
		if ( ! post_type_exists( $post_type ) ) {
			throw new InvalidArgumentException( sprintf( 'Invalid post type: %s', $post_type ) );
		}

		$this->post_type = $post_type;
		$this->config    = wp_parse_args( $config, self::DEFAULTS );

		// Set default page ID if not provided
		if ( empty( $this->config['page_id'] ) ) {
			$this->config['page_id'] = $post_type . '-taxonomy-tabs';
		}

		// Set default parent menu file if not provided
		if ( empty( $this->config['parent_menu_file'] ) ) {
			$this->config['parent_menu_file'] = "edit.php?post_type={$post_type}";
		}

		$this->initialize();
	}

	/**
	 * Initialize hooks and load taxonomies
	 */
	protected function initialize(): void {
		// Load taxonomies
		$this->load_taxonomies();

		// Hook into WordPress
		add_action( 'admin_menu', [ $this, 'adjust_submenus' ], $this->config['menu_priority'] );

		if ( $this->config['highlight_parent'] ) {
			add_action( 'admin_head', [ $this, 'modify_menu_highlight' ], 9999 );
			add_action( 'admin_head', [ $this, 'modify_new_highlight' ], 9999 );
		}

		// Add tab display hook
		add_action( 'admin_notices', [ $this, 'display_tabs' ] );

		// Handle UI modifications - both global and screen-specific
		add_action( 'admin_head', [ $this, 'add_global_styles' ] ); // Add this for global styles
		add_action( 'admin_head', [ $this, 'modify_admin_ui' ] );
	}

	/**
	 * Load taxonomies based on configuration
	 */
	protected function load_taxonomies(): void {
		$registered = get_object_taxonomies( $this->post_type, 'objects' );

		if ( empty( $registered ) ) {
			return;
		}

		// If no specific taxonomies defined, use all registered ones
		if ( empty( $this->config['taxonomies'] ) ) {
			$this->taxonomies = $registered;

			return;
		}

		// Otherwise, only load the specified taxonomies
		foreach ( $this->config['taxonomies'] as $tax ) {
			if ( isset( $registered[ $tax ] ) ) {
				$this->taxonomies[ $tax ] = $registered[ $tax ];
			}
		}
	}

	/**
	 * Remove taxonomy submenus
	 */
	public function adjust_submenus(): void {
		foreach ( array_keys( $this->taxonomies ) as $taxonomy ) {
			remove_submenu_page(
				$this->config['parent_menu_file'],
				"edit-tags.php?taxonomy={$taxonomy}&amp;post_type={$this->post_type}"
			);
		}
	}

	/**
	 * Modify menu highlight for taxonomies
	 */
	public function modify_menu_highlight(): void {
		global $submenu_file, $parent_file;

		// Bail if not viewing a taxonomy
		if ( empty( $_GET['taxonomy'] ) ) {
			return;
		}

		$taxonomy = sanitize_key( $_GET['taxonomy'] );

		// Bail if not our taxonomy
		if ( ! isset( $this->taxonomies[ $taxonomy ] ) ) {
			return;
		}

		// Force the parent/submenu files
		$parent_file  = $this->config['parent_menu_file'];
		$submenu_file = $this->config['parent_menu_file'];
	}

	/**
	 * Modify menu highlight for new items
	 */
	public function modify_new_highlight(): void {
		global $submenu_file, $parent_file, $pagenow;

		// Bail if not adding new
		if ( 'post-new.php' !== $pagenow || empty( $_GET['post_type'] ) ) {
			return;
		}

		// Bail if not our post type
		if ( $this->post_type !== sanitize_key( $_GET['post_type'] ) ) {
			return;
		}

		// Force the parent/submenu files
		$parent_file  = $this->config['parent_menu_file'];
		$submenu_file = $this->config['parent_menu_file'];
	}

	/**
	 * Display taxonomy tabs
	 */
	public function display_tabs(): void {
		if ( ! $this->should_display_tabs() ) {
			return;
		}

		// Build initial tabs array
		$tabs = [
			'items' => [
				'name' => $this->get_post_type_label(),
				'url'  => admin_url( $this->config['parent_menu_file'] ),
			],
		];

		// Add taxonomy tabs
		foreach ( $this->taxonomies as $tax => $details ) {
			$tabs[ $tax ] = [
				'name' => $details->labels->menu_name,
				'url'  => add_query_arg(
					[
						'taxonomy'  => $tax,
						'post_type' => $this->post_type,
					],
					admin_url( 'edit-tags.php' )
				),
			];
		}

		// Add any additional custom tabs
		$tabs = array_merge( $tabs, $this->config['custom_tabs'] );

		/**
		 * Filter the tabs array
		 *
		 * @param array  $tabs      The tabs array
		 * @param string $post_type The post type
		 */
		$tabs = apply_filters( "arraypress_{$this->post_type}_taxonomy_tabs", $tabs, $this->post_type );

		// Create navigation instance
		$class = $this->config['navigation_class'];
		if ( ! class_exists( $class ) ) {
			$class = SecondaryNavigation::class;
		}

		$navigation = new $class(
			$tabs,
			$this->config['page_id'],
			[
				'active_tab' => $this->get_active_tab(),
				'legacy'     => $this->config['legacy_classes'],
			]
		);

		$navigation->render();
	}

	/**
	 * Check if tabs should be displayed
	 *
	 * @return bool
	 */
	protected function should_display_tabs(): bool {
		global $pagenow;

		// Get current screen
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}

		// Check if we're on a valid screen base
		if ( ! in_array( $screen->base, $this->config['screen_base'], true ) ) {
			return false;
		}

		// Check if we're on our post type
		if ( ! empty( $_GET['post_type'] ) && $this->post_type !== sanitize_key( $_GET['post_type'] ) ) {
			return false;
		}

		// Check taxonomy pages
		if ( 'edit-tags.php' === $pagenow && ! empty( $_GET['taxonomy'] ) ) {
			return isset( $this->taxonomies[ sanitize_key( $_GET['taxonomy'] ) ] );
		}

		return true;
	}

	/**
	 * Get the active tab
	 *
	 * @return string
	 */
	protected function get_active_tab(): string {
		global $pagenow;

		if ( 'edit-tags.php' === $pagenow && ! empty( $_GET['taxonomy'] ) ) {
			return sanitize_key( $_GET['taxonomy'] );
		}

		return 'items';
	}

	/**
	 * Get post type label
	 *
	 * @return string
	 */
	protected function get_post_type_label(): string {
		$post_type_obj = get_post_type_object( $this->post_type );

		return $post_type_obj ? $post_type_obj->labels->name : ucfirst( $this->post_type );
	}

	/**
	 * Add global styles that should apply across all admin pages
	 */
	public function add_global_styles(): void {
		$styles = [];

		// Handle add new button visibility globally
		$hide_add_new = (bool) apply_filters(
			"arraypress_{$this->post_type}_hide_menu_add_new",
			$this->config['hide_menu_add_new'],
			$this->post_type
		);

		if ( $hide_add_new ) {
			$styles[] = sprintf(
				'#menu-posts-%s li > a[href^="post-new.php?post_type=%s"] { display: none !important; }',
				$this->post_type,
				$this->post_type
			);
		}

		// Output global styles if any
		if ( ! empty( $styles ) ) {
			printf( '<style>%s</style>', implode( ' ', $styles ) );
		}
	}

	/**
	 * Modify admin UI elements (screen-specific styles)
	 */
	public function modify_admin_ui(): void {
		if ( ! $this->should_display_tabs() ) {
			return;
		}

		$styles = [];

		// Handle heading visibility
		$hide_heading = (bool) apply_filters(
			"arraypress_{$this->post_type}_hide_heading",
			$this->config['hide_heading'],
			$this->post_type
		);

		if ( $hide_heading ) {
			$styles[] = '.wp-heading-inline { display: none !important; }';
		}

		// Add New button visibility and responsiveness
		$styles[] = "
	        @media screen and (max-width: 600px) {
	            .nav-tabs-wrapper .button {
	                display: none !important;
	            }
	        }
	        @media screen and (min-width: 601px) {
	            .page-title-action {
	                display: none !important;
	            }
	        }
	        .nav-tabs-wrapper .button {
	            margin: 2px 0 0 12px;
	        }
	    ";

		// Modify page title if needed
		$modify_title = (bool) apply_filters(
			"arraypress_{$this->post_type}_modify_title",
			$this->config['modify_title'],
			$this->post_type
		);

		if ( $modify_title ) {
			add_filter( 'admin_title', [ $this, 'modify_admin_title' ], 10, 2 );
		}

		if ( ! empty( $styles ) ) {
			printf( '<style>%s</style>', implode( ' ', $styles ) );
		}
	}

	/**
	 * Modify admin title
	 *
	 * @param string $admin_title The full admin title
	 * @param string $title       The page title
	 *
	 * @return string
	 */
	protected function modify_admin_title( string $admin_title, string $title ): string {
		global $pagenow;

		// Handle taxonomy pages
		if ( 'edit-tags.php' === $pagenow && ! empty( $_GET['taxonomy'] ) ) {
			$taxonomy = sanitize_key( $_GET['taxonomy'] );
			if ( isset( $this->taxonomies[ $taxonomy ] ) ) {
				$tax_object       = get_taxonomy( $taxonomy );
				$post_type_object = get_post_type_object( $this->post_type );

				if ( $tax_object && $post_type_object ) {
					$admin_title = sprintf(
						'%s &lsaquo; %s &#8212; %s',
						$tax_object->labels->name,
						$post_type_object->labels->name,
						get_bloginfo( 'name' )  // Get the actual site name
					);
				}
			}
		}

		return $admin_title;
	}

}