<?php
/**
 * 后台设置页。
 *
 * @package fuckrss
 */

defined( 'ABSPATH' ) || exit;

class Fuckrss_Admin {

	const PAGE  = 'fuckrss';
	const GROUP = 'fuckrss';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( FUCKRSS_FILE ), array( $this, 'action_links' ) );
	}

	public function menu() {
		add_options_page(
			'FuckRSS',
			'FuckRSS',
			'manage_options',
			self::PAGE,
			array( $this, 'render_page' )
		);
	}

	public function settings() {
		register_setting(
			self::GROUP,
			Fuckrss_Options::KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'Fuckrss_Options', 'sanitize' ),
				'default'           => Fuckrss_Options::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	public function assets( $hook ) {
		if ( 'settings_page_' . self::PAGE !== $hook ) {
			return;
		}

		wp_enqueue_style( 'fuckrss-admin', FUCKRSS_URL . 'assets/css/admin.css', array(), FUCKRSS_VERSION );
		wp_enqueue_script( 'fuckrss-admin', FUCKRSS_URL . 'assets/js/admin.js', array(), FUCKRSS_VERSION, true );
	}

	public function action_links( $links ) {
		$url = admin_url( 'options-general.php?page=' . self::PAGE );

		array_unshift( $links, '<a href="' . esc_url( $url ) . '">设置</a>' );

		return $links;
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$opts  = Fuckrss_Options::all();
		$rules = $opts['rules'];
		?>
		<div class="wrap fuckrss-wrap">
			<h1 class="fuckrss-title">FuckRSS</h1>

			<form action="options.php" method="post" class="fuckrss-form">
				<?php settings_fields( self::GROUP ); ?>

				<section class="fuckrss-card">
					<h2 class="fuckrss-card-title">总开关</h2>

					<label class="fuckrss-check" for="fuckrss-enabled">
						<input type="checkbox" id="fuckrss-enabled" name="fuckrss_settings[enabled]" value="1" <?php checked( 1, (int) $opts['enabled'] ); ?> />
						启用 RSS 加料
					</label>
					<p class="fuckrss-help">仅对 Feed 请求生效，关闭后按原文输出。</p>
				</section>

				<section class="fuckrss-card">
					<h2 class="fuckrss-card-title">UA 规则</h2>

					<div class="fuckrss-table-scroll">
						<table class="fuckrss-rules">
							<thead>
								<tr>
									<th scope="col" class="fuckrss-col-ua">UA 关键词</th>
									<th scope="col">摘要加料句</th>
									<th scope="col" class="fuckrss-col-title">加料标题</th>
									<th scope="col" class="fuckrss-col-act"><span class="screen-reader-text">操作</span></th>
								</tr>
							</thead>
							<tbody id="fuckrss-rules-body">
								<?php
								if ( empty( $rules ) ) {
									$this->rule_row( 'r0' );
								} else {
									foreach ( array_values( $rules ) as $index => $rule ) {
										$this->rule_row(
											'r' . $index,
											(string) ( $rule['keyword'] ?? '' ),
											(string) ( $rule['sentence'] ?? '' ),
											(string) ( $rule['title'] ?? '' )
										);
									}
								}
								?>
							</tbody>
						</table>
					</div>

					<button type="button" class="fuckrss-btn fuckrss-btn-ghost" id="fuckrss-add-rule">添加规则</button>

					<script type="text/template" id="fuckrss-rule-tpl"><?php $this->rule_row( '__i__' ); ?></script>
				</section>

				<div class="fuckrss-actions">
					<input type="submit" name="submit" id="submit" class="fuckrss-btn fuckrss-btn-primary" value="保存设置" />
				</div>
			</form>
		</div>
		<?php
	}

	private function rule_row( $token, $keyword = '', $sentence = '', $title = '' ) {
		$name = 'fuckrss_settings[rules][' . $token . ']';
		?>
		<tr class="fuckrss-rule">
			<td>
				<input type="text" class="fuckrss-input" name="<?php echo esc_attr( $name ); ?>[keyword]" value="<?php echo esc_attr( $keyword ); ?>" placeholder="Feedspot" />
				<p class="fuckrss-help">包含即命中，不区分大小写。</p>
			</td>
			<td>
				<textarea rows="2" class="fuckrss-input" name="<?php echo esc_attr( $name ); ?>[sentence]" placeholder="如需查看完整内容，请访问原文页面。"><?php echo esc_textarea( $sentence ); ?></textarea>
				<p class="fuckrss-help">插入到每条摘要的最后一句之前。</p>
			</td>
			<td>
				<input type="text" class="fuckrss-input" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $title ); ?>" />
				<p class="fuckrss-help">替换 Feed 最后一篇的标题。</p>
			</td>
			<td>
				<button type="button" class="fuckrss-link fuckrss-remove-rule">删除</button>
			</td>
		</tr>
		<?php
	}
}
