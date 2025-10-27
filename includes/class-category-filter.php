<?php
/**
 * Kategorie-Filter
 *
 * @package SymbionEURestriction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Kategorie-Filter-Klasse
 * 
 * Versteckt Kategorien, die ausschließlich Set-Produkte enthalten
 */
class Symbion_EU_Category_Filter {

	/**
	 * Singleton-Instanz
	 *
	 * @var Symbion_EU_Category_Filter
	 */
	private static $instance = null;

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return Symbion_EU_Category_Filter
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
		$this->register_hooks();
	}

	/**
	 * Hooks registrieren
	 */
	private function register_hooks() {
		// Kategorien aus Menüs filtern
		add_filter( 'wp_get_nav_menu_items', array( $this, 'filter_menu_items' ), 20 );
		
		// Kategorien aus Widgets filtern
		add_filter( 'widget_categories_args', array( $this, 'filter_widget_categories' ), 20 );
		add_filter( 'widget_product_categories_args', array( $this, 'filter_widget_categories' ), 20 );
		
		// get_terms filtern (allgemein)
		add_filter( 'get_terms', array( $this, 'filter_terms' ), 20, 4 );
		
		// Kategorie-Archiv blockieren
		add_action( 'template_redirect', array( $this, 'block_category_archive' ) );
	}

	/**
	 * Menü-Items filtern
	 *
	 * @param array $items Menü-Items
	 * @return array
	 */
	public function filter_menu_items( $items ) {
		if ( ! $this->should_filter() || empty( $items ) ) {
			return $items;
		}

		$set_only_categories = $this->get_set_only_category_ids();
		if ( empty( $set_only_categories ) ) {
			return $items;
		}

		// Kategorie-Items filtern
		foreach ( $items as $key => $item ) {
			if ( 'taxonomy' === $item->type && 'product_cat' === $item->object ) {
				if ( in_array( (int) $item->object_id, $set_only_categories, true ) ) {
					unset( $items[ $key ] );
				}
			}
		}

		return array_values( $items );
	}

	/**
	 * Widget-Kategorien filtern
	 *
	 * @param array $args Widget-Argumente
	 * @return array
	 */
	public function filter_widget_categories( $args ) {
		if ( ! $this->should_filter() ) {
			return $args;
		}

		$set_only_categories = $this->get_set_only_category_ids();
		if ( empty( $set_only_categories ) ) {
			return $args;
		}

		// Kategorien ausschließen
		$exclude = isset( $args['exclude'] ) ? $args['exclude'] : '';
		$exclude_array = ! empty( $exclude ) ? explode( ',', $exclude ) : array();
		$exclude_array = array_merge( $exclude_array, $set_only_categories );
		$args['exclude'] = implode( ',', array_unique( $exclude_array ) );

		return $args;
	}

	/**
	 * Terms filtern
	 *
	 * @param array  $terms      Terms
	 * @param array  $taxonomies Taxonomien
	 * @param array  $args       Argumente
	 * @param object $term_query Term-Query
	 * @return array
	 */
	public function filter_terms( $terms, $taxonomies, $args, $term_query ) {
		if ( ! $this->should_filter() || empty( $terms ) ) {
			return $terms;
		}

		// Nur für product_cat
		if ( ! in_array( 'product_cat', (array) $taxonomies, true ) ) {
			return $terms;
		}

		$set_only_categories = $this->get_set_only_category_ids();
		if ( empty( $set_only_categories ) ) {
			return $terms;
		}

		// Terms filtern
		return array_filter(
			$terms,
			function( $term ) use ( $set_only_categories ) {
				if ( ! isset( $term->term_id ) ) {
					return true;
				}
				return ! in_array( (int) $term->term_id, $set_only_categories, true );
			}
		);
	}

	/**
	 * Kategorie-Archiv blockieren
	 */
	public function block_category_archive() {
		if ( ! $this->should_filter() ) {
			return;
		}

		if ( ! is_tax( 'product_cat' ) ) {
			return;
		}

		$term = get_queried_object();
		if ( ! $term || ! isset( $term->term_id ) ) {
			return;
		}

		$set_only_categories = $this->get_set_only_category_ids();
		if ( ! in_array( (int) $term->term_id, $set_only_categories, true ) ) {
			return;
		}

		// 404 anzeigen
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
		include get_query_template( '404' );
		exit;
	}

	/**
	 * Set-Only-Kategorie-IDs abrufen
	 *
	 * @return array
	 */
	private function get_set_only_category_ids() {
		$core = symbion_eu_restriction()->get_component( 'core' );
		return $core ? $core->get_set_only_category_ids() : array();
	}

	/**
	 * Prüfen ob gefiltert werden soll
	 *
	 * @return bool
	 */
	private function should_filter() {
		// Feature aktiviert?
		if ( 'yes' !== get_option( 'symbion_eu_filter_categories', 'yes' ) ) {
			return false;
		}

		$core = symbion_eu_restriction()->get_component( 'core' );
		return $core ? $core->should_filter() : false;
	}
}

