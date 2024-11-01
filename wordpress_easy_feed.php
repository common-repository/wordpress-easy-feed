<?php
/*
Plugin Name: Wordpress Easy Feed
Plugin URI: http://www.geevcookie.com/wordpress-easy-feed
Description: A widget which allows you to easily include items from your favourite feeds. Basic styling options also available.
Version: 1.1.1
Author: Zander Janse van Rensburg
Author URI: http://www.geevcookie.com
*/

// Add function that registers the widget to widget_init.
add_action( 'widgets_init', 'wpef_load_widget' );

// Register the widget.
function wpef_load_widget() {
	register_widget('wpef_widget');
}

// Widget class that handles the updating, viewing and settings form of the widget.
class wpef_widget extends WP_Widget {

	// Widget Setup.
	function wpef_widget() {
		// Set widget and control settings.
		$widget_settings = array('classname' => 'wpef-widget', 'description' => __('Widget to easily display items from a feed.', 'wpef-widget') );
		$control_settings = array('id_base' => 'wpef-widget-id');

		// Create widget using above settings.
		$this->WP_Widget( 'wpef-widget-id', __('Wordpress Easy Feed', 'wpef-widget'), $widget_settings, $control_settings );
	}

	// Display widget.
	function widget( $args, $instance ) {
		extract( $args );
		// Include wordpress' feed.php which will handle the feed queries.
		include_once(ABSPATH . WPINC . '/feed.php');
		
		// Populate variables with the values from the widget options.
		$title = apply_filters('widget_title', $instance['title'] );

		$cache_time = $instance['cache-time'];
		$type = $instance['type'];
		$special = $instance['special'];
		$amount = $instance['amount'];
		$display_time = $instance['display-time'];
		$new_window = $instance['new-window'];
		
		$wrapper_before = $instance['wrapper-before'];
		$wrapper_after = $instance['wrapper-after'];
		
		$item_before = $instance['item-before'];
		$item_after = $instance['item-after'];
		
		$custom_error = $instance['custom-error'];
		
		// Set the feed cache lifetime back to defaults.
		switch ($cache_time) {
			case '15 Minutes':
				$time = 900;
				break;
			case '30 Minutes':
				$time = 1800;
				break;
			case '1 Hour':
				$time = 3600;
				break;
			case '6 Hours':
				$time = 21600;
				break;
			case '12 Hours':
				$time = 43200;
				break;
			case '24 Hours':
				$time = 86400;
				break;
		}
		
		// Detect which feed was chosen and query the correct rss URL.
		switch ($type) {
			case 'Twitter':
				$rss = wpef_fetch_feed('http://twitter.com/statuses/user_timeline/' . $special . '.rss', $time);
				break;
			case 'Delicious':
				$rss = wpef_fetch_feed('http://feeds.delicious.com/v2/rss/'. $special, $time);
				break;
			case 'StumbleUpon':
				$rss = wpef_fetch_feed('http://rss.stumbleupon.com/user/' . $special . '/favorites', $time);
				break;
			case 'Digg':
				$rss = wpef_fetch_feed('http://digg.com/users/' . $special . '/history.rss', $time);
				break;
			case 'reddit':
				$rss = wpef_fetch_feed('http://www.reddit.com/user/' . $special . '/submitted/.rss', $time);
				break;
			case 'Technorati':
				$rss = wpef_fetch_feed('http://technorati.com/people/' . $special . '/index.xml', $time);
				break;
			case 'YouTube':
				$rss = wpef_fetch_feed('http://gdata.youtube.com/feeds/base/users/' . $special . '/uploads?alt=rss&v=2&orderby=published&client=ytapi-youtube-profile', $time);
				break;
			case 'Other':
				$rss = wpef_fetch_feed($special, $time);
				break;
		}
		
		// If there were no errors get the amount of items to display.
		if (!is_wp_error( $rss ) ) :
			$maxitems = $rss->get_item_quantity($amount);
			$rss_items = $rss->get_items(0, $maxitems); 
		endif;
		
		// Determined by theme.
		echo $before_widget;

		// Display the tile of the widget which was entered in the widget options.
		if ( $title )
			echo $before_title . $title . $after_title;

		// Wrap the results with user specified values.
		if ( $wrapper_before )
			echo htmlspecialchars_decode($wrapper_before);
		
		// Display error if no results were returned or if there was an error.
		// @since 1.1.0: Display Custom Error
		if ($maxitems == 0) : echo htmlspecialchars_decode($custom_error);
   		else :
			// Loop through results and echo each result in the form of a hyperlink.
			foreach ( $rss_items as $item ) : ?>
			
				<?php // Wrap item with user specified values. ?>
				<?php if ( $item_before )
					echo htmlspecialchars_decode($item_before); ?>
					
					<a href='<?php echo $item->get_permalink(); ?>'
					title='<?php echo 'Posted '.$item->get_date('j F Y | g:i a'); ?>' 
					<?php if ($new_window == "true") echo 'target="_blank"'; ?>>
					<?php
						// @since 1.1.0: Display normal title or title with timestamp.
						if (!$display_time) {
							echo $item->get_title();
						} else {
							echo $item->get_title() . " (" . $item->get_date() . ")";
						}
					?></a>
				
				<?php // End item wrap. ?>
				<?php if ( $item_after )
					echo htmlspecialchars_decode($item_after); ?>
				
			<?php endforeach;
		endif;

		// End results wrap.
		if ( $wrapper_after )
			echo htmlspecialchars_decode($wrapper_after);

		// Determined by theme.
		echo $after_widget;
	}

	// Set fields to update when user saves widget options.
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = htmlspecialchars( $new_instance['title'] );
		$instance['special'] = htmlspecialchars( $new_instance['special'] );
		$instance['wrapper-before'] = htmlspecialchars( $new_instance['wrapper-before'] );
		$instance['wrapper-after'] = htmlspecialchars( $new_instance['wrapper-after'] );
		$instance['item-before'] = htmlspecialchars( $new_instance['item-before'] );
		$instance['item-after'] = htmlspecialchars( $new_instance['item-after'] );
		$instance['amount'] = htmlspecialchars( $new_instance['amount'] );
		$instance['custom-error'] = htmlspecialchars( $new_instance['custom-error'] );
		
		$instance['new-window'] = $new_instance['new-window'];
		$instance['type'] = $new_instance['type'];
		$instance['cache-time'] = $new_instance['cache-time'];
		$instance['display-time'] = $new_instance['display-time'];

		return $instance;
	}

	// Display options form.
	function form( $instance ) {

		// Set some defaults.
		$defaults = array( 
				'title' => __('Wordpress Easy Feed', 'example'),
				'type' => 'Twitter',
				'special' => '',
				'amount' => 5,
				'wrapper-before' => '<ul>',
				'wrapper-after' => '</ul>',
				'item-before' => '<li>',
				'item-after' => '</li>',
				'custom-error' => 'No Items Available.',
				'cache-time' => '12 Hours',
				'display-time' => '',
				'new-window' => '' );
				
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<!-- Title Of Widget -->
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'hybrid'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>

		<!-- Feed Type -->
		<p>
			<label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e('Feed Type:', 'wpef-widget'); ?></label>
			<select id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>" class="widefat special-select" style="width:100%;">
				<option <?php if ( 'Twitter' == $instance['type'] ) echo 'selected="selected"'; ?>>Twitter</option>
				<option <?php if ( 'Delicious' == $instance['type'] ) echo 'selected="selected"'; ?>>Delicious</option>
				<option <?php if ( 'StumbleUpon' == $instance['type'] ) echo 'selected="selected"'; ?>>StumbleUpon</option>
				<option <?php if ( 'Digg' == $instance['type'] ) echo 'selected="selected"'; ?>>Digg</option>
				<option <?php if ( 'reddit' == $instance['type'] ) echo 'selected="selected"'; ?>>reddit</option>
				<option <?php if ( 'Technorati' == $instance['type'] ) echo 'selected="selected"'; ?>>Technorati</option>
				<option <?php if ( 'YouTube' == $instance['type'] ) echo 'selected="selected"'; ?>>YouTube</option>
				<option <?php if ( 'Other' == $instance['type'] ) echo 'selected="selected"'; ?>>Other</option>
			</select>
		</p>

		<!-- Username Or Feed URL If Other Was Chosen For Type -->
		<p>
			<label class="wpef-special-title" for="<?php echo $this->get_field_id( 'special' ); ?>"><?php _e('Username/Feed URL:', 'wpef-widget'); ?></label>
			<input id="<?php echo $this->get_field_id( 'special' ); ?>" name="<?php echo $this->get_field_name( 'special' ); ?>" value="<?php echo $instance['special']; ?>" style="width:100%;" />
		</p>
		
		<!-- Amount Of Results To Display -->
		<p>
			<label for="<?php echo $this->get_field_id( 'amount' ); ?>"><?php _e('Display Amount:', 'wpef-widget'); ?></label>
			<input id="<?php echo $this->get_field_id( 'amount' ); ?>" name="<?php echo $this->get_field_name( 'amount' ); ?>" value="<?php echo $instance['amount']; ?>" style="width:100%;" />
		</p>

		<!-- Start Of Wrapper For All Results -->
		<p>
			<label for="<?php echo $this->get_field_id( 'wrapper-before' ); ?>"><?php _e('Wrapper Before:', 'wpef-widget'); ?></label>
			<input id="<?php echo $this->get_field_id( 'wrapper-before' ); ?>" name="<?php echo $this->get_field_name( 'wrapper-before' ); ?>" value="<?php echo $instance['wrapper-before']; ?>" style="width:100%;" />
		</p>
		
		<!-- End Of Wrapper For All Results -->
		<p>
			<label for="<?php echo $this->get_field_id( 'wrapper-after' ); ?>"><?php _e('Wrapper After:', 'wpef-widget'); ?></label>
			<input id="<?php echo $this->get_field_id( 'wrapper-after' ); ?>" name="<?php echo $this->get_field_name( 'wrapper-after' ); ?>" value="<?php echo $instance['wrapper-after']; ?>" style="width:100%;" />
		</p>
		
		<!-- Start Of Wrapper For Result Output -->
		<p>
			<label for="<?php echo $this->get_field_id( 'item-before' ); ?>"><?php _e('Item Before:', 'wpef-widget'); ?></label>
			<input id="<?php echo $this->get_field_id( 'item-before' ); ?>" name="<?php echo $this->get_field_name( 'item-before' ); ?>" value="<?php echo $instance['item-before']; ?>" style="width:100%;" />
		</p>
		
		<!-- End Of Wrapper For Result Output -->
		<p>
			<label for="<?php echo $this->get_field_id( 'item-after' ); ?>"><?php _e('Item After:', 'wpef-widget'); ?></label>
			<input id="<?php echo $this->get_field_id( 'item-after' ); ?>" name="<?php echo $this->get_field_name( 'item-after' ); ?>" value="<?php echo $instance['item-after']; ?>" style="width:100%;" />
		</p>
		
		<!-- Custom Message If There Was An Error Or If There Are No Feeds -->
		<p>
			<label for="<?php echo $this->get_field_id( 'custom-error' ); ?>"><?php _e('Error Message:', 'wpef-widget'); ?></label>
			<input id="<?php echo $this->get_field_id( 'custom-error' ); ?>" name="<?php echo $this->get_field_name( 'custom-error' ); ?>" value="<?php echo $instance['custom-error']; ?>" style="width:100%;" />
		</p>
		
		<!-- Cache Time: Beta -->
		<p>
			<label for="<?php echo $this->get_field_id( 'cache-time' ); ?>"><?php _e('Cache Time:', 'wpef-widget'); ?></label>
			<select id="<?php echo $this->get_field_id( 'cache-time' ); ?>" name="<?php echo $this->get_field_name( 'cache-time' ); ?>" class="widefat" style="width:100%;">
				<option <?php if ( '15 Minutes' == $instance['cache-time'] ) echo 'selected="selected"'; ?>>15 Minutes</option>
				<option <?php if ( '30 Minutes' == $instance['cache-time'] ) echo 'selected="selected"'; ?>>30 Minutes</option>
				<option <?php if ( '1 Hour' == $instance['cache-time'] ) echo 'selected="selected"'; ?>>1 Hour</option>
				<option <?php if ( '6 Hours' == $instance['cache-time'] ) echo 'selected="selected"'; ?>>6 Hours</option>
				<option <?php if ( '12 Hours' == $instance['cache-time'] ) echo 'selected="selected"'; ?>>12 Hours</option>
				<option <?php if ( '24 Hours' == $instance['cache-time'] ) echo 'selected="selected"'; ?>>24 Hours</option>
			</select>
		</p>
		
		<!-- Display Time In Title -->
		<p>
			<label for="<?php echo $this->get_field_id( 'display-time' ); ?>"><?php _e('Display Time:', 'wpef-widget'); ?></label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'display-time' ); ?>" name="<?php echo $this->get_field_name( 'display-time' ); ?>" value="true" <?php if ($instance['display-time'] == "true") echo "checked"; ?> />
		</p>
		
		<!-- Open Links in New Window -->
		<p>
			<label for="<?php echo $this->get_field_id( 'new-window' ); ?>"><?php _e('Open In New Window:', 'wpef-widget'); ?></label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'new-window' ); ?>" name="<?php echo $this->get_field_name( 'new-window' ); ?>" value="true" <?php if ($instance['new-window'] == "true") echo "checked"; ?> />
		</p>
	<?php
	}
}

// Altered version of the fetch_feed function in /wp-include/feed.php.
// @since 1.1.0: Fetches feed with custom cache duration.
function wpef_fetch_feed($url, $time) {
	require_once (ABSPATH . WPINC . '/class-feed.php');

	$feed = new SimplePie();
	$feed->set_feed_url($url);
	$feed->set_cache_class('WP_Feed_Cache');
	$feed->set_file_class('WP_SimplePie_File');
	$feed->set_cache_duration(apply_filters('wp_feed_cache_transient_lifetime', $time, $url));
	do_action_ref_array( 'wp_feed_options', array( &$feed, $url ) );
	$feed->init();
	$feed->handle_content_type();

	if ( $feed->error() )
		return new WP_Error('simplepie-error', $feed->error());

	return $feed;
}
?>