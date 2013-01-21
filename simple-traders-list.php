<?php
/**
 * Simple Traders List Class
 * 
 * @package Simple Traders
 * @subpackage Widget
 * @version 1.0
 * @author Michael Furner <jason@findingsimple.com>
 * @copyright Copyright (c) 2010 - 2012, Finding Simple
 * @link http://expression.edu/
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

class Widget_Traders_List extends WP_Widget {

	function Widget_Traders_List() {
	
		$widget_ops = array('classname' => 'traders-list', 'description' => __('Displays a list of traders under their respective categories.'));
		
		$control_ops = array('width' => 400, 'height' => 350);
		
		$this->WP_Widget('traders-list', __('Traders List'), $widget_ops, $control_ops);
		
		add_action('wp_print_scripts', array($this,'trader_scripts'));
		
	} // end function Widget_Traders_List

	function widget( $args, $instance ) {
		
		extract($args);
		
		$title = apply_filters('widget_title', empty($instance['title']) ? '' : $instance['title']);
	
		echo $before_widget;
		
		if ( !empty( $title ) )
			echo $before_title . $title . $after_title; 
		
		echo '<div class="traders-list-wrapper">';	

		$trader_types = get_terms('simple_trader_category', array('orderby'=>'id'));
		
		foreach($trader_types as $type) {
			
			echo '<h4 style="background:#'.$type->description.';">'.$type->name.'</h4>';
			
			$traders = get_posts(array('post_type' => 'simple_trader', 'trader-category' => $type->slug, 'posts_per_page' => -1));
			
			if($traders) {
				
				if(has_term($type->name,'simple_trader_category'))
					echo '<ul class="current-type">';
				else
					echo '<ul>';
				
				foreach($traders as $trader) {
					
					if($trader->ID==get_the_ID())
						echo '<li class="current-trader">';
					else
						echo '<li>';
											
					echo '<a href="'.get_permalink($trader->ID).'">'.$trader->post_title.'</a>';
					
					echo '</li>';
				}
				
				echo '</ul>';
				
			}
			
		}
		
		echo '</div>';
		
		echo $after_widget;
		
	} // end function widget

	function update( $new_instance, $old_instance ) {
	
		$instance = $old_instance;
		
		$instance['title'] = strip_tags($new_instance['title']);
		
		return $instance;
		
	} //end function update

	function form( $instance ) {
	
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'caption' => '', 'video-id' => '', 'numberposts' => '' ) );
		
		$title = strip_tags($instance['title']);
		
		?>		
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>
		
		
		<?php
		
	} // end function form
	
	function trader_scripts() {
	
		if ( is_active_widget( false, false, $this->id_base, true ) ) {
 			wp_enqueue_script( 'simple-traders' );
		}
		
	}
	

	
}

// register Widget_Traders_List widget
add_action('widgets_init', create_function('', 'return register_widget("Widget_Traders_List");'));



