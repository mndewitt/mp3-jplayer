<?php 
/* 
Plugin Name: MP3-jPlayer
Plugin URI: http://mp3-jplayer.com
Description: Easy, Flexible Audio for WordPress. 
Version: 2.0
Author: Simon Ward
Author URI: http://www.sjward.org
License: GPL2
  	
	Copyright 2014 Simon Ward
	
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

$mp3j_path = dirname(__FILE__);
include_once( $mp3j_path . '/main.php');
include_once( $mp3j_path . '/frontend.php'); //extends main

if ( class_exists("MP3j_Front") ) {
	$mp3_fox = new MP3j_Front();
}

if ( isset($mp3_fox) )
{
	include_once( $mp3j_path . '/widget-ui.php'); //ui widget (playlister)
	include_once( $mp3j_path . '/widget-sh.php'); //shortcodes Widget
	
	$ops = $mp3_fox->theSettings;
	if ( is_admin() )
	{
		include_once( $mp3j_path . '/admin-settings.php'); //settings page
		//include_once( $mp3j_path . '/admin-help.php');
		include_once( $mp3j_path . '/admin-colours.php');
		
		function mp3j_adminpage()
		{
			/*
			//extension page - $p (arr)
			--
				[parent]-> str
				[title]-> str
				[menuName]-> str
				[capability]-> str
				[slug]-> str
				[drawFunction]-> str
				['scriptsFunction'] -> false/str
			*/
			
			//add menu pages	
			global $mp3_fox;
			
			//Settings page
			$pluginpage = add_menu_page( 'Settings | MP3 jPlayer', 'MP3 jPlayer', 'manage_options', 'mp3-jplayer', 'mp3j_print_admin_page' ); //root				
			add_submenu_page( 'mp3-jplayer', 'Settings | MP3 jPlayer', 'Settings', 'manage_options', 'mp3-jplayer', 'mp3j_print_admin_page' ); //root in sub
			add_action( 'admin_head-'. $pluginpage, array(&$mp3_fox, 'mp3j_admin_header') ); 
			
			//Design page
			$subm_colours = add_submenu_page( 'mp3-jplayer', 'Design | MP3 jPlayer', 'Design', 'manage_options', 'mp3-jplayer-colours', 'mp3j_print_colours_page' );
			add_action( 'admin_head-'. $subm_colours, array(&$mp3_fox, 'mp3j_admin_colours_header') ); 
			$mp3_fox->dbug['str'] .= 'colours handle: ' . $subm_colours;
			
			//Extension pages
			foreach ( $mp3_fox->EXTpages as $p ) {
				$submenu = add_submenu_page( $p['parent'], $p['title'], $p['menuName'], $p['capability'], $p['slug'], $p['drawFunction'] );
				if ( $p['scriptsFunction'] !== false ) {
					add_action( 'admin_head-'. $submenu, $p['scriptsFunction'] );
				}
			}
			
			//Help page
			//$subm_help = add_submenu_page( 'mp3-jplayer', 'Help | MP3 jPlayer', 'Help', 'manage_options', 'mp3-jplayer-help', 'mp3j_print_help_page' );
			//add_action( 'admin_head-'. $subm_help, array(&$mp3_fox, 'mp3j_admin_header') );
			
			add_filter( 'plugin_action_links', 'mp3j_plugin_links', 10, 2 );
		}

		function mp3j_plugin_links( $links, $file )
		{ 
			//add a settings link on plugins page 
			if( $file == 'mp3-jplayer/mp3jplayer.php' ) {
				$settings_link = '<a href="admin.php?page=mp3-jplayer">'.__('Settings').'</a>';
				array_unshift( $links, $settings_link );
			}
			return $links;
		}
		
		add_action('deactivate_mp3-jplayer/mp3jplayer.php',  array(&$mp3_fox, 'uninitFox'));
		add_action('admin_menu', 'mp3j_adminpage', 100);
		
		//add_action( 'admin_enqueue_scripts', array(&$mp3_fox, 'adminPostEditHead') );
		
	}
	 
	
	//template functions
	function mp3j_addscripts( $style = "" ) {
		do_action('mp3j_addscripts', $style);
	}

	function mp3j_put( $shortcodes = "" ) {
		do_action( 'mp3j_put', $shortcodes );
	}

	function mp3j_debug( $display = "" ) {
		do_action('mp3j_debug', $display);
	}
	
	function mp3j_grab_library( $format = "" ) { 
		$lib = apply_filters('mp3j_grab_library', '' );
		return $lib;
	}
	
	
	//Widgets
	function mp3jplayer_widget_init() {
		register_widget( 'MP3_jPlayer' );
	}
	add_action( 'widgets_init', 'mp3jplayer_widget_init' ); 
	
	function mp3jshortcodes_widget_init() { 
		register_widget( 'MP3j_single' ); //silly name but can't change it now!
	}
	add_action( 'widgets_init', 'mp3jshortcodes_widget_init' );
	
	
	//Shortcodes
	add_shortcode('mp3t', array(&$mp3_fox, 'inline_play_handler'));
	add_shortcode('mp3j', array(&$mp3_fox, 'inline_play_graphic'));
	add_shortcode('mp3-jplayer', array(&$mp3_fox, 'primary_player'));
	
	if ( shortcode_exists('popout') )	{
		remove_shortcode('popout');
	}
	add_shortcode('popout', array(&$mp3_fox, 'popout_link_player'));
	add_shortcode('mp3-popout', array(&$mp3_fox, 'popout_link_player'));
	
	if ( $ops['replace_WP_playlist'] === 'true' && ! is_admin() ) {
		if ( shortcode_exists('playlist') )	{
			remove_shortcode('playlist');
		}
		add_shortcode('playlist', array(&$mp3_fox, 'replacePlaylistShortcode'));
	}
	
	if ( ! is_admin() && ($ops['replace_WP_audio'] === 'true' || $ops['replace_WP_embedded'] === 'true' || $ops['replace_WP_attached'] === 'true') )	{
		if ( shortcode_exists('audio') ) {
			remove_shortcode('audio');
		}
		add_shortcode('audio', array(&$mp3_fox, 'replaceAudioShortcode'));
	}
	
	
	//Template hooks
	//scripts
	/*
	* should use this conditional but it means that as page loads up players initially appear unstyled (aren't styled until
	* last minute) TODO:enqueue style in header when poss.
	* always run both hooks for the mo.
	*/
	//$WPversion = substr( get_bloginfo('version'), 0, 3);
	//if ( $WPversion < 3.3 ) {
		add_action('wp_head', array(&$mp3_fox, 'header_scripts_handler'), 2);	//Support for WP versions below 3.3
	//}
	add_action('wp_footer', array(&$mp3_fox, 'checkAddScripts'), 1); 		//Final chance to enqueue, process this action early (priority < 20).
	add_action('wp_footer', array(&$mp3_fox, 'footercode_handler'), 200); 	//Add any inline js, process this action late (enqueues fire at priority 20).
	
	//options
	if ( $ops['make_player_from_link'] == "true" ) {
		add_filter('the_content', array(&$mp3_fox, 'replace_links'), 1);
	}
	if ( $ops['run_shcode_in_excerpt'] == "true" ) {
		add_filter( 'the_excerpt', 'shortcode_unautop');
		add_filter( 'the_excerpt', 'do_shortcode');
	}
	
	//theme template actions
	add_action('mp3j_put', array(&$mp3_fox, 'template_tag_handler'), 10, 1 );
	add_action('mp3j_addscripts', array(&$mp3_fox, 'scripts_tag_handler'), 1, 1 );
	add_filter('mp3j_grab_library', array(&$mp3_fox, 'grablibrary_handler'), 10, 1 );
	add_action('mp3j_debug', array(&$mp3_fox, 'debug_info'), 10, 1 );
	
	//retired
	function mp3j_set_meta( $tracks, $captions = "", $startnum = 1 ) { } //since 1.7
	function mp3j_flag( $set = 1 ) { } //since 1.6
	function mp3j_div() { } //since 1.8
}
?>