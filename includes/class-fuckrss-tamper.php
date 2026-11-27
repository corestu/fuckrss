<?php
/**
 * 摘要加料：保留全部原句，把指定句子插到最后一句之前。
 *
 * @package fuckrss
 */

defined( 'ABSPATH' ) || exit;

class Fuckrss_Tamper {

	public static function apply( $excerpt, $sentence ) {
		$excerpt  = trim( (string) $excerpt );
		$sentence = self::finish( (string) $sentence );

		if ( '' === $sentence ) {
			return $excerpt;
		}

		if ( '' === $excerpt ) {
			return $sentence;
		}

		$parts = self::split( $excerpt );
		$last  = array_pop( $parts );

		$head = '';
		foreach ( $parts as $part ) {
			$head .= self::finish( $part );
		}

		return $head . $sentence . $last;
	}

	private static function split( $text ) {
		$parts = preg_split( '/(?<=[。！？!?])(?![」』”’）\)])|(?<=…)(?!…)|(?<=[.!?])(?=\s)/u', $text );

		if ( ! is_array( $parts ) ) {
			return array( $text );
		}

		$parts = array_values( array_filter( array_map( 'trim', $parts ), 'strlen' ) );

		return $parts ? $parts : array( $text );
	}

	/**
	 * 保证句子可自然衔接：中文补「。」，英文补「. 」。
	 */
	private static function finish( $text ) {
		$text = rtrim( (string) $text );

		if ( '' === $text ) {
			return '';
		}

		$last = mb_substr( $text, -1, 1, 'UTF-8' );
		$cjk  = self::has_cjk( $text );

		if ( false !== strpos( '。！？!?…', $last ) ) {
			return $cjk ? $text : $text . ' ';
		}

		if ( preg_match( '/[」』”’）\)]$/u', $text ) ) {
			return $text;
		}

		return $cjk ? $text . '。' : $text . '. ';
	}

	private static function has_cjk( $text ) {
		return (bool) preg_match( '/\p{Han}/u', $text );
	}
}
