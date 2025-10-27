<?php
/**
 * Plugin Name: Symbion EU Restriction
 * Plugin URI: https://symbion.dev
 * Description: Intelligente EU-Restriktion für WooCommerce Set-Produkte mit Testmodus und Content-Filterung
 * Version: 1.0.0
 * Author: symbion.dev
 * Author URI: https://symbion.dev
 * Text Domain: symbion-eu-restriction
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package SymbionEURestriction
 */

// Direktzugriff verhindern
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin-Konstanten definieren
define( 'SYMBION_EU_VERSION', '1.0.0' );
define( 'SYMBION_EU_FILE', __FILE__ );
define( 'SYMBION_EU_PATH', plugin_dir_path( __FILE__ ) );
define( 'SYMBION_EU_URL', plugin_dir_url( __FILE__ ) );
define( 'SYMBION_EU_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Hauptklasse für das Plugin
 */
final class Symbion_EU_Restriction {

	/**
	 * Singleton-Instanz
	 *
	 * @var Symbion_EU_Restriction
	 */
	private static $instance = null;

	/**
	 * Plugin-Komponenten
	 *
	 * @var array
	 */
	private $components = array();

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return Symbion_EU_Restriction
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
		// Autoloader registrieren
		spl_autoload_register( array( $this, 'autoload' ) );

		// Initialisierung
		add_action( 'plugins_loaded', array( $this, 'init' ), 10 );
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Aktivierung/Deaktivierung
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Autoloader für Plugin-Klassen
	 *
	 * @param string $class Klassenname
	 */
	public function autoload( $class ) {
		// Nur eigene Klassen laden
		if ( strpos( $class, 'Symbion_EU_' ) !== 0 ) {
			return;
		}

		// Klassenname in Dateiname konvertieren
		$class_name = str_replace( 'Symbion_EU_', '', $class );
		$class_name = strtolower( str_replace( '_', '-', $class_name ) );
		$file       = SYMBION_EU_PATH . 'includes/class-' . $class_name . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}

	/**
	 * Plugin initialisieren
	 */
	public function init() {
		// WooCommerce-Check
		if ( ! $this->check_requirements() ) {
			add_action( 'admin_notices', array( $this, 'requirements_notice' ) );
			return;
		}

		// Kern-Komponenten laden
		$this->load_components();

		// Action-Hook für Erweiterungen
		do_action( 'symbion_eu_restriction_loaded' );
	}

	/**
	 * Anforderungen prüfen
	 *
	 * @return bool
	 */
	private function check_requirements() {
		// WooCommerce aktiv?
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		// PHP-Version
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Komponenten laden
	 */
	private function load_components() {
		// Kern-Komponenten
		$this->components['core']            = Symbion_EU_Core::instance();
		$this->components['geoip']           = Symbion_EU_GeoIP::instance();
		$this->components['product_meta']    = Symbion_EU_Product_Meta::instance();
		$this->components['product_filter']  = Symbion_EU_Product_Filter::instance();
		$this->components['category_filter'] = Symbion_EU_Category_Filter_Simple::instance();
		$this->components['content_filter']  = Symbion_EU_Content_Filter::instance();

		// Admin-Komponenten
		if ( is_admin() ) {
			$this->components['admin']      = Symbion_EU_Admin::instance();
			$this->components['bulk_edit']  = Symbion_EU_Bulk_Edit::instance();
		}

		// Admin Bar (Frontend & Backend)
		$this->components['test_mode'] = Symbion_EU_Test_Mode::instance();
	}

	/**
	 * Textdomain laden
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'symbion-eu-restriction',
			false,
			dirname( SYMBION_EU_BASENAME ) . '/languages'
		);
	}

	/**
	 * Plugin-Aktivierung
	 */
	public function activate() {
		// Default-Optionen setzen
		$defaults = array(
			'enabled'                => 'yes',
			'test_mode_enabled'      => 'yes',
			'filter_admins'          => 'no',
			'geoip_provider'         => 'woocommerce',
			'fallback_is_eu'         => 'yes',
			'redirect_type'          => '404',
			'redirect_custom_page'   => 0,
			'filter_categories'      => 'yes',
			'hide_empty_categories'  => 'yes',
		);

		foreach ( $defaults as $key => $value ) {
			$option_key = 'symbion_eu_' . $key;
			if ( false === get_option( $option_key ) ) {
				add_option( $option_key, $value );
			}
		}

		// Cache leeren
		$this->clear_cache();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Plugin-Deaktivierung
	 */
	public function deactivate() {
		// Cache leeren
		$this->clear_cache();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Cache leeren
	 */
	private function clear_cache() {
		delete_transient( 'symbion_eu_set_product_ids' );
		delete_transient( 'symbion_eu_set_only_categories' );
		
		// WooCommerce-Cache leeren
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients();
		}
	}

	/**
	 * Anforderungs-Hinweis anzeigen
	 */
	public function requirements_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Symbion EU Restriction', 'symbion-eu-restriction' ); ?></strong><br>
				<?php esc_html_e( 'Dieses Plugin benötigt WooCommerce und PHP 7.4 oder höher.', 'symbion-eu-restriction' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Komponente abrufen
	 *
	 * @param string $name Komponentenname
	 * @return object|null
	 */
	public function get_component( $name ) {
		return isset( $this->components[ $name ] ) ? $this->components[ $name ] : null;
	}
}

/**
 * Plugin-Instanz starten
 *
 * @return Symbion_EU_Restriction
 */
function symbion_eu_restriction() {
	return Symbion_EU_Restriction::instance();
}

// Los geht's!
symbion_eu_restriction();

