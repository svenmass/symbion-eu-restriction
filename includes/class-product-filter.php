<?php
/**
 * Produkt-Filter
 *
 * @package SymbionEURestriction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Produkt-Filter-Klasse
 */
class Symbion_EU_Product_Filter {

	/**
	 * Singleton-Instanz
	 *
	 * @var Symbion_EU_Product_Filter
	 */
	private static $instance = null;

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return Symbion_EU_Product_Filter
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
		// Nur im Frontend
		if ( ! is_admin() || wp_doing_ajax() ) {
			$this->register_hooks();
		}
	}

	/**
	 * Hooks registrieren
	 */
	private function register_hooks() {
		// Haupt-Queries filtern
		add_action( 'pre_get_posts', array( $this, 'filter_queries' ), 20 );
		
		// WooCommerce-spezifische Hooks
		add_action( 'woocommerce_product_query', array( $this, 'filter_wc_query' ), 20 );
		add_filter( 'woocommerce_product_is_visible', array( $this, 'filter_visibility' ), 20, 2 );
		
		// Einzelprodukt-Zugriff blockieren
		add_action( 'template_redirect', array( $this, 'block_single_product' ) );
		
		// Related, Upsells, Cross-Sells
		add_filter( 'woocommerce_related_products', array( $this, 'filter_product_ids' ), 20 );
		add_filter( 'woocommerce_cross_sells_ids', array( $this, 'filter_product_ids' ), 20 );
		add_filter( 'woocommerce_upsell_ids', array( $this, 'filter_product_ids' ), 20 );
		
		// Shortcodes
		add_filter( 'woocommerce_shortcode_products_query', array( $this, 'filter_shortcode_query' ), 20, 3 );
		
		// REST API
		add_filter( 'woocommerce_rest_prepare_product_object', array( $this, 'filter_rest_api' ), 20, 3 );
		add_filter( 'woocommerce_rest_product_object_query', array( $this, 'filter_rest_query' ), 20, 2 );
	}

	/**
	 * Queries filtern
	 *
	 * @param WP_Query $query Query-Objekt
	 */
	public function filter_queries( $query ) {
		if ( ! $this->should_filter() ) {
			return;
		}

		// Nur Produkt-Queries
		$post_type = $query->get( 'post_type' );
		if ( 'product' !== $post_type && ! $query->is_search() ) {
			return;
		}

		// Set-IDs ausschließen
		$this->exclude_sets( $query );
	}

	/**
	 * WooCommerce-Query filtern
	 *
	 * @param WP_Query $query Query-Objekt
	 */
	public function filter_wc_query( $query ) {
		if ( ! $this->should_filter() ) {
			return;
		}

		$this->exclude_sets( $query );
	}

	/**
	 * Produkt-Sichtbarkeit filtern
	 *
	 * @param bool       $visible Sichtbarkeit
	 * @param int|object $product Produkt
	 * @return bool
	 */
	public function filter_visibility( $visible, $product ) {
		if ( ! $this->should_filter() ) {
			return $visible;
		}

		$core = symbion_eu_restriction()->get_component( 'core' );
		if ( $core && $core->is_product_set( $product ) ) {
			return false;
		}

		return $visible;
	}

	/**
	 * Einzelprodukt-Zugriff blockieren
	 */
	public function block_single_product() {
		if ( ! $this->should_filter() ) {
			return;
		}

		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		global $post;
		if ( ! $post || 'product' !== $post->post_type ) {
			return;
		}

		$core = symbion_eu_restriction()->get_component( 'core' );
		if ( ! $core || ! $core->is_product_set( $post->ID ) ) {
			return;
		}

		// Weiterleitung oder 404
		$redirect_type = get_option( 'symbion_eu_redirect_type', '404' );

		if ( '404' === $redirect_type ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			nocache_headers();
			include get_query_template( '404' );
			exit;
		}

		// Zur Shop-Seite weiterleiten
		$redirect_url = wc_get_page_permalink( 'shop' );
		if ( empty( $redirect_url ) ) {
			$redirect_url = home_url( '/' );
		}
		
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Produkt-IDs filtern
	 *
	 * @param array $ids Produkt-IDs
	 * @return array
	 */
	public function filter_product_ids( $ids ) {
		if ( ! $this->should_filter() || empty( $ids ) ) {
			return $ids;
		}

		$core    = symbion_eu_restriction()->get_component( 'core' );
		$set_ids = $core ? $core->get_set_product_ids() : array();

		return array_diff( $ids, $set_ids );
	}

	/**
	 * Shortcode-Query filtern
	 *
	 * @param array  $args Query-Args
	 * @param array  $atts Shortcode-Attribute
	 * @param string $type Shortcode-Typ
	 * @return array
	 */
	public function filter_shortcode_query( $args, $atts, $type ) {
		if ( ! $this->should_filter() ) {
			return $args;
		}

		$core    = symbion_eu_restriction()->get_component( 'core' );
		$set_ids = $core ? $core->get_set_product_ids() : array();

		if ( ! empty( $set_ids ) ) {
			$existing = isset( $args['post__not_in'] ) ? $args['post__not_in'] : array();
			$args['post__not_in'] = array_merge( $existing, $set_ids );
		}

		return $args;
	}

	/**
	 * REST API Response filtern
	 *
	 * @param WP_REST_Response $response Response
	 * @param WC_Product       $product  Produkt
	 * @param WP_REST_Request  $request  Request
	 * @return WP_REST_Response|WP_Error
	 */
	public function filter_rest_api( $response, $product, $request ) {
		if ( ! $this->should_filter() ) {
			return $response;
		}

		$core = symbion_eu_restriction()->get_component( 'core' );
		if ( $core && $core->is_product_set( $product ) ) {
			return new WP_Error(
				'product_restricted',
				__( 'Dieses Produkt ist in Ihrer Region nicht verfügbar.', 'symbion-eu-restriction' ),
				array( 'status' => 403 )
			);
		}

		return $response;
	}

	/**
	 * REST API Query filtern
	 *
	 * @param array           $args    Query-Args
	 * @param WP_REST_Request $request Request
	 * @return array
	 */
	public function filter_rest_query( $args, $request ) {
		if ( ! $this->should_filter() ) {
			return $args;
		}

		$core    = symbion_eu_restriction()->get_component( 'core' );
		$set_ids = $core ? $core->get_set_product_ids() : array();

		if ( ! empty( $set_ids ) ) {
			$existing = isset( $args['post__not_in'] ) ? $args['post__not_in'] : array();
			$args['post__not_in'] = array_merge( $existing, $set_ids );
		}

		return $args;
	}

	/**
	 * Sets von Query ausschließen
	 *
	 * @param WP_Query $query Query-Objekt
	 */
	private function exclude_sets( $query ) {
		$core    = symbion_eu_restriction()->get_component( 'core' );
		$set_ids = $core ? $core->get_set_product_ids() : array();

		if ( ! empty( $set_ids ) ) {
			$existing = $query->get( 'post__not_in' );
			if ( ! is_array( $existing ) ) {
				$existing = array();
			}
			$query->set( 'post__not_in', array_merge( $existing, $set_ids ) );
		}
	}

	/**
	 * Prüfen ob gefiltert werden soll
	 *
	 * @return bool
	 */
	private function should_filter() {
		$core = symbion_eu_restriction()->get_component( 'core' );
		return $core ? $core->should_filter() : false;
	}
}

