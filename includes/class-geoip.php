<?php
/**
 * GeoIP-Handler mit Testmodus
 *
 * @package SymbionEURestriction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GeoIP-Handler-Klasse
 */
class Symbion_EU_GeoIP {

	/**
	 * Singleton-Instanz
	 *
	 * @var Symbion_EU_GeoIP
	 */
	private static $instance = null;

	/**
	 * EU-Ländercodes (ISO 3166-1 alpha-2)
	 *
	 * @var array
	 */
	private $eu_countries = array(
		'AT', // Österreich
		'BE', // Belgien
		'BG', // Bulgarien
		'HR', // Kroatien
		'CY', // Zypern
		'CZ', // Tschechien
		'DK', // Dänemark
		'EE', // Estland
		'FI', // Finnland
		'FR', // Frankreich
		'DE', // Deutschland
		'GR', // Griechenland
		'HU', // Ungarn
		'IE', // Irland
		'IT', // Italien
		'LV', // Lettland
		'LT', // Litauen
		'LU', // Luxemburg
		'MT', // Malta
		'NL', // Niederlande
		'PL', // Polen
		'PT', // Portugal
		'RO', // Rumänien
		'SK', // Slowakei
		'SI', // Slowenien
		'ES', // Spanien
		'SE', // Schweden
	);

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return Symbion_EU_GeoIP
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
		// Hooks hier falls benötigt
	}

	/**
	 * Prüfen ob Besucher aus EU kommt
	 *
	 * @return bool
	 */
	public function is_visitor_from_eu() {
		// Testmodus prüfen
		$test_country = $this->get_test_mode_country();
		if ( $test_country ) {
			return in_array( $test_country, $this->eu_countries, true );
		}

		// Land ermitteln
		$country = $this->get_visitor_country();

		// Fallback wenn Land nicht ermittelt werden konnte
		if ( ! $country ) {
			$fallback_is_eu = get_option( 'symbion_eu_fallback_is_eu', 'yes' );
			return 'yes' === $fallback_is_eu;
		}

		// Prüfen ob EU-Land
		return in_array( $country, $this->eu_countries, true );
	}

	/**
	 * Besucher-Land ermitteln
	 *
	 * @return string|false Ländercode oder false
	 */
	private function get_visitor_country() {
		$provider = get_option( 'symbion_eu_geoip_provider', 'woocommerce' );

		switch ( $provider ) {
			case 'woocommerce':
				return $this->get_country_from_woocommerce();
			
			case 'cloudflare':
				return $this->get_country_from_cloudflare();
			
			case 'header':
				return $this->get_country_from_header();
			
			default:
				return false;
		}
	}

	/**
	 * Land aus WooCommerce GeoIP ermitteln (MaxMind)
	 *
	 * @return string|false
	 */
	private function get_country_from_woocommerce() {
		// WooCommerce GeoIP nutzen
		if ( ! class_exists( 'WC_Geolocation' ) ) {
			return false;
		}

		// WooCommerce Geolocation
		$location = WC_Geolocation::geolocate_ip();
		
		if ( ! empty( $location['country'] ) && strlen( $location['country'] ) === 2 ) {
			return strtoupper( $location['country'] );
		}

		// Fallback: Aus Customer-Daten (wenn eingeloggt)
		if ( function_exists( 'WC' ) && WC()->customer ) {
			$country = WC()->customer->get_billing_country();
			if ( ! empty( $country ) && strlen( $country ) === 2 ) {
				return strtoupper( $country );
			}
		}

		return false;
	}

	/**
	 * Land aus Cloudflare-Header ermitteln
	 *
	 * @return string|false
	 */
	private function get_country_from_cloudflare() {
		if ( isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			$country = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) );
			// Cloudflare sendet 'XX' für unbekannte Länder
			if ( 'XX' !== $country && strlen( $country ) === 2 ) {
				return $country;
			}
		}
		return false;
	}

	/**
	 * Land aus generischem Header ermitteln
	 *
	 * @return string|false
	 */
	private function get_country_from_header() {
		// Verschiedene Header-Namen die CDNs/Proxies verwenden
		$header_names = array(
			'HTTP_CF_IPCOUNTRY',          // Cloudflare
			'HTTP_X_COUNTRY_CODE',         // Generisch
			'HTTP_GEOIP_COUNTRY_CODE',     // GeoIP
			'HTTP_X_GEOIP_COUNTRY',        // GeoIP Alternative
		);

		foreach ( $header_names as $header ) {
			if ( isset( $_SERVER[ $header ] ) ) {
				$country = strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
				if ( strlen( $country ) === 2 && ctype_alpha( $country ) ) {
					return $country;
				}
			}
		}

		return false;
	}

	/**
	 * Testmodus-Land abrufen
	 *
	 * @return string|false
	 */
	private function get_test_mode_country() {
		// Nur für berechtigte Benutzer
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		// Aus User Meta (zuverlässiger als Cookies)
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$test_country = get_user_meta( $user_id, 'symbion_eu_test_country', true );
			if ( ! empty( $test_country ) ) {
				return $test_country;
			}
		}

		return false;
	}

	/**
	 * EU-Länder abrufen
	 *
	 * @return array
	 */
	public function get_eu_countries() {
		return $this->eu_countries;
	}

	/**
	 * Test-Länder mit Labels abrufen
	 *
	 * @return array
	 */
	public function get_test_countries() {
		return array(
			''   => __( 'Kein Testmodus', 'symbion-eu-restriction' ),
			'DE' => '🇩🇪 ' . __( 'Deutschland (EU)', 'symbion-eu-restriction' ),
			'CH' => '🇨🇭 ' . __( 'Schweiz (Non-EU)', 'symbion-eu-restriction' ),
			'GB' => '🇬🇧 ' . __( 'Großbritannien (Non-EU)', 'symbion-eu-restriction' ),
			'US' => '🇺🇸 ' . __( 'USA (Non-EU)', 'symbion-eu-restriction' ),
			'FR' => '🇫🇷 ' . __( 'Frankreich (EU)', 'symbion-eu-restriction' ),
			'AT' => '🇦🇹 ' . __( 'Österreich (EU)', 'symbion-eu-restriction' ),
		);
	}
}

