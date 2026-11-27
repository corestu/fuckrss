<?php
/**
 * 卸载清理：删除配置项与相关 transient，不留残留数据。
 *
 * @package fuckrss
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

function fuckrss_uninstall_site() {
	$options = array(
		'fuckrss_settings',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	global $wpdb;

	$patterns = array(
		'_transient_fuckrss_',
		'_transient_timeout_fuckrss_',
	);

	foreach ( $patterns as $pattern ) {
		$like = $wpdb->esc_like( $pattern ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}

if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( (array) $site_ids as $site_id ) {
		switch_to_blog( (int) $site_id );
		fuckrss_uninstall_site();
		restore_current_blog();
	}
} else {
	fuckrss_uninstall_site();
}

wp_cache_flush();
