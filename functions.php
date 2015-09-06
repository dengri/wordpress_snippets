<?php

/*-----------------------------------------------------------
 * Enque scripts and styles
 *---------------------------------------------------------*/

function javdeluxe_enqueue_theme_styles(){
	wp_enqueue_style( 'bootstrap_css', get_template_directory_uri() . '/css/bootstrap.min.css' );
	wp_enqueue_style( 'style', get_template_directory_uri() . '/style.css' );
}

function javdeluxe_enqueue_theme_scripts(){
	wp_enqueue_script( 'bootstrap_js', get_template_directory_uri() . '/js/bootstrap.min.js', array('jquery'), '', true );
}

add_action( 'wp_enqueue_scripts', 'javdeluxe_enqueue_theme_styles' );
add_action( 'wp_enqueue_scripts', 'javdeluxe_enqueue_theme_scripts' );





/*------------------------------------------------------------
 * Turn on post thumbnails
 * and custom menus
 * ---------------------------------------------------------*/
add_theme_support('post-thumbnails');
add_theme_support('menus');




/*------------------------------------------------------------
 * Register theme menus
 * ---------------------------------------------------------*/
function register_theme_menus(){
	register_nav_menus(
		array(
			'main-menu' => 'Top menu of the theme',
		)	
	);
}

add_action('init', 'register_theme_menus');




/*------------------------------------------------------------
 *  Register Custom Navigation Walker to Bootstrap WP menu
 *----------------------------------------------------------*/
require_once('navwalker/wp_bootstrap_navwalker.php');




/*------------------------------------------------------------
 * Change excerpt length
 * ---------------------------------------------------------*/
function change_excerpt_length($length){
	return 10;
}

add_filter('excerpt_length', 'change_excerpt_length');





/*-----------------------------------------------------------
 * Register custom post type
 * --------------------------------------------------------*/
function register_movies_post_type(){

	$labels = array(
				'name'               =>  'Movies',
				'singular_name'      =>  'Movie',
				'menu_name'          =>  'Movies',
				'name_admin_bar'     =>  'Movies',
				'add_new'            =>  'Add New',
				'add_new_item'       =>  'Add New Movie',
				'new_item'           =>  'New Movie',
				'edit_item'          =>  'Edit Movie',
				'view_item'          =>  'View Movie',
				'all_items'          =>  'All Movies',
				'search_items'       =>  'Search Movies',
				'parent_item_colon'  =>  'Parent Movies:',
				'not_found'          =>  'No movies found.',
				'not_found_in_trash' =>  'No movies found in Trash.'
		);

	$args = array(
				'labels'             => $labels,
        'description'        => 'JAV Movies',
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => 'download-jav-movies' ),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
			);

	register_post_type( 'movies', $args );
}

add_action( 'init', 'register_movies_post_type' );





/*------------------------------------------------------------------
 *
 *     Rewriting permalinks
 *
 * ----------------------------------------------------------------*/
function my_rewrite_flush(){

	register_movies_post_type();
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'my_rewrite_flush');





/*===================================================================
 *
 * Adding test custom fields
 *
 * ==================================================================*/

/*-------------------------------------------------------------------
 * Registering metabox
 * -----------------------------------------------------------------*/
function cd_metabox_add(){
	add_meta_box( 'my-meta-box-id', 'My Test Meta Box', 'cd_meta_box_cb', 'post', 'normal', 'high' );
}



/*--------------------------------------------------------------------
 * Adding HTML for this metabox
 * ------------------------------------------------------------------*/
function cd_meta_box_cb( $post ){
xdebug_break();	
	//Getting current post/page metainformation
	$values = get_post_custom($post->ID);	

	//Sanitizing recieved values
	$text = isset( $values['my_meta_box_text'] ) ? esc_attr( $values['my_meta_box_text'][0] ) : "";
	$selected = isset( $values['my_meta_box_select'] ) ? esc_attr( $values['my_meta_box_select'][0] ) : "";
	$check = isset( $values['my_meta_box_check'] ) ? esc_attr( $values['my_meta_box_check'][0] ) : "";
	xdebug_break();
	//Outputs 'nonce'  hidden field 
	wp_nonce_field('my_meta_box_nonce', 'meta_box_nonce');
?>

<!--Outputs HTML for the metabox form-->
<p>
	<label for='my_meta_box_text'>Text Label</label>
	<input type='text' name='my_meta_box_text' id='my_meta_box_text' value='<?php echo $text; ?>' />
</p>

	<p>
		<label for='my_meta_box_select'>Color</label>
		<select name='my_meta_box_select' id='my_meta_box_select'>
			<option value='red' <?php selected( $selected, 'red' ); ?>>Red</option>
			<option value='blue' <?php selected( $selected, 'blue' ); ?>>Blue</option>
		</select>
	</p>

	<input type='checkbox' id='my_meta_box_check' name='my_meta_box_check' <?php checked( $check, 'on'); ?>>
	<label for='my_meta_box_check'>Do not check this!</label> 
<?php

}//End of function cd_mete_box_cb( $post )

add_action( 'add_meta_boxes', 'cd_metabox_add');




/*--------------------------------------------------------------
 *
 * Cleaning and Writing Data to DB
 *
 * ------------------------------------------------------------*/

function cd_metabox_save( $post_id ){
	
	//Checking if this is NOT an autosave
	if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;


	//Verifying 'nonce' hidden field
	if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'my_meta_box_nonce' ) ) return ;

	if( !current_user_can( 'edit_post' ) ) return;
	
	//Array of allowed html tags for wp_kses() function
	$allowed = array( 'a' => array( 'href' => array() ) );


	/*-------------------------------------------------*
	 * Updating database with new custom fields values *
	 *-------------------------------------------------*/
	if(isset($_POST['my_meta_box_text']))
		update_post_meta( $post_id, 'my_meta_box_text', wp_kses( $_POST['my_meta_box_text'], $allowed ) );

	if(isset($_POST['my_meta_box_select']))
		update_post_meta( $post_id, 'my_meta_box_select', esc_attr( $_POST['my_meta_box_select'] ) );

	$checked = isset($_POST['my_meta_box_check']) ? $_POST['my_meta_box_check'] : '';
	update_post_meta( $post_id, 'my_meta_box_check', $checked );
}

add_action( 'save_post', 'cd_metabox_save' );
