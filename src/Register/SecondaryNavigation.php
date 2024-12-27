<?php
/**
 * Secondary Navigation Tab Manager
 *
 * @package     ArrayPress/WP/Register/TaxonomyTabs
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      ArrayPress
 */

declare( strict_types=1 );

namespace ArrayPress\WP\Register;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class SecondaryNavigation
 */
class SecondaryNavigation {

	/**
	 * The tabs
	 *
	 * @var array
	 */
	protected array $tabs;

	/**
	 * The page ID
	 *
	 * @var string
	 */
	protected string $page_id;

	/**
	 * Configuration arguments
	 *
	 * @var array
	 */
	protected array $args;

	/**
	 * Initialize Secondary Navigation
	 *
	 * @param array  $tabs    The tabs array
	 * @param string $page_id The page identifier
	 * @param array  $args    Optional arguments
	 */
	public function __construct( array $tabs, string $page_id, array $args = [] ) {
		$this->tabs    = $this->prepare_tabs( $tabs, $page_id );
		$this->page_id = $page_id;
		$this->args    = wp_parse_args( $args, [
			'active_tab'    => '',
			'legacy'        => true,
			'wrapper_class' => 'nav-wrapper',
			'nav_class'     => 'nav-tabs-wrapper',
			'list_class'    => 'nav-tabs',
			'item_class'    => 'nav-tab-item',
			'tab_class'     => 'nav-tab',
			'active_class'  => 'nav-tab-active',
			'aria_label'    => __( 'Secondary menu', 'arraypress' ),
		] );
	}

	/**
	 * Render the navigation
	 */
	public function render(): void {
		?>
        <div class="<?php echo esc_attr( $this->args['wrapper_class'] ); ?>">
            <nav class="<?php echo esc_attr( $this->get_nav_classes() ); ?>"
                 aria-label="<?php echo esc_attr( $this->args['aria_label'] ); ?>">
                <ul class="<?php echo esc_attr( $this->args['list_class'] ); ?>">
					<?php $this->render_tabs(); ?>
                </ul>

				<?php $this->render_add_new_button(); ?>
            </nav>
        </div>
		<?php
	}

	/**
	 * Render the "Add New" button.
	 *
	 * Displays an "Add New" button if the user has the capability to create posts
	 * for the current post type. The button is only shown on the main post type page
	 * (not on taxonomy tabs).
	 */
	public function render_add_new_button( ): void {
		if ( isset( $_GET['taxonomy'] ) ) {
			return; // Don't show the button on taxonomy tabs.
		}

		$post_type        = $_GET['post_type'] ?? 'post';
		$post_type_object = get_post_type_object( $post_type );

		if ( $post_type_object && current_user_can( $post_type_object->cap->create_posts ) ) {
			printf(
				'<a href="%s" class="button">%s</a>',
				esc_url( admin_url( "post-new.php?post_type={$post_type}" ) ),
				esc_html( sprintf( __( 'Add New %s', 'arraypress' ), $post_type_object->labels->singular_name ) )
			);
		}
	}


	/**
	 * Render individual tabs
	 */
	protected function render_tabs(): void {
		foreach ( $this->tabs as $slug => $tab_data ) {
			printf(
				'<li class="%4$s"><a href="%1$s" class="%2$s">%3$s</a></li>',
				esc_url( $this->get_tab_url( $slug, $tab_data ) ),
				esc_attr( $this->get_tab_classes( $slug ) ),
				esc_html( $this->get_tab_name( $tab_data ) ),
				esc_attr( $this->get_li_classes( $slug ) )
			);
		}
	}

	/**
	 * Prepare tabs with filters
	 *
	 * @param array  $tabs    The tabs array
	 * @param string $page_id The page identifier
	 *
	 * @return array
	 */
	protected function prepare_tabs( array $tabs, string $page_id ): array {
		/**
		 * Filter the navigation tabs
		 *
		 * @param array  $tabs    The tabs array
		 * @param string $page_id The page identifier
		 */
		return (array) apply_filters( 'arraypress_secondary_navigation_tabs', $tabs, $page_id );
	}

	/**
	 * Get the nav classes
	 *
	 * @return string
	 */
	protected function get_nav_classes(): string {
		$classes = [
			$this->args['nav_class'],
			"{$this->page_id}-nav",
		];

		if ( $this->args['legacy'] ) {
			$classes[] = 'wp-nav-tabs-wrapper';
			$classes[] = 'wp-clearfix';
		}

		return $this->prepare_classes( $classes );
	}

	/**
	 * Get the tab link classes
	 *
	 * @param string $slug The tab slug
	 *
	 * @return string
	 */
	protected function get_tab_classes( string $slug ): string {
		$classes = [ $this->args['tab_class'] ];

		if ( $this->is_tab_active( $slug ) ) {
			$classes[] = $this->args['active_class'];
		}

		if ( $this->args['legacy'] ) {
			$classes[] = 'wp-nav-tab';
		}

		return $this->prepare_classes( $classes );
	}

	/**
	 * Get the li element classes
	 *
	 * @param string $slug The tab slug
	 *
	 * @return string
	 */
	protected function get_li_classes( string $slug ): string {
		$classes = [ $this->args['item_class'] ];

		if ( $this->is_tab_active( $slug ) ) {
			$classes[] = 'active';
		}

		return $this->prepare_classes( $classes );
	}

	/**
	 * Check if a tab is active
	 *
	 * @param string $slug The tab slug
	 *
	 * @return bool
	 */
	protected function is_tab_active( string $slug ): bool {
		$current = $this->get_current_tab();

		return $current === $slug;
	}

	/**
	 * Get the current active tab
	 *
	 * @return string
	 */
	protected function get_current_tab(): string {
		if ( ! empty( $this->args['active_tab'] ) ) {
			return $this->args['active_tab'];
		}

		$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( $tab && array_key_exists( $tab, $this->tabs ) ) {
			return $tab;
		}

		return array_key_first( $this->tabs );
	}

	/**
	 * Get the tab URL
	 *
	 * @param string $slug The tab slug
	 * @param array  $data The tab data
	 *
	 * @return string
	 */
	protected function get_tab_url( string $slug, array $data ): string {
		if ( ! empty( $data['url'] ) ) {
			return $data['url'];
		}

		$args = [
			'page' => $this->page_id,
		];

		if ( array_key_first( $this->tabs ) !== $slug ) {
			$args['tab'] = $slug;
		}

		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Get the tab display name
	 *
	 * @param array $data The tab data
	 *
	 * @return string
	 */
	protected function get_tab_name( array $data ): string {
		return ! empty( $data['name'] ) ? $data['name'] : '';
	}

	/**
	 * Prepare CSS classes
	 *
	 * @param array $classes Array of classes
	 *
	 * @return string
	 */
	protected function prepare_classes( array $classes ): string {
		return implode( ' ', array_map( 'sanitize_html_class', array_filter( $classes ) ) );
	}

}