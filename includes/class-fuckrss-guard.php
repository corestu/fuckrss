<?php
/**
 * Feed 请求拦截：UA 命中规则后，改摘要 / 改末篇标题。
 *
 * @package fuckrss
 */

defined( 'ABSPATH' ) || exit;

class Fuckrss_Guard {

	private static $instance = null;

	private $sentence = '';

	private $title = '';

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	public function register() {
		add_action( 'template_redirect', array( $this, 'maybe_tamper' ), 1 );
	}

	/**
	 * Feed 内容随 UA 变化，缓存层必须放弃存储，否则命中 UA 会拿到缓存的正常副本，加料全部失效。
	 * X-Accel-Expires 供 Nginx / FastCGI cache 判定，Cache-Control 供浏览器、CDN 与页面缓存判定。
	 */
	public function send_no_cache_headers() {
		if ( headers_sent() ) {
			return;
		}

		foreach ( array( 'Cache-Control', 'Pragma', 'Expires', 'X-Accel-Expires', 'Vary' ) as $name ) {
			header_remove( $name );
		}

		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
		header( 'X-Accel-Expires: 0' );
		header( 'Vary: User-Agent' );
	}

	/**
	 * template_redirect 早于 Feed 模板输出触发，此处只挂摘要与标题过滤器，
	 * link / pubDate / guid / content:encoded 等数据由 WordPress 原样输出。
	 */
	public function maybe_tamper() {
		if ( ! is_feed() || is_comment_feed() ) {
			return;
		}

		$opts = Fuckrss_Options::all();
		if ( empty( $opts['enabled'] ) ) {
			return;
		}

		// 命中判定依赖 UA，必须在 PHP 里执行，故启用后所有 Feed 请求都不允许被缓存。
		$this->send_no_cache_headers();

		$ua = $this->user_agent();
		if ( '' === $ua ) {
			return;
		}

		$rule = $this->match( $ua, $opts['rules'] );
		if ( null === $rule ) {
			return;
		}

		$this->sentence = trim( $rule['sentence'] );
		$this->title    = trim( $rule['title'] );

		if ( '' === $this->sentence && '' === $this->title ) {
			return;
		}

		if ( ! headers_sent() ) {
			header( 'X-FuckRSS: hit' );
		}

		if ( '' !== $this->sentence ) {
			add_filter( 'the_excerpt_rss', array( $this, 'filter_excerpt' ), 999 );
		}

		if ( '' !== $this->title ) {
			add_filter( 'the_title_rss', array( $this, 'filter_title' ), 999 );
		}

		do_action( 'fuckrss_hit', $ua, $rule['keyword'], (string) get_query_var( 'feed' ) );
	}

	public function filter_excerpt( $excerpt ) {
		$excerpt = (string) $excerpt;

		if ( '' === $this->sentence || false !== strpos( $excerpt, $this->sentence ) ) {
			return $excerpt;
		}

		$tampered = Fuckrss_Tamper::apply( $excerpt, $this->sentence );

		return apply_filters( 'fuckrss_excerpt', $tampered, $excerpt, $this->sentence );
	}

	/**
	 * 只替换 Feed 中最后一篇的标题，前置文章保持原样。
	 * rss2 为裸文本节点、atom 为 CDATA，故统一做实体转义，两种格式解析后一致。
	 */
	public function filter_title( $title ) {
		global $wp_query;

		if ( '' === $this->title || ! ( $wp_query instanceof WP_Query ) ) {
			return $title;
		}

		$last = (int) $wp_query->post_count - 1;
		if ( $last < 0 || (int) $wp_query->current_post !== $last ) {
			return $title;
		}

		return esc_html( (string) apply_filters( 'fuckrss_title', $this->title, $title ) );
	}

	public function user_agent() {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return '';
		}

		return trim( (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
	}

	public function match( $ua, $rules ) {
		$haystack = strtolower( $ua );
		$rules    = apply_filters( 'fuckrss_rules', $rules );

		foreach ( (array) $rules as $rule ) {
			if ( ! is_array( $rule ) || empty( $rule['keyword'] ) ) {
				continue;
			}

			$keyword = strtolower( trim( (string) $rule['keyword'] ) );
			if ( '' === $keyword ) {
				continue;
			}

			if ( false !== strpos( $haystack, $keyword ) ) {
				return array(
					'keyword'  => (string) $rule['keyword'],
					'sentence' => (string) ( $rule['sentence'] ?? '' ),
					'title'    => (string) ( $rule['title'] ?? '' ),
				);
			}
		}

		return null;
	}
}
