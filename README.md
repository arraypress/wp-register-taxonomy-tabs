# WordPress Taxonomy Tabs Manager

A WordPress library for creating organized taxonomy navigation tabs in the admin interface, providing a better user experience for managing taxonomies across post types.

## Features

- Seamless taxonomy navigation interface
- Custom tab support
- Screen-specific styling
- Responsive design
- Parent menu highlighting
- Configurable UI elements
- WordPress core styling integration

## Requirements

- PHP 7.4 or higher
- WordPress 6.0 or higher

## Installation

Install via Composer:

```bash
composer require arraypress/wp-taxonomy-tabs
```

## Usage

### Object-Oriented Approach

```php
use ArrayPress\WP\Register\TaxonomyTabs;

// Basic setup
$tabs = new TaxonomyTabs( 'post', [
	'taxonomies' => [ 'category', 'tag' ]
] );

// Advanced setup
$tabs = new TaxonomyTabs( 'download', [
	'taxonomies'   => [ 'download_category', 'download_tag' ],
	'custom_tabs'  => [
		'settings' => [
			'name' => 'Settings',
			'url'  => admin_url( 'edit.php?post_type=download&page=settings' )
		]
	],
	'hide_heading' => true,
	'modify_title' => true
] );
```

### Procedural Approach

```php
// Using helper function
register_taxonomy_tabs( 'post', [
	'taxonomies' => [ 'category', 'tag' ]
] );

// With admin hook
add_action( 'admin_init', function () {
	register_taxonomy_tabs( 'download', [
		'taxonomies'  => [ 'download_category', 'download_tag' ],
		'custom_tabs' => [
			'reports' => [
				'name' => 'Reports',
				'url'  => admin_url( 'edit.php?post_type=download&page=reports' )
			]
		]
	] );
} );
```

## Configuration Options

### Core Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| taxonomies | array | [] | Specific taxonomies to include |
| custom_tabs | array | [] | Additional custom navigation tabs |
| legacy_classes | bool | true | Enable WordPress legacy classes |
| menu_priority | int | 999 | Menu registration priority |
| parent_menu_file | string | '' | Parent menu file path |
| highlight_parent | bool | true | Highlight parent menu item |

### Display Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| screen_base | array | ['edit', 'edit-tags', 'post-new', 'post'] | Valid screen bases |
| hide_heading | bool | true | Hide page heading |
| hide_menu_add_new | bool | true | Hide add new button |
| modify_title | bool | true | Modify admin title |
| page_id | string | '' | Custom page identifier |

## Filter Hooks

### Taxonomy Tabs Filter

Modify the tabs array for a specific post type:

```php
add_filter( "arraypress_{$post_type}_taxonomy_tabs", function ( $tabs, $post_type ) {
	// Add or modify tabs
	$tabs['custom'] = [
		'name' => 'Custom Tab',
		'url'  => admin_url( 'edit.php?post_type=' . $post_type . '&page=custom' )
	];

	return $tabs;
}, 10, 2 );
```

### UI Element Filters

Control visibility of UI elements:

```php
// Control heading visibility
add_filter( "arraypress_{$post_type}_hide_heading", function ( $hide, $post_type ) {
	return true; // or false
}, 10, 2 );

// Control add new button visibility
add_filter( "arraypress_{$post_type}_hide_menu_add_new", function ( $hide, $post_type ) {
	return true; // or false
}, 10, 2 );

// Control title modification
add_filter( "arraypress_{$post_type}_modify_title", function ( $modify, $post_type ) {
	return true; // or false
}, 10, 2 );
```

### Secondary Navigation Filter

Customize the navigation tab structure:

```php
add_filter( 'arraypress_secondary_navigation_tabs', function ( $tabs, $page_id ) {
	// Modify navigation structure
	return $tabs;
}, 10, 2 );
```

## Advanced Usage

### Custom Tab Integration

```php
$tabs = new TaxonomyTabs( 'product', [
	'taxonomies'   => [ 'product_cat', 'product_tag' ],
	'custom_tabs'  => [
		'settings' => [
			'name' => 'Settings',
			'url'  => admin_url( 'edit.php?post_type=product&page=settings' )
		],
		'reports'  => [
			'name' => 'Reports',
			'url'  => admin_url( 'edit.php?post_type=product&page=reports' )
		]
	],
	'screen_base'  => [ 'edit', 'edit-tags' ],
	'hide_heading' => true,
	'modify_title' => true
] );
```

### Multiple Post Types

```php
$post_types = [ 'download', 'product', 'course' ];

foreach ( $post_types as $post_type ) {
	register_taxonomy_tabs( $post_type, [
		'hide_heading'      => true,
		'hide_menu_add_new' => true
	] );
}
```

## License

GPL2+ License. See LICENSE file for details.

## Credits

Developed by ArrayPress Limited.

## Support

For support, feature requests, and bug reports, please use the [GitHub issues](https://github.com/arraypress/wp-taxonomy-tabs/issues) page.