<?php
/**
 * Einfache Kategorie-Filterung (Manual Selection)
 *
 * @package SymbionEURestriction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kategorie-Filter-Klasse (Simple)
 */
class Symbion_EU_Category_Filter_Simple {

	/**
	 * Singleton-Instanz
	 *
	 * @var Symbion_EU_Category_Filter_Simple
	 */
	private static $instance = null;

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return Symbion_EU_Category_Filter_Simple
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
		// WooCommerce Product Query (Shop, Archive)
		add_filter( 'woocommerce_product_query_tax_query', array( $this, 'filter_product_query' ), 10, 2 );
		
		// Terms Query (Widget, Menü)
		add_filter( 'get_terms_args', array( $this, 'filter_terms_args' ), 10, 2 );
		add_filter( 'get_terms', array( $this, 'filter_terms' ), 10, 3 );
	}

	/**
	 * Produktabfrage filtern
	 *
	 * @param array    $tax_query Tax Query
	 * @param WC_Query $wc_query  WC Query
	 * @return array
	 */
	public function filter_product_query( $tax_query, $wc_query ) {
		$core = symbion_eu_restriction()->get_component( 'core' );
		if ( ! $core || ! $core->should_filter() ) {
			return $tax_query;
		}

		// Kategorien filtern aktiviert?
		if ( 'yes' !== get_option( 'symbion_eu_filter_categories', 'yes' ) ) {
			return $tax_query;
		}

		$hidden_categories = get_option( 'symbion_eu_hidden_categories', array() );
		if ( empty( $hidden_categories ) || ! is_array( $hidden_categories ) ) {
			return $tax_query;
		}

		// Ausgewählte Kategorien ausschließen
		$tax_query[] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => $hidden_categories,
			'operator' => 'NOT IN',
		);

		return $tax_query;
	}

	/**
	 * Terms Query Args filtern
	 *
	 * @param array  $args       Query Args
	 * @param array  $taxonomies Taxonomies
	 * @return array
	 */
	public function filter_terms_args( $args, $taxonomies ) {
		// Nur für product_cat
		if ( ! in_array( 'product_cat', $taxonomies, true ) ) {
			return $args;
		}

		$core = symbion_eu_restriction()->get_component( 'core' );
		if ( ! $core || ! $core->should_filter() ) {
			return $args;
		}

		// Kategorien filtern aktiviert?
		if ( 'yes' !== get_option( 'symbion_eu_filter_categories', 'yes' ) ) {
			return $args;
		}

		$hidden_categories = get_option( 'symbion_eu_hidden_categories', array() );
		if ( empty( $hidden_categories ) || ! is_array( $hidden_categories ) ) {
			return $args;
		}

		// Exclude hinzufügen
		if ( ! isset( $args['exclude'] ) ) {
			$args['exclude'] = array();
		} elseif ( ! is_array( $args['exclude'] ) ) {
			$args['exclude'] = array( $args['exclude'] );
		}

		$args['exclude'] = array_merge( $args['exclude'], $hidden_categories );

		return $args;
	}

	/**
	 * Terms filtern (Post-Query)
	 *
	 * @param array  $terms      Terms
	 * @param array  $taxonomies Taxonomies
	 * @param array  $args       Args
	 * @return array
	 */
	public function filter_terms( $terms, $taxonomies, $args ) {
		// Nur für product_cat
		if ( ! in_array( 'product_cat', $taxonomies, true ) ) {
			return $terms;
		}

		$core = symbion_eu_restriction()->get_component( 'core' );
		if ( ! $core || ! $core->should_filter() ) {
			return $terms;
		}

		// Kategorien filtern aktiviert?
		if ( 'yes' !== get_option( 'symbion_eu_filter_categories', 'yes' ) ) {
			return $terms;
		}

		$hidden_categories = get_option( 'symbion_eu_hidden_categories', array() );
		if ( empty( $hidden_categories ) || ! is_array( $hidden_categories ) ) {
			return $terms;
		}

		// Ausgewählte Kategorien entfernen
		return array_filter( $terms, function( $term ) use ( $hidden_categories ) {
			return ! in_array( $term->term_id, $hidden_categories, true );
		} );
	}
}

