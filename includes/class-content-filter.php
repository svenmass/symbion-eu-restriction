<?php
/**
 * Content-Filter für CSS-Klasse "nur-eu"
 *
 * @package SymbionEURestriction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content-Filter-Klasse
 */
class Symbion_EU_Content_Filter {

	/**
	 * Singleton-Instanz
	 *
	 * @var Symbion_EU_Content_Filter
	 */
	private static $instance = null;

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return Symbion_EU_Content_Filter
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
	}

	/**
	 * Assets laden
	 */
	public function enqueue_assets() {
		// CSS
		wp_enqueue_style(
			'symbion-eu-content-filter',
			SYMBION_EU_URL . 'assets/css/frontend.css',
			array(),
			SYMBION_EU_VERSION
		);

		// JavaScript für erweiterte Funktionalität
		wp_enqueue_script(
			'symbion-eu-content-filter',
			SYMBION_EU_URL . 'assets/js/frontend.js',
			array(),
			SYMBION_EU_VERSION,
			true
		);

		// Daten an JavaScript übergeben
		wp_localize_script(
			'symbion-eu-content-filter',
			'symbionEU',
			array(
				'isEU'       => ! $this->should_hide_nur_eu(),
				'isNonEU'    => $this->should_hide_nur_eu(),
				'testMode'   => $this->is_test_mode(),
				'testCountry' => $this->get_test_country(),
			)
		);
	}

	/**
	 * Body-Klasse hinzufügen
	 *
	 * @param array $classes Body-Klassen
	 * @return array
	 */
	public function add_body_class( $classes ) {
		$geoip = symbion_eu_restriction()->get_component( 'geoip' );
		
		if ( $geoip ) {
			if ( $geoip->is_visitor_from_eu() ) {
				$classes[] = 'symbion-eu-visitor';
			} else {
				$classes[] = 'symbion-non-eu-visitor';
			}
		}

		// Testmodus-Klasse
		if ( $this->is_test_mode() ) {
			$classes[] = 'symbion-test-mode';
			$test_country = $this->get_test_country();
			if ( $test_country ) {
				$classes[] = 'symbion-test-' . strtolower( $test_country );
			}
		}

		return $classes;
	}

	/**
	 * Prüfen ob "nur-eu" Inhalte versteckt werden sollen
	 *
	 * @return bool
	 */
	private function should_hide_nur_eu() {
		$core = symbion_eu_restriction()->get_component( 'core' );
		return $core ? $core->should_filter() : false;
	}

	/**
	 * Prüfen ob Testmodus aktiv
	 *
	 * @return bool
	 */
	private function is_test_mode() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		$user_id = get_current_user_id();
		$test_country = get_user_meta( $user_id, 'symbion_eu_test_country', true );
		return ! empty( $test_country );
	}

	/**
	 * Test-Land abrufen
	 *
	 * @return string
	 */
	private function get_test_country() {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			return get_user_meta( $user_id, 'symbion_eu_test_country', true );
		}
		return '';
	}
}

