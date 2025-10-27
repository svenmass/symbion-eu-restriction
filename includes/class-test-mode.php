<?php
/**
 * Testmodus mit Admin Bar Integration
 *
 * @package SymbionEURestriction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Testmodus-Klasse
 */
class Symbion_EU_Test_Mode {

	/**
	 * Singleton-Instanz
	 *
	 * @var Symbion_EU_Test_Mode
	 */
	private static $instance = null;

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return Symbion_EU_Test_Mode
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
		// AJAX-Handler
		add_action( 'wp_ajax_symbion_eu_set_test_country', array( $this, 'ajax_set_test_country' ) );
		
		// Admin Bar
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 100 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_admin_bar_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_bar_assets' ) );
	}

	/**
	 * Admin Bar Menü hinzufügen
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin Bar
	 */
	public function add_admin_bar_menu( $wp_admin_bar ) {
		// Nur für Administratoren
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Aktuelles Test-Land aus User Meta
		$user_id = get_current_user_id();
		$current_test = get_user_meta( $user_id, 'symbion_eu_test_country', true );

		// Debug: Log Status
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Symbion EU Test Mode - User Meta Value: ' . ( $current_test ? $current_test : 'EMPTY' ) );
			error_log( 'Symbion EU Test Mode - User ID: ' . $user_id );
		}

		// Titel
		$title = '<span class="ab-icon dashicons dashicons-admin-site-alt3"></span>';
		if ( ! empty( $current_test ) ) {
			$geoip = symbion_eu_restriction()->get_component( 'geoip' );
			$countries = $geoip ? $geoip->get_test_countries() : array();
			$country_label = isset( $countries[ $current_test ] ) ? $countries[ $current_test ] : $current_test;
			$title .= '<span class="ab-label"> ' . esc_html( $country_label ) . '</span>';
		} else {
			$title .= '<span class="ab-label"> EU Restriction</span>';
		}

		// Haupt-Menü
		$wp_admin_bar->add_node(
			array(
				'id'    => 'symbion-eu-test',
				'title' => $title,
				'href'  => '#',
				'meta'  => array(
					'class' => 'symbion-eu-test-menu',
				),
			)
		);

		// Test-Länder hinzufügen
		$geoip = symbion_eu_restriction()->get_component( 'geoip' );
		$test_countries = $geoip ? $geoip->get_test_countries() : array();

		foreach ( $test_countries as $code => $label ) {
			// Vergleich: Leerer String vs leerer Code
			$is_active = ( empty( $current_test ) && empty( $code ) ) || ( ! empty( $current_test ) && $current_test === $code );
			
			// Country-Code in die ID einbauen (kann dann per JavaScript ausgelesen werden)
			$node_id = 'symbion-eu-test-' . ( $code ? $code : 'none' );
			
			$wp_admin_bar->add_node(
				array(
					'parent' => 'symbion-eu-test',
					'id'     => $node_id,
					'title'  => $is_active ? '✓ ' . $label : $label,
					'href'   => 'javascript:void(0);',
					'meta'   => array(
						'class'   => 'symbion-eu-test-country' . ( $is_active ? ' active' : '' ),
						'onclick' => 'return false;',
						// Country-Code als HTML5 data attribute im title-span
						'title'   => $code, // Wird als title-Attribut gesetzt
					),
				)
			);
		}

		// Einstellungen-Link
		$wp_admin_bar->add_node(
			array(
				'parent' => 'symbion-eu-test',
				'id'     => 'symbion-eu-settings',
				'title'  => '⚙️ ' . __( 'Einstellungen', 'symbion-eu-restriction' ),
				'href'   => admin_url( 'admin.php?page=symbion-eu-restriction' ),
			)
		);
	}

	/**
	 * Admin Bar Assets laden
	 */
	public function enqueue_admin_bar_assets() {
		if ( ! current_user_can( 'manage_options' ) || ! is_admin_bar_showing() ) {
			return;
		}

		wp_enqueue_style(
			'symbion-eu-admin-bar',
			SYMBION_EU_URL . 'assets/css/admin-bar.css',
			array(),
			SYMBION_EU_VERSION
		);

		wp_enqueue_script(
			'symbion-eu-admin-bar',
			SYMBION_EU_URL . 'assets/js/admin-bar.js',
			array( 'jquery' ),
			SYMBION_EU_VERSION,
			true
		);

		wp_localize_script(
			'symbion-eu-admin-bar',
			'symbionEUAdminBar',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'symbion_eu_test_mode' ),
			)
		);
	}

	/**
	 * AJAX: Test-Land setzen
	 */
	public function ajax_set_test_country() {
		check_ajax_referer( 'symbion_eu_test_mode', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung', 'symbion-eu-restriction' ) ) );
		}

		$country = isset( $_POST['country'] ) ? sanitize_text_field( wp_unslash( $_POST['country'] ) ) : '';
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Benutzer nicht gefunden', 'symbion-eu-restriction' ) ) );
		}

		// User Meta setzen oder löschen (viel zuverlässiger als Cookies!)
		if ( empty( $country ) ) {
			delete_user_meta( $user_id, 'symbion_eu_test_country' );
			
			// Debug-Logging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Symbion EU: Testmodus deaktiviert für User ' . $user_id );
			}
			
			wp_send_json_success( 
				array( 
					'message' => __( 'Testmodus deaktiviert', 'symbion-eu-restriction' ),
					'country' => '',
				) 
			);
		} else {
			update_user_meta( $user_id, 'symbion_eu_test_country', $country );
			
			// Debug-Logging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Symbion EU: Testmodus aktiviert für User ' . $user_id . ' - Land: ' . $country );
			}
			
			wp_send_json_success( 
				array( 
					'message' => sprintf( __( 'Testmodus: %s', 'symbion-eu-restriction' ), $country ),
					'country' => $country,
				) 
			);
		}
	}
}

