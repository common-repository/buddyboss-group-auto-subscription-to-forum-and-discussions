<?php
/*
Plugin Name: Buddyboss Group Auto Subscription To Forum and Discussions
Plugin URI:  https://wordpress.org/buddyboss-group-auto-subscription-to-forum-and-discussions
Description: Auto subscribe to forum and all its discussions after joining a group.
Version:     1.1.0
Author:      John Albert Catama
Author URI:  https://github.com/jcatama
License:     GPL2
 
Buddyboss Group Auto Subscription To Forum and Discussions is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Buddyboss Group Auto Subscription To Forum and Discussions is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Buddyboss Group Auto Subscription To Forum and Discussions. If not, see LICENSE.md.

== Features ==

1. Automatically subscribe user to forum & all discussions after joining the Group

2. Automatically subscribe user to new discussions

3. Unsubscribe to forum & discussions when user leave the Group

4. Do not auto subscribe users who opt-out from a specific discussion

5. Compatible with Buddyboss + Learndash

Supported version:
PHP 7.2+
Wordpress 5.3+
BuddyBoss Platform 1.5.8+
*/

define('BBGSG_VERSION', '1.1.0');

/**
 * Check for BuddyBoss dependency
 */
register_activation_hook(__FILE__, 'bbsgs_activate');
function bbsgs_activate() {
  $plugin = plugin_basename(__FILE__);
  if(!is_plugin_active('buddyboss-platform/bp-loader.php') and current_user_can('activate_plugins')):
    wp_die('Sorry, but this plugin requires the BuddyBoss Platform Plugin to be installed and active. <br>
    <a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
    deactivate_plugins($plugin);
  endif;
}

/**
 * Add setting page link
 */
add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'bbsgs_plugin_page_settings_link');
function bbsgs_plugin_page_settings_link( $links ) {
	$links[] = '<a href="' .
		admin_url( 'options-general.php?page=bbsgs' ) .
		'">' . __('Settings') . '</a>';
	return $links;
}

/**
 * Include admin setting for plugin dashboard
 */
require_once plugin_dir_path(__FILE__) . 'admin/bb-group-subscription-setting.php';

/**
 * Include admin hooks for Group subscription
 */
require_once plugin_dir_path(__FILE__) . 'admin/bb-group-subscription-admin.php';