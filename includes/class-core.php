<?php
/**
 * Kern-Controller
 *
 * @package SymbionEURestriction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kern-Controller-Klasse
 */
class Symbion_EU_Core {

	/**
	 * Singleton-Instanz
	 *
	 * @var Symbion_EU_Core
	 */
	private static $instance = null;

	/**
	 * Meta-Key für Set-Produkte
	 *
	 * @var string
	 */
	const META_KEY_SET = '_symbion_is_product_set';

	/**
	 * Meta-Key für Set-Kategorien
	 *
	 * @var string
	 */
	const META_KEY_CATEGORY = '_symbion_is_set_category';

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return Symbion_EU_Core
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
		// Hooks registrieren
		$this->register_hooks();
	}

	/**
	 * Hooks registrieren
	 */
	private function register_hooks() {
		// Cache-Invalidierung bei Produkt-Update
		add_action( 'save_post_product', array( $this, 'invalidate_cache' ) );
		add_action( 'edit_term', array( $this, 'invalidate_cache' ) );
		add_action( 'delete_term', array( $this, 'invalidate_cache' ) );
	}

	/**
	 * Prüfen ob Plugin aktiv ist
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return 'yes' === get_option( 'symbion_eu_enabled', 'yes' );
	}

	/**
	 * Prüfen ob gefiltert werden soll
	 *
	 * @return bool
	 */
	public function should_filter() {
		// Plugin deaktiviert?
		if ( ! $this->is_enabled() ) {
			return false;
		}

		// Administratoren filtern?
		$filter_admins = get_option( 'symbion_eu_filter_admins', 'no' );
		if ( 'no' === $filter_admins && current_user_can( 'manage_options' ) ) {
			return false;
		}

		// GeoIP-Check
		$geoip      = symbion_eu_restriction()->get_component( 'geoip' );
		$is_from_eu = $geoip ? $geoip->is_visitor_from_eu() : true;

		// Nur für Non-EU Besucher filtern
		return ! $is_from_eu;
	}

	/**
	 * Cache invalidieren
	 */
	public function invalidate_cache() {
		delete_transient( 'symbion_eu_set_product_ids' );
		delete_transient( 'symbion_eu_set_only_categories' );
	}

	/**
	 * Set-Produkt-IDs abrufen (gecacht)
	 *
	 * @return array
	 */
	public function get_set_product_ids() {
		$cache_key = 'symbion_eu_set_product_ids';
		$ids       = get_transient( $cache_key );

		if ( false !== $ids && is_array( $ids ) ) {
			return $ids;
		}

		// Produkte abfragen
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => self::META_KEY_SET,
					'value' => '1',
				),
			),
		);

		$query = new WP_Query( $args );
		$ids   = $query->posts;

		// Cache für 10 Minuten
		set_transient( $cache_key, $ids, 10 * MINUTE_IN_SECONDS );

		return $ids;
	}

	/**
	 * Kategorien abrufen, die nur Sets enthalten (gecacht)
	 *
	 * @return array Term-IDs
	 */
	public function get_set_only_category_ids() {
		$cache_key = 'symbion_eu_set_only_categories';
		$ids       = get_transient( $cache_key );

		if ( false !== $ids && is_array( $ids ) ) {
			return $ids;
		}

		$set_only_categories = array();

		// Alle Produkt-Kategorien holen
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $categories ) || empty( $categories ) ) {
			set_transient( $cache_key, array(), 10 * MINUTE_IN_SECONDS );
			return array();
		}

		// Für jede Kategorie prüfen
		foreach ( $categories as $category ) {
			// Produkte in dieser Kategorie abrufen
			$products = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'tax_query'      => array(
						array(
							'taxonomy' => 'product_cat',
							'field'    => 'term_id',
							'terms'    => $category->term_id,
						),
					),
				)
			);

			// Kategorie leer? Überspringen
			if ( empty( $products ) ) {
				continue;
			}

			// Prüfen ob ALLE Produkte Sets sind
			$all_are_sets = true;
			foreach ( $products as $product_id ) {
				if ( ! $this->is_product_set( $product_id ) ) {
					$all_are_sets = false;
					break;
				}
			}

			// Wenn alle Produkte Sets sind, Kategorie merken
			if ( $all_are_sets ) {
				$set_only_categories[] = $category->term_id;
			}
		}

		// Cache für 10 Minuten
		set_transient( $cache_key, $set_only_categories, 10 * MINUTE_IN_SECONDS );

		return $set_only_categories;
	}

	/**
	 * Prüfen ob Produkt ein Set ist
	 *
	 * @param int|WC_Product $product Produkt-ID oder Objekt
	 * @return bool
	 */
	public function is_product_set( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product instanceof WC_Product ) {
			return false;
		}

		return '1' === $product->get_meta( self::META_KEY_SET, true );
	}
}

