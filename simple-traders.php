<?php
/*
Plugin Name: Simple Traders
Plugin URI: http://plugins.findingsimple.com
Description: Build a library of traders that can be used by a theme or within content.
Version: 1.0
Author: Finding Simple
Author URI: http://findingsimple.com
License: GPL2
*/
/*
Copyright 2012  Finding Simple  (email : plugins@findingsimple.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once dirname( __FILE__ ) . '/simple-traders-list.php';

if ( ! class_exists( 'Simple_Traders' ) ) :

/**
 * So that themes and other plugins can customise the text domain, the Simple_Traders
 * should not be initialized until after the plugins_loaded and after_setup_theme hooks.
 * However, it also needs to run early on the init hook.
 *
 * @author Jason Conroy <jason@findingsimple.com>
 * @package Simple Traders
 * @since 1.0
 */
function initialize_traders(){
	Simple_Traders::init();
}
add_action( 'init', 'initialize_traders', -1 );

/**
 * Plugin Main Class.
 *
 * @package Simple Traders
 * @author Jason Conroy <jason@findingsimple.com>
 * @since 1.0
 */
class Simple_Traders {

	static $text_domain;

	static $post_type_name;

	static $admin_screen_id;
	
	static $defaults;
	
	static $add_scripts;
	
	/**
	 * Initialise
	 */
	public static function init() {
	
		global $wp_version;
		
		self::$defaults = array(
			'x' => '',
			'y' => array()
		);
		
		self::$text_domain = apply_filters( 'simple_traders_text_domain', 'Simple_Traders' );

		self::$post_type_name = apply_filters( 'simple_traders_post_type_name', 'simple_trader' );

		self::$admin_screen_id = apply_filters( 'simple_traders_admin_screen_id', 'simple_traders' );

		self::$defaults = apply_filters( 'simple_traders_defaults', self::$defaults );
		
		add_action( 'init', array( __CLASS__, 'register' ) );
		
		add_filter( 'post_updated_messages', array( __CLASS__, 'updated_messages' ) );
		
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ) );
						
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ) );
		
		add_action( 'save_post', array( __CLASS__, 'save_meta' ), 10, 1 );
		
		add_action( 'init', __CLASS__ . '::register_image_sizes' , 99 );
				
		add_action( 'init', array( __CLASS__, 'add_styles_and_scripts') );
		
		add_action( 'wp_footer', array(__CLASS__, 'print_footer_scripts') );
		
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles_and_scripts'), 100 );
		
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_styles_and_scripts' ) );
												
		add_filter( 'enter_title_here' , __CLASS__ . '::change_default_title' );
				
		add_filter("manage_" . self::$post_type_name . "_posts_columns", __CLASS__ . '::manage_columns');
		
		add_action("manage_" . self::$post_type_name . "_posts_custom_column", __CLASS__ . '::manage_columns_values', 10, 2);
		
		add_filter("manage_" . self::$post_type_name . "_posts_custom_column", __CLASS__ . '::manage_columns_values', 10, 2);
						
	}

	/**
	 * Register the post type
	 */
	public static function register() {
		
		$labels = array(
			'name' => _x('Traders', 'post type general name', self::$text_domain ),
			'singular_name' => _x('Trader', 'post type singular name', self::$text_domain ),
			'all_items' => __( 'All Traders', self::$text_domain ),
			'add_new_item' => __('Add New Trader', self::$text_domain ),
			'edit_item' => __('Edit Trader', self::$text_domain ),
			'new_item' => __('New Trader', self::$text_domain ),
			'view_item' => __('View Trader', self::$text_domain ),
			'search_items' => __('Search Traders', self::$text_domain ),
			'not_found' =>  __('No traders found', self::$text_domain ),
			'not_found_in_trash' => __('No traders found in Trash', self::$text_domain ),
			'parent_item_colon' => ''
		);
		
		$args = array(
			'labels' => $labels,
			'public' => true,
			'show_ui' => true, 
			'query_var' => true,
			'has_archive' => false,
			'rewrite' => array('slug' => __('traders',self::$text_domain), 'with_front' => false ),
			'capability_type' => 'post',
			'hierarchical' => true, //allows use of wp_dropdown_pages
			'menu_position' => null,
			'taxonomies' => array(''),
			'supports' => array( 'title', 'editor', 'thumbnail','revisions', 'excerpt', 'author', 'comments' )
		); 
		
		$args = apply_filters( self::$post_type_name . '_cpt_args' , $args );

		register_post_type( self::$post_type_name , $args );
		
	}	

	/**
	 * Filter the "post updated" messages
	 *
	 * @param array $messages
	 * @return array
	 */
	public static function updated_messages( $messages ) {
		global $post;

		$messages[ self::$post_type_name ] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __('Trader updated.', self::$text_domain ), esc_url( get_permalink($post->ID) ) ),
			2 => __('Custom field updated.', self::$text_domain ),
			3 => __('Custom field deleted.', self::$text_domain ),
			4 => __('Trader updated.', self::$text_domain ),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( __('Trader restored to revision from %s', self::$text_domain ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __('Trader published.', self::$text_domain ), esc_url( get_permalink($post->ID) ) ),
			7 => __('Trader saved.', self::$text_domain ),
			8 => sprintf( __('Trader submitted.', self::$text_domain ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post->ID) ) ) ),
			9 => sprintf( __('Trader scheduled for: <strong>%1$s</strong>.', self::$text_domain ),
			  // translators: Publish box date format, see http://php.net/date
			  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post->ID) ) ),
			10 => sprintf( __('Trader draft updated.', self::$text_domain ), esc_url( add_query_arg( 'preview', 'true', get_permalink($post->ID) ) ) ),
		);

		return $messages;
	}
	
	public static function register_taxonomies() {

		$labels = array(
			'name' => _x( 'Trader Categories', 'Trader category taxonomy' ),
			'singular_name' => _x( 'Category', 'Trader category taxonomy' ),
			'search_items' =>  __( 'Search Categories' ),
			'all_items' => __( 'All Categories' ),
   			'parent_item' => __( 'Parent Category' ),
   			'parent_item_colon' => __( 'Parent Category:' ),
			'edit_item' => __( 'Edit Category' ), 
			'update_item' => __( 'Update Category' ),
			'add_new_item' => __( 'Add New Category' ),
			'new_item_name' => __( 'New Category' ),
			'menu_name' => __( 'Categories' ),
		); 	
		
		$args = array(
			'hierarchical' => true,
			'labels' => $labels,
			'show_ui' => true,
			'show_admin_column' => true,
			'query_var' => 'trader-category',
			'rewrite' => array( 'slug' => 'trader-category' )		
		);
		
		register_taxonomy( self::$post_type_name . '_category' , array( self::$post_type_name ), $args );

		
	}
	
	/**
	 * Add scripts and styles
	 *
	 * @since 1.0
	 */
	public static function add_styles_and_scripts(){
	
		wp_register_script( 'simple-traders', self::get_url( '/js/simple-traders.js', __FILE__ ) , 'jquery' , '1', true );
		
	}

	/**
	 * Conditional print some scripts in the footer when required
	 *
	 * @since 1.0
	 */
	public static function print_footer_scripts() {
	
		if ( ! self::$add_scripts )
			return;

		wp_print_scripts('simple-traders');
		
	}

	/**
	 * Add styles and scripts
	 *
	 * @since 1.0
	 */
	public static function enqueue_styles_and_scripts(){
		
		if ( !is_admin() ) {
		
			wp_enqueue_style( 'simple-traders', self::get_url( '/css/simple-traders.css', __FILE__ ) );
		
		}
		
	}

	/**
	 * Enqueues the necessary scripts and styles in the admin area
	 *
	 * @since 1.0
	 */
	public static function enqueue_admin_styles_and_scripts() {

		global $post_type;
				
		wp_register_style( 'simple-traders-admin', self::get_url( '/css/simple-traders-admin.css', __FILE__ ) , false, '1.0' );
		wp_enqueue_style( 'simple-traders-admin' );
							
		if ( self::$post_type_name == $post_type ) {

			wp_register_script( 'simple-traders-admin', self::get_url( '/js/simple-traders-admin.js', __FILE__ ) , false, '1.0', true );
			wp_enqueue_script( 'simple-traders-admin' );		
		
		}
		
	}
	
	/**
	 * Add trader meta box/es
	 *
	 * @wp-action add_meta_boxes
	 */
	public static function add_meta_box() {
	
		add_meta_box( 'trader-details', __( 'Trader Details', self::$text_domain  ), array( __CLASS__, 'do_trader_details_meta_box' ), self::$post_type_name , 'normal', 'core' );
		
	}

	/**
	 * Output the trader instructions meta box HTML
	 *
	 * @param WP_Post $object Current post object
	 * @param array $box Metabox information
	 */
	public static function do_trader_details_meta_box( $object, $box ) {
	
		wp_nonce_field( basename( __FILE__ ), 'trader-details-nonce' );
							
		 ?>

		<p>
			<label for='trader-stall'>
				<?php _e( 'Stall:', self::$text_domain ); ?>
				<input type='text' id='trader-stall' name='trader-stall' value='<?php echo esc_attr( get_post_meta( $object->ID, '_trader-stall', true ) ); ?>' />
			</label>
		</p>
		<p>
			<label for='trader-contact'>
				<?php _e( 'Contact:', self::$text_domain ); ?>
				<input type='text' id='trader-contact' name='trader-contact' value='<?php echo esc_attr( get_post_meta( $object->ID, '_trader-contact', true ) ); ?>' />
			</label>
		</p>

<?php
 
	}

	/**
	 * Save the trader metadata / options
	 *
	 * @wp-action save_post
	 * @param int $post_id The ID of the current post being saved.
	 */
	public static function save_meta( $post_id ) {

		/* Verify the nonce before proceeding. */
		if ( !isset( $_POST['trader-details-nonce'] ) || !wp_verify_nonce( $_POST['trader-details-nonce'], basename( __FILE__ ) ) )
			return $post_id;

		$meta = array(
			'trader-stall',
			'trader-contact'
		);

		foreach ( $meta as $meta_key ) {
			$new_meta_value = $_POST[$meta_key];

			/* Get the meta value of the custom field key. */
			$meta_value = get_post_meta( $post_id, '_' . $meta_key , true );

			/* If there is no new meta value but an old value exists, delete it. */
			if ( '' == $new_meta_value && $meta_value )
				delete_post_meta( $post_id, '_' . $meta_key , $meta_value );

			/* If a new meta value was added and there was no previous value, add it. */
			elseif ( $new_meta_value && '' == $meta_value )
				add_post_meta( $post_id, '_' . $meta_key , $new_meta_value, true );

			/* If the new meta value does not match the old value, update it. */
			elseif ( $new_meta_value && $new_meta_value != $meta_value )
				update_post_meta( $post_id, '_' . $meta_key , $new_meta_value );
		}
	
	}


	/**
	 * Register admin thumbnail size
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Traders
	 * @since 1.0
	 */	
	public static function register_image_sizes( ){
			
		add_image_size( 'simple_trader_admin' , '60' , '60' , true );

		add_filter( 'image_size_names_choose', array ( __CLASS__ , 'remove_image_size_options' ) );

	}

	/**
	 * Remove admin thumbnail size from the list of available sizes in the media uploader
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Traders
	 * @since 1.0
	 */	
	public static function remove_image_size_options( $sizes ){
				 	
		unset( $sizes['simple_trader_admin'] );
		
		return $sizes;
	 
	}

	/**
	 * Helper function to get the URL of a given file. 
	 * 
	 * As this plugin may be used as both a stand-alone plugin and as a submodule of 
	 * a theme, the standard WP API functions, like plugins_url() can not be used. 
	 *
	 * @since 1.0
	 * @return array $post_name => $post_content
	 */
	public static function get_url( $file ) {

		// Get the path of this file after the WP content directory
		$post_content_path = substr( dirname( str_replace('\\','/',__FILE__) ), strpos( __FILE__, basename( WP_CONTENT_DIR ) ) + strlen( basename( WP_CONTENT_DIR ) ) );

		// Return a content URL for this path & the specified file
		return content_url( $post_content_path . $file );
	}	

	/**
	 * Replaces the "Enter title here" text
	 *
	 * @author Brent Shepherd <brent@findingsimple.com>
	 * @package Simple Traders
	 * @since 1.0
	 */
	public static function change_default_title( $title ){
		$screen = get_current_screen();

		if  ( self::$post_type_name == $screen->post_type )
			$title = __( 'Enter Trader Name', self::$text_domain );

		return $title;
	}


	/**
	 * Prepend the new ID column to the columns array
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Traders
	 * @since 1.0
	 */
	public static function manage_columns($cols) {
		
		//Remove date column
		unset( $cols['date'] );

		//Add new column for trader ids
		$cols['srid'] = 'ID';
		
		return $cols;
		
	}
	
	/**
	 * Echo the ID for the new column
	 *
	 * @author Jason Conroy <jason@findingsimple.com>
	 * @package Simple Traders
	 * @since 1.0
	 */
	public static function manage_columns_values( $column_name , $id ) {
	
		if ( $column_name == 'srid' )
			echo $id;
			
	}

	/**
	 * Return Trader's contact information
	 *
	 * @author Michael Furner <michael@findingsimple.com>
	 * @package Simple Traders
	 * @since 1.0
	 */	
	public static function get_contact( $id = -1 ) {

		//if no trader ID use current
		if ( $id == -1  )
			$id = get_the_ID();
			
		return get_post_meta( $id, '_trader-contact', true );
	
	}
	
	/**
	 * Return Trader's Stall number
	 *
	 * @author Michael Furner <michael@findingsimple.com>
	 * @package Simple Traders
	 * @since 1.0
	 */	
	public static function get_stall( $id = -1 ) {

		//if no trader ID use current
		if ( $id == -1 )
			$id = get_the_ID();
			
		return get_post_meta( $id, '_trader-stall', true );
	
	}
			
}

endif;