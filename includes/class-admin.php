<?php
/**
 * Admin-Interface
 *
 * @package SymbionEURestriction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin-Klasse
 */
class Symbion_EU_Admin {

	/**
	 * Singleton-Instanz
	 *
	 * @var Symbion_EU_Admin
	 */
	private static $instance = null;

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return Symbion_EU_Admin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Konstruktor
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'plugin_action_links_' . SYMBION_EU_BASENAME, array( $this, 'add_action_links' ) );
	}

	/**
	 * Admin-Menü hinzufügen
	 */
	public function add_menu() {
		add_menu_page(
			__( 'Symbion EU Restriction', 'symbion-eu-restriction' ),
			__( 'EU Restriction', 'symbion-eu-restriction' ),
			'manage_options',
			'symbion-eu-restriction',
			array( $this, 'render_admin_page' ),
			'dashicons-admin-site-alt3',
			56
		);

		add_submenu_page(
			'symbion-eu-restriction',
			__( 'Einstellungen', 'symbion-eu-restriction' ),
			__( 'Einstellungen', 'symbion-eu-restriction' ),
			'manage_options',
			'symbion-eu-restriction',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Einstellungen registrieren
	 */
	public function register_settings() {
		// Haupt-Einstellungen
		register_setting( 'symbion_eu_settings', 'symbion_eu_enabled' );
		register_setting( 'symbion_eu_settings', 'symbion_eu_test_mode_enabled' );
		register_setting( 'symbion_eu_settings', 'symbion_eu_filter_admins' );
		register_setting( 'symbion_eu_settings', 'symbion_eu_geoip_provider' );
		register_setting( 'symbion_eu_settings', 'symbion_eu_fallback_is_eu' );
		register_setting( 'symbion_eu_settings', 'symbion_eu_redirect_type' );
		register_setting( 'symbion_eu_settings', 'symbion_eu_redirect_custom_page' );
		register_setting( 'symbion_eu_settings', 'symbion_eu_filter_categories' );
		register_setting( 'symbion_eu_settings', 'symbion_eu_hide_empty_categories' );
		register_setting( 'symbion_eu_settings', 'symbion_eu_hidden_categories' );
	}

	/**
	 * Admin-Assets laden
	 *
	 * @param string $hook Hook-Name
	 */
	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_symbion-eu-restriction' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'symbion-eu-admin',
			SYMBION_EU_URL . 'assets/css/admin.css',
			array(),
			SYMBION_EU_VERSION
		);

		wp_enqueue_script(
			'symbion-eu-admin',
			SYMBION_EU_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			SYMBION_EU_VERSION,
			true
		);
	}

	/**
	 * Action-Links hinzufügen
	 *
	 * @param array $links Links
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=symbion-eu-restriction' ) . '">' . __( 'Einstellungen', 'symbion-eu-restriction' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Admin-Seite rendern
	 */
	public function render_admin_page() {
		// Einstellungen speichern
		if ( isset( $_POST['symbion_eu_save_settings'] ) && check_admin_referer( 'symbion_eu_settings' ) ) {
			$this->save_settings();
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Einstellungen gespeichert.', 'symbion-eu-restriction' ) . '</p></div>';
		}

		$core    = symbion_eu_restriction()->get_component( 'core' );
		$geoip   = symbion_eu_restriction()->get_component( 'geoip' );
		$is_eu   = $geoip ? $geoip->is_visitor_from_eu() : true;
		$set_ids = $core ? $core->get_set_product_ids() : array();

		?>
		<div class="wrap symbion-eu-admin-wrap">
			<div class="symbion-eu-header">
				<div class="symbion-eu-logo">
					<img src="<?php echo esc_url( SYMBION_EU_URL . 'assets/images/symbion-logo-white.svg' ); ?>" alt="Symbion">
				</div>
				<h1><?php esc_html_e( 'EU Restriction', 'symbion-eu-restriction' ); ?></h1>
				<p class="symbion-eu-subtitle">
					<?php esc_html_e( 'Intelligente Geo-Filterung für WooCommerce Set-Produkte', 'symbion-eu-restriction' ); ?>
				</p>
			</div>

			<!-- Dashboard-Stats -->
			<div class="symbion-eu-dashboard">
				<div class="symbion-eu-card symbion-eu-stat">
					<div class="stat-icon">
						<span class="dashicons dashicons-products"></span>
					</div>
					<div class="stat-content">
						<div class="stat-number"><?php echo esc_html( count( $set_ids ) ); ?></div>
						<div class="stat-label"><?php esc_html_e( 'Set-Produkte', 'symbion-eu-restriction' ); ?></div>
					</div>
				</div>

				<div class="symbion-eu-card symbion-eu-stat">
					<div class="stat-icon">
						<span class="dashicons dashicons-admin-site-alt3"></span>
					</div>
					<div class="stat-content">
						<div class="stat-number"><?php echo $is_eu ? '🇪🇺 EU' : '🌍 Non-EU'; ?></div>
						<div class="stat-label"><?php esc_html_e( 'Ihr Standort', 'symbion-eu-restriction' ); ?></div>
					</div>
				</div>

				<div class="symbion-eu-card symbion-eu-stat">
					<div class="stat-icon">
						<span class="dashicons dashicons-visibility"></span>
					</div>
					<div class="stat-content">
						<div class="stat-number"><?php echo $core && $core->is_enabled() ? __( 'Aktiv', 'symbion-eu-restriction' ) : __( 'Inaktiv', 'symbion-eu-restriction' ); ?></div>
						<div class="stat-label"><?php esc_html_e( 'Plugin-Status', 'symbion-eu-restriction' ); ?></div>
					</div>
				</div>
			</div>

			<!-- Tabs -->
			<div class="symbion-eu-tabs">
				<button class="symbion-eu-tab active" data-tab="general">
					<span class="dashicons dashicons-admin-generic"></span>
					<?php esc_html_e( 'Allgemein', 'symbion-eu-restriction' ); ?>
				</button>
				<button class="symbion-eu-tab" data-tab="geoip">
					<span class="dashicons dashicons-admin-site-alt3"></span>
					<?php esc_html_e( 'GeoIP', 'symbion-eu-restriction' ); ?>
				</button>
				<button class="symbion-eu-tab" data-tab="categories">
					<span class="dashicons dashicons-category"></span>
					<?php esc_html_e( 'Kategorien', 'symbion-eu-restriction' ); ?>
				</button>
				<button class="symbion-eu-tab" data-tab="advanced">
					<span class="dashicons dashicons-admin-tools"></span>
					<?php esc_html_e( 'Erweitert', 'symbion-eu-restriction' ); ?>
				</button>
			</div>

			<!-- Settings Form -->
			<form method="post" action="">
				<?php wp_nonce_field( 'symbion_eu_settings' ); ?>

				<!-- Tab: Allgemein -->
				<div class="symbion-eu-tab-content active" data-tab-content="general">
					<div class="symbion-eu-card">
						<h2><?php esc_html_e( 'Grundeinstellungen', 'symbion-eu-restriction' ); ?></h2>

						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="symbion_eu_enabled"><?php esc_html_e( 'Plugin aktivieren', 'symbion-eu-restriction' ); ?></label>
								</th>
								<td>
									<label class="symbion-eu-toggle">
										<input type="checkbox" name="symbion_eu_enabled" id="symbion_eu_enabled" value="yes" <?php checked( get_option( 'symbion_eu_enabled', 'yes' ), 'yes' ); ?>>
										<span class="symbion-eu-toggle-slider"></span>
									</label>
									<p class="description">
										<?php esc_html_e( 'Aktiviert die Filterung von Set-Produkten für Non-EU Besucher', 'symbion-eu-restriction' ); ?>
									</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="symbion_eu_test_mode_enabled"><?php esc_html_e( 'Testmodus', 'symbion-eu-restriction' ); ?></label>
								</th>
								<td>
									<label class="symbion-eu-toggle">
										<input type="checkbox" name="symbion_eu_test_mode_enabled" id="symbion_eu_test_mode_enabled" value="yes" <?php checked( get_option( 'symbion_eu_test_mode_enabled', 'yes' ), 'yes' ); ?>>
										<span class="symbion-eu-toggle-slider"></span>
									</label>
									<p class="description">
										<?php esc_html_e( 'Zeigt Testmodus-Dropdown in der Admin Bar für Administratoren', 'symbion-eu-restriction' ); ?>
									</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="symbion_eu_filter_admins"><?php esc_html_e( 'Administratoren filtern', 'symbion-eu-restriction' ); ?></label>
								</th>
								<td>
									<label class="symbion-eu-toggle">
										<input type="checkbox" name="symbion_eu_filter_admins" id="symbion_eu_filter_admins" value="yes" <?php checked( get_option( 'symbion_eu_filter_admins', 'no' ), 'yes' ); ?>>
										<span class="symbion-eu-toggle-slider"></span>
									</label>
									<p class="description">
										<?php esc_html_e( 'Wenn deaktiviert, sehen Administratoren immer alle Produkte', 'symbion-eu-restriction' ); ?>
									</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="symbion_eu_redirect_type"><?php esc_html_e( 'Weiterleitung', 'symbion-eu-restriction' ); ?></label>
								</th>
								<td>
									<select name="symbion_eu_redirect_type" id="symbion_eu_redirect_type">
										<option value="404" <?php selected( get_option( 'symbion_eu_redirect_type', '404' ), '404' ); ?>>
											<?php esc_html_e( '404 Fehlerseite anzeigen', 'symbion-eu-restriction' ); ?>
										</option>
										<option value="shop" <?php selected( get_option( 'symbion_eu_redirect_type', '404' ), 'shop' ); ?>>
											<?php esc_html_e( 'Zur Shop-Seite weiterleiten', 'symbion-eu-restriction' ); ?>
										</option>
									</select>
									<p class="description">
										<?php esc_html_e( 'Was passiert wenn ein Non-EU Besucher ein Set-Produkt direkt aufruft?', 'symbion-eu-restriction' ); ?>
									</p>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<!-- Tab: GeoIP -->
				<div class="symbion-eu-tab-content" data-tab-content="geoip">
					<div class="symbion-eu-card">
						<h2><?php esc_html_e( 'GeoIP-Einstellungen', 'symbion-eu-restriction' ); ?></h2>

						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="symbion_eu_geoip_provider"><?php esc_html_e( 'GeoIP-Provider', 'symbion-eu-restriction' ); ?></label>
								</th>
								<td>
									<select name="symbion_eu_geoip_provider" id="symbion_eu_geoip_provider">
										<option value="woocommerce" <?php selected( get_option( 'symbion_eu_geoip_provider', 'woocommerce' ), 'woocommerce' ); ?>>
											<?php esc_html_e( 'WooCommerce (MaxMind)', 'symbion-eu-restriction' ); ?>
										</option>
										<option value="cloudflare" <?php selected( get_option( 'symbion_eu_geoip_provider', 'woocommerce' ), 'cloudflare' ); ?>>
											Cloudflare
										</option>
										<option value="header" <?php selected( get_option( 'symbion_eu_geoip_provider', 'woocommerce' ), 'header' ); ?>>
											<?php esc_html_e( 'Generischer Header', 'symbion-eu-restriction' ); ?>
										</option>
									</select>
									<p class="description">
										<?php esc_html_e( 'WooCommerce (MaxMind) empfohlen - nutzt die bereits konfigurierte WooCommerce GeoIP-Datenbank', 'symbion-eu-restriction' ); ?>
									</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="symbion_eu_fallback_is_eu"><?php esc_html_e( 'Fallback', 'symbion-eu-restriction' ); ?></label>
								</th>
								<td>
									<label class="symbion-eu-toggle">
										<input type="checkbox" name="symbion_eu_fallback_is_eu" id="symbion_eu_fallback_is_eu" value="yes" <?php checked( get_option( 'symbion_eu_fallback_is_eu', 'yes' ), 'yes' ); ?>>
										<span class="symbion-eu-toggle-slider"></span>
									</label>
									<p class="description">
										<?php esc_html_e( 'Bei aktiviert: Besucher mit unbekanntem Land sehen alle Produkte (als EU-Besucher behandelt)', 'symbion-eu-restriction' ); ?>
									</p>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<!-- Tab: Kategorien -->
				<div class="symbion-eu-tab-content" data-tab-content="categories">
					<div class="symbion-eu-card">
						<h2><?php esc_html_e( 'Kategorie-Filterung', 'symbion-eu-restriction' ); ?></h2>

						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="symbion_eu_filter_categories"><?php esc_html_e( 'Kategorien filtern', 'symbion-eu-restriction' ); ?></label>
								</th>
								<td>
									<label class="symbion-eu-toggle">
										<input type="checkbox" name="symbion_eu_filter_categories" id="symbion_eu_filter_categories" value="yes" <?php checked( get_option( 'symbion_eu_filter_categories', 'yes' ), 'yes' ); ?>>
										<span class="symbion-eu-toggle-slider"></span>
									</label>
									<p class="description">
										<?php esc_html_e( 'Versteckt ausgewählte Kategorien für Non-EU Besucher', 'symbion-eu-restriction' ); ?>
									</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="symbion_eu_hidden_categories"><?php esc_html_e( 'Auszublendende Kategorien', 'symbion-eu-restriction' ); ?></label>
								</th>
								<td>
									<?php
									$hidden_categories = get_option( 'symbion_eu_hidden_categories', array() );
									if ( ! is_array( $hidden_categories ) ) {
										$hidden_categories = array();
									}
									
									$product_categories = get_terms( array(
										'taxonomy'   => 'product_cat',
										'hide_empty' => false,
										'orderby'    => 'name',
										'order'      => 'ASC',
									) );
									
									if ( ! empty( $product_categories ) && ! is_wp_error( $product_categories ) ) {
										echo '<select name="symbion_eu_hidden_categories[]" id="symbion_eu_hidden_categories" multiple size="10" style="min-width: 400px; height: 200px;">';
										foreach ( $product_categories as $category ) {
											$selected = in_array( $category->term_id, $hidden_categories, true ) ? ' selected' : '';
											echo '<option value="' . esc_attr( $category->term_id ) . '"' . $selected . '>' . esc_html( $category->name ) . ' (' . $category->count . ')</option>';
										}
										echo '</select>';
										echo '<p class="description">' . esc_html__( 'Halte Strg/Cmd gedrückt, um mehrere Kategorien auszuwählen. Diese Kategorien werden für Non-EU Besucher komplett ausgeblendet.', 'symbion-eu-restriction' ) . '</p>';
									} else {
										echo '<p>' . esc_html__( 'Keine Produkt-Kategorien gefunden.', 'symbion-eu-restriction' ) . '</p>';
									}
									?>
								</td>
							</tr>
						</table>
					</div>
				</div>

				<!-- Tab: Erweitert -->
				<div class="symbion-eu-tab-content" data-tab-content="advanced">
					<div class="symbion-eu-card">
						<h2><?php esc_html_e( 'Erweiterte Einstellungen', 'symbion-eu-restriction' ); ?></h2>

						<table class="form-table">
							<tr>
								<th scope="row">
									<?php esc_html_e( 'CSS-Klasse "nur-eu"', 'symbion-eu-restriction' ); ?>
								</th>
								<td>
									<p>
										<?php esc_html_e( 'Fügen Sie beliebigen HTML-Elementen die Klasse hinzu:', 'symbion-eu-restriction' ); ?>
										<code>class="nur-eu"</code>
									</p>
									<p class="description">
										<?php esc_html_e( 'Diese Elemente werden für Non-EU Besucher automatisch versteckt.', 'symbion-eu-restriction' ); ?>
									</p>
									<pre>&lt;div class="nur-eu"&gt;Nur für EU-Besucher sichtbar&lt;/div&gt;</pre>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<?php esc_html_e( 'Cache leeren', 'symbion-eu-restriction' ); ?>
								</th>
								<td>
									<button type="button" class="button" id="symbion-eu-clear-cache">
										<?php esc_html_e( 'Cache jetzt leeren', 'symbion-eu-restriction' ); ?>
									</button>
									<p class="description">
										<?php esc_html_e( 'Leert den internen Plugin-Cache und WooCommerce-Produkt-Cache', 'symbion-eu-restriction' ); ?>
									</p>
								</td>
							</tr>
						</table>
					</div>

					<div class="symbion-eu-card">
						<h2><?php esc_html_e( 'Plugin-Informationen', 'symbion-eu-restriction' ); ?></h2>
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Version', 'symbion-eu-restriction' ); ?></th>
								<td><?php echo esc_html( SYMBION_EU_VERSION ); ?></td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Entwickler', 'symbion-eu-restriction' ); ?></th>
								<td><a href="https://symbion.dev" target="_blank">symbion.dev</a></td>
							</tr>
						</table>
					</div>
				</div>

				<div class="symbion-eu-card">
					<button type="submit" name="symbion_eu_save_settings" class="button button-primary button-large">
						<?php esc_html_e( 'Einstellungen speichern', 'symbion-eu-restriction' ); ?>
					</button>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Einstellungen speichern
	 */
	private function save_settings() {
		$options = array(
			'symbion_eu_enabled',
			'symbion_eu_test_mode_enabled',
			'symbion_eu_filter_admins',
			'symbion_eu_geoip_provider',
			'symbion_eu_fallback_is_eu',
			'symbion_eu_redirect_type',
			'symbion_eu_filter_categories',
		);

		foreach ( $options as $option ) {
			$value = isset( $_POST[ $option ] ) ? sanitize_text_field( wp_unslash( $_POST[ $option ] ) ) : '';
			
			// Checkboxes
			if ( in_array( $option, array( 'symbion_eu_enabled', 'symbion_eu_test_mode_enabled', 'symbion_eu_filter_admins', 'symbion_eu_fallback_is_eu', 'symbion_eu_filter_categories' ), true ) ) {
				$value = 'yes' === $value ? 'yes' : 'no';
			}

			update_option( $option, $value );
		}

		// Kategorien-Array separat speichern
		$hidden_categories = isset( $_POST['symbion_eu_hidden_categories'] ) ? array_map( 'absint', $_POST['symbion_eu_hidden_categories'] ) : array();
		update_option( 'symbion_eu_hidden_categories', $hidden_categories );

		// Cache invalidieren
		$core = symbion_eu_restriction()->get_component( 'core' );
		if ( $core ) {
			$core->invalidate_cache();
		}
	}
}

