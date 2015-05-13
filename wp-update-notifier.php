<?php
// replace "PLUGINNAME", "pluginname" and "Plugin Name" by your plugin name to prevent conflicts between plugins using this script
// replace "yourdomain.com" by your domain URL
// replace "envatousername" by your Envato username

define('PLUGINNAME_NOTIFIER_PLUGIN_NAME', 'Plugin Name'); // The plugin name
define('PLUGINNAME_NOTIFIER_PLUGIN_SHORT_NAME', 'Plugin Name'); // The plugin short name, only if needed to make the menu item fit.
define('PLUGINNAME_NOTIFIER_PLUGIN_FOLDER_NAME', 'pluginname'); // The plugin folder name
define('PLUGINNAME_NOTIFIER_PLUGIN_FILE_NAME', 'pluginname.php'); // The plugin file name
define('PLUGINNAME_NOTIFIER_PLUGIN_XML_FILE', 'http://yourdomain.com/pluginname.xml'); // The remote notifier XML file containing the latest version of the plugin and changelog
define('PLUGINNAME_PLUGIN_NOTIFIER_CACHE_INTERVAL', 86400); // The time interval for the remote XML cache in the database (86400 seconds = 24 hours)
define('PLUGINNAME_PLUGIN_NOTIFIER_CODECANYON_USERNAME', 'envatousername'); // Envato username

// Adds an update notification to the WordPress dashboard menu
function pluginname_update_plugin_notifier_menu() {
	if(function_exists('simplexml_load_string')) {
		$xml = pluginname_get_latest_plugin_version(PLUGINNAME_PLUGIN_NOTIFIER_CACHE_INTERVAL);
		$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . PLUGINNAME_NOTIFIER_PLUGIN_FOLDER_NAME . '/' . PLUGINNAME_NOTIFIER_PLUGIN_FILE_NAME);

		if((string) $xml->latest > (string) $plugin_data['Version']) {
			if(defined('PLUGINNAME_NOTIFIER_PLUGIN_SHORT_NAME')) {
				$menu_name = PLUGINNAME_NOTIFIER_PLUGIN_SHORT_NAME;
			}
			else {
				$menu_name = PLUGINNAME_NOTIFIER_PLUGIN_NAME;
			}
			add_dashboard_page(PLUGINNAME_NOTIFIER_PLUGIN_NAME . ' plugin update', $menu_name . ' <span class="update-plugins count-1"><span class="update-count">New version!</span></span>', 'administrator', 'pluginname-plugin-update-notifier', 'pluginname_update_notifier');
		}
	}	
}
add_action('admin_menu', 'pluginname_update_plugin_notifier_menu');  

// Adds an update notification to the admin bar
function pluginname_update_notifier_bar_menu() {
	if(function_exists('simplexml_load_string')) {
		global $wp_admin_bar, $wpdb;

		if(!is_super_admin() || !is_admin_bar_showing())
			return;

		$xml = pluginname_get_latest_plugin_version(PLUGINNAME_PLUGIN_NOTIFIER_CACHE_INTERVAL);
		$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . PLUGINNAME_NOTIFIER_PLUGIN_FOLDER_NAME . '/' . PLUGINNAME_NOTIFIER_PLUGIN_FILE_NAME);

		if((string)$xml->latest > (string) $plugin_data['Version']) {
			$wp_admin_bar->add_menu(array(
				'id' 	=> 'plugin_update_notifier',
				'title' => '<span>' . PLUGINNAME_NOTIFIER_PLUGIN_NAME . ' <span id="ab-updates">New version!</span></span>',
				'href' 	=> get_admin_url() . 'index.php?page=pluginname-plugin-update-notifier'
			));
		}
	}
}
add_action('admin_bar_menu', 'pluginname_update_notifier_bar_menu', 1000);

// The notifier page
function pluginname_update_notifier() { 
	$xml = pluginname_get_latest_plugin_version(PLUGINNAME_PLUGIN_NOTIFIER_CACHE_INTERVAL);
	$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . PLUGINNAME_NOTIFIER_PLUGIN_FOLDER_NAME . '/' . PLUGINNAME_NOTIFIER_PLUGIN_FILE_NAME ); ?>

	<div class="wrap">
		<h2><?php echo PLUGINNAME_NOTIFIER_PLUGIN_NAME ?> plugin update</h2>
		<div id="message" class="updated below-h2"><p><strong>There is a new version of the <?php echo PLUGINNAME_NOTIFIER_PLUGIN_NAME; ?> plugin available.</strong> You have version <b><?php echo $plugin_data['Version']; ?></b> installed. Update to version <b><?php echo $xml->latest; ?></b>.</p></div>
		
	    <h3>Update download and instructions</h3>
	    <p><strong>Please note:</strong> make a <strong>backup</strong> of the Plugin inside your WordPress installation folder <strong>/wp-content/plugins/<?php echo PLUGINNAME_NOTIFIER_PLUGIN_FOLDER_NAME; ?>/</strong></p>
	    <p>To update the Plugin, login to <a href="//codecanyon.net/?ref=<?php echo PLUGINNAME_PLUGIN_NOTIFIER_CODECANYON_USERNAME; ?>">CodeCanyon</a>, head over to your <strong>downloads</strong> section and re-download the plugin like you did when you bought it.</p>
	    <p>Extract the zip's contents, look for the extracted plugin folder, and after you have all the new files upload them using FTP to the <strong>/wp-content/plugins/<?php echo PLUGINNAME_NOTIFIER_PLUGIN_FOLDER_NAME; ?>/</strong> folder overwriting the old ones (this is why it's important to backup any changes you've made to the plugin files).</p>
	    <p>If you didn't make any changes to the plugin files, you are free to overwrite them with the new ones without the risk of losing any plugins settings, and backwards compatibility is guaranteed.</p>

		<hr>
	    <h3>Changelog</h3>
	    <?php echo $xml->changelog; ?>
	</div>
<?php } 

// Get the remote XML file contents and return its data (Version and Changelog)
// Uses the cached version if available and inside the time interval defined
function pluginname_get_latest_plugin_version($interval) {
	$notifier_file_url = PLUGINNAME_NOTIFIER_PLUGIN_XML_FILE;	
	$db_cache_field = 'notifier-cache';
	$db_cache_field_last_updated = 'notifier-cache-last-updated';
	$last = get_option($db_cache_field_last_updated);
	$now = time();

	// check the cache
	if(!$last || (($now - $last) > $interval)) {
		// cache doesn't exist, or is old, so refresh it
		if(function_exists('curl_init')) {
			$ch = curl_init($notifier_file_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			$cache = curl_exec($ch);
			curl_close($ch);
		}
		else {
			$cache = file_get_contents($notifier_file_url);
		}

		if($cache) {
			update_option($db_cache_field, $cache);
			update_option($db_cache_field_last_updated, time());
		} 
		$notifier_data = get_option($db_cache_field);
	}
	else {
		// cache file is fresh enough, so read from it
		$notifier_data = get_option($db_cache_field);
	}

	// Let's see if the $xml data was returned as we expected it to.
	// If it didn't, use the default 1.0 as the latest version so that we don't have problems when the remote server hosting the XML file is down
	if(strpos((string) $notifier_data, '<notifier>') === false) {
		$notifier_data = '<?xml version="1.0" encoding="UTF-8"?><notifier><latest>1.0</latest><changelog></changelog></notifier>';
	}

	// Load the remote XML data into a variable and return it
	$xml = simplexml_load_string($notifier_data);

	return $xml;
}
?>
