<?php

namespace Tofandel;


use Tofandel\Core\Objects\WP_Plugin;
use Tofandel\Medias\WPP_Media;

require_once __DIR__ . '/admin/tgmpa-config.php';

require_once __DIR__.'/vendor/autoload.php';

if (!class_exists('Tofandel\WPlusPlusCore'))
	return;

/**
 * Plugin Name: W++ Medias
 * Plugin URI: https://github.com/Tofandel/wplusplus-medias/
 * Description: W++ medias allows to create user-friendly hardlinks for medias so that the link doesn't change when the media is updated
 * Version: 1.1
 * Author: Adrien Foulon <tofandel@tukan.hu>
 * Author URI: https://tukan.fr/a-propos/#adrien-foulon
 * Text Domain: wplusplusmedias
 * Domain Path: /languages/
 * Download Url: https://github.com/Tofandel/wplusplus-medias/
 * WC tested up to: 4.8
 */
class WPlusPlusMedias extends WP_Plugin {

	/**
	 * Add the tables and settings and any plugin variable specifics here
	 *
	 * @return void
	 * @throws \ReflectionException
	 */
	public function definitions() {
		WPP_Media::__init__();
	}

	/**
	 * Add actions and filters here
	 */
	public function actionsAndFilters() {
	}

	/**
	 * Called function after a plugin update
	 * Can be used if options needs to be added or if previous database entries need to be modified
	 */
	protected function upgrade( $last_version ) {
	}

	/**
	 * Add redux framework menus, sub-menus and settings page in this function
	 */
	public function reduxConfig() {
	}
}

global $WPlusPlusMedias;

try {
	$WPlusPlusMedias = new WplusPlusMedias();
} catch ( \Exception $e ) {
	echo $e->getMessage();
}