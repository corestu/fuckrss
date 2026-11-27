<?php
/**
 * 配置读写与规则归一。
 *
 * @package fuckrss
 */

defined( 'ABSPATH' ) || exit;

class Fuckrss_Options {

	const KEY = 'fuckrss_settings';

	public static function defaults() {
		return array(
			'enabled' => 1,
			'rules'   => array(),
		);
	}

	public static function all() {
		$stored = get_option( self::KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$opts          = wp_parse_args( $stored, self::defaults() );
		$opts['rules'] = self::normalize_rules( $opts['rules'] );

		return $opts;
	}

	public static function get( $key, $fallback = null ) {
		$all = self::all();

		return array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
	}

	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();

		$clean            = array();
		$clean['enabled'] = empty( $input['enabled'] ) ? 0 : 1;

		$rules = array();
		$seen  = array();

		foreach ( (array) ( $input['rules'] ?? array() ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$keyword = sanitize_text_field( (string) ( $item['keyword'] ?? '' ) );
			if ( '' === $keyword ) {
				continue;
			}

			$key = strtolower( $keyword );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;

			$rules[] = array(
				'keyword'  => $keyword,
				'sentence' => sanitize_textarea_field( (string) ( $item['sentence'] ?? '' ) ),
				'title'    => sanitize_text_field( (string) ( $item['title'] ?? '' ) ),
			);
		}

		$clean['rules'] = $rules;

		return $clean;
	}

	public static function install() {
		add_option( self::KEY, self::defaults() );
	}

	private static function normalize_rules( $raw ) {
		$rules = array();

		foreach ( (array) $raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$keyword = trim( (string) ( $item['keyword'] ?? '' ) );
			if ( '' === $keyword ) {
				continue;
			}

			$rules[] = array(
				'keyword'  => $keyword,
				'sentence' => trim( (string) ( $item['sentence'] ?? '' ) ),
				'title'    => trim( (string) ( $item['title'] ?? '' ) ),
			);
		}

		return $rules;
	}
}
