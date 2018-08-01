<?php

namespace Tofandel;

use Tofandel\Core\Objects\WP_Plugin;
use Tofandel\Medias\WPP_Media;

require_once __DIR__ . '/plugins/tgmpa-config.php';

require_once __DIR__ . '/vendor/autoload.php';

if ( ! class_exists( WPlusPlusCore::class ) ) {
	return;
}

/**
 * Plugin Name: W++ Medias
 * Plugin URI: https://github.com/Tofandel/wplusplus-medias/
 * Description: W++ medias allows to create user-friendly hardlinks for medias so that the link doesn't change when the media is updated
 * Version: 1.3.1
 * Author: Adrien Foulon <tofandel@tukan.hu>
 * Author URI: https://tukan.fr/a-propos/#adrien-foulon
 * Text Domain: wplusplusmedias
 * Domain Path: /languages/
 * WC tested up to: 4.8
 */
class WPlusPlusMedias extends WP_Plugin {
	protected $repo_url = 'https://github.com/Tofandel/wplusplus-medias/';

	/**
	 * Add the tables and settings and any plugin variable specifics here
	 *
	 * @return void
	 */
	public function definitions() {
		$this->setSubModule( new WPP_Media( $this ) );
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

	/**
	 * Called function if a plugin is uninstalled
	 */
	protected function uninstall() {
	}
}

global $WPlusPlusMedias;

try {
	$WPlusPlusMedias = new WplusPlusMedias();
} catch ( \Exception $e ) {
	echo $e->getMessage();
}