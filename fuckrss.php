<?php
/**
 * Plugin Name:       FuckRSS
 * Description:       按 User-Agent 关键词识别 RSS 抓取者，命中后改写 Feed 摘要与最后一篇标题。
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            FuckRSS
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;

define( 'FUCKRSS_VERSION', '1.0.0' );
define( 'FUCKRSS_FILE', __FILE__ );
define( 'FUCKRSS_DIR', plugin_dir_path( __FILE__ ) );
define( 'FUCKRSS_URL', plugin_dir_url( __FILE__ ) );

require_once FUCKRSS_DIR . 'includes/class-fuckrss-options.php';
require_once FUCKRSS_DIR . 'includes/class-fuckrss-tamper.php';
require_once FUCKRSS_DIR . 'includes/class-fuckrss-guard.php';
require_once FUCKRSS_DIR . 'includes/class-fuckrss-admin.php';

register_activation_hook( __FILE__, array( 'Fuckrss_Options', 'install' ) );

add_action( 'plugins_loaded', 'fuckrss_bootstrap' );

function fuckrss_bootstrap() {
	Fuckrss_Guard::instance()->register();

	if ( is_admin() ) {
		Fuckrss_Admin::instance()->register();
	}
}
