<?php
/*
Plugin Name: Steam Widget
Plugin URI: http://wordpress.org/extend/plugins/steam-widget/
Description: Display Steam Community profile in the sidebar of your blog
Version: 1.0
Author: Hew Sutton
Author URI: http://burningcubicle.com/
*/

/**
 * This file contains a PHP class written by
 * Rob McFadzean for utilizing the Steam API
 */
require( dirname( __FILE__ ) . '/SteamAPI.class.php' );

class Steam_Widget extends WP_Widget {
	function Steam_Widget() {
                $widget_ops = array( 'classname' => 'widget_steam', 'description' => __( "Display your Steam Community profile") );
                $this->WP_Widget( 'steam', __('Steam'), $widget_ops );
	}

	function form( $instance ) {
		echo '<p><label for="' . $this->get_field_id('title') . '">' . __('Title:') . '
		<input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $instance['title'] . '" /></label></p>';

		echo '<p><label for="' . $this->get_field_id('steamid') . '">' . __('Steam ID:') . '
		<input class="widefat" id="' . $this->get_field_id('steamid') . '" name="' . $this->get_field_name('steamid') . '" type="text" value="' . $instance['steamid'] . '" /></label></p>';

	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['steamid'] = strip_tags( $new_instance['steamid'] );

		return $instance;
	}

	function widget( $args, $instance ) {
		extract( $args );

		/*
		 * create SteamAPI object, retreive specified profile, and populate games list
		 */
		$steam = new SteamAPI( $instance['steamid'] );
		$steam->retrieveProfile();
		$games = $steam->getGames();

		/*
		 * sort the games list by most play time
		 */
		usort( $games, array( $this, 'gamecmp' ) );
		$games = array_reverse( $games );

		/*
		 * check to see if user entered a desired title equal to their Steam Friendly Name
		 * if so, set the actual title to be that friendly name (hyperlinked)
		 * if not, set the actual title to the entered value
		 *if no entered value by user, set a default of "Steam Profile"
		 */
		if ( $instance['title'] == $steam->friendlyName )
			$actual_title = '<a href="'. $steam->baseURL() . '" target="_blank">' . $steam->friendlyName . '</a>';
		elseif ( $instance['title'] )
			$actual_title = $instance['title'];
		else
			$actual_title = 'Steam Profile';

		echo $before_widget;

		echo $before_title . $actual_title . $after_title;

		/*
		 * create top of Steam Widget (icon, buttons, friendly name)
		 */
		echo '<div id="steam-top"><p><a href="'. $steam->baseURL() .'" target="_blank"><img style="float:left;padding:0px 8px;" src="' . $steam->avatarMedium . '"></a>';
		echo '<a href="steam://friends/add/'. $steam->getSteamID64() .'"><img style="padding:1px 0px;" src="'. WP_PLUGIN_URL . '/steam-widget/img/add.png" title="Befriend User" /></a><br>';

		/*
		 * if user is online, display button to message user
		 */
		if ( $steam->stateMessage == 'Online' )
			echo '<a href="steam://friends/message/'. $steam->getSteamID64() .'"><img style="padding:1px 0px;" src="'. WP_PLUGIN_URL . '/steam-widget/img/msgme.png" title="Send Message" /></a></p>';
		echo '<div style="clear:both;padding:3px 0px"> </div>';

		if ( $instance['title'] != $steam->friendlyName )
			echo '<a href="'. $steam->baseURL() .'" target="_blank"><h2>'. $steam->friendlyName .'</h2></a></div>';

		/*
		 * create middle of Steam Widget (state, join date, recently played)
		 */
		echo '<div id="steam-mid">' . $steam->stateMessage . '<br>';
		echo 'Member since '.$steam->memberSince . '<br>';
		echo $steam->hoursPlayed2Wk .'h played in last 2wks';
		echo '</div><br>';

		/*
		 * create bottom of Steam widget (top 5 played games list)
		 */
		echo '<div id="steam-bottom">';
		echo $steam->getTotalGames() . ' games; top five by playtime:<br>';
		for( $i = 0; $i < 5; $i++ ) {
			if( $games[$i]['hoursOnRecord'] != 0 ) {
				echo '<a href="'. $games[$i]['storeLink'] .'" title="Link to '. $games[$i]['name'] .' in the store"><img src="'. $games[$i]['logo'] .'" /></a><br />';
				echo 'Playtime: ' . $games[$i]['hoursOnRecord'] . ' h<br />';
			}
			else {
				echo '<br />';
			}
		}

		echo '</div>';
		echo $after_widget;
	}

	function gamecmp( $a, $b )
	{
		if( $a['hoursOnRecord'] ==  $b['hoursOnRecord'] ) {
			return 0;
		}
		else {
  			return ( $a['hoursOnRecord'] < $b['hoursOnRecord'] ) ? -1 : 1;
		}
	}
}


add_action( 'widgets_init', 'steam_widget_init' );
function steam_widget_init() {
        register_widget( 'Steam_Widget' );
}

?>
