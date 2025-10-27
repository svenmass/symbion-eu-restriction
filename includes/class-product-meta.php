<?php
/**
 * Produkt-Meta-Verwaltung
 *
 * @package SymbionEURestriction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Produkt-Meta-Klasse
 */
class Symbion_EU_Product_Meta {

	/**
	 * Singleton-Instanz
	 *
	 * @var Symbion_EU_Product_Meta
	 */
	private static $instance = null;

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return Symbion_EU_Product_Meta
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
		// Meta-Feld registrieren
		add_action( 'init', array( $this, 'register_meta' ) );

		// Block Editor Integration
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_product_field' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_field' ) );

		// REST API Support (Block Editor)
		add_action( 'rest_api_init', array( $this, 'register_rest_field' ) );
	}

	/**
	 * Meta-Feld registrieren
	 */
	public function register_meta() {
		register_post_meta(
			'product',
			Symbion_EU_Core::META_KEY_SET,
			array(
				'type'              => 'string',
				'description'       => __( 'Ist Set-Produkt (EU-Restriktion)', 'symbion-eu-restriction' ),
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
				'auth_callback'     => function() {
					return current_user_can( 'edit_products' );
				},
			)
		);
	}

	/**
	 * REST-Feld registrieren (für Block Editor)
	 */
	public function register_rest_field() {
		register_rest_field(
			'product',
			'symbion_is_set',
			array(
				'get_callback'    => function( $object ) {
					return get_post_meta( $object['id'], Symbion_EU_Core::META_KEY_SET, true ) === '1';
				},
				'update_callback' => function( $value, $object ) {
					update_post_meta( $object->ID, Symbion_EU_Core::META_KEY_SET, $value ? '1' : '' );
				},
				'schema'          => array(
					'type'        => 'boolean',
					'description' => __( 'Ist Set-Produkt', 'symbion-eu-restriction' ),
					'context'     => array( 'view', 'edit' ),
				),
			)
		);
	}

	/**
	 * Produkt-Feld hinzufügen (Classic & Block Editor)
	 */
	public function add_product_field() {
		global $post;

		$is_set = get_post_meta( $post->ID, Symbion_EU_Core::META_KEY_SET, true ) === '1';

		echo '<div class="options_group symbion-eu-field">';
		
		woocommerce_wp_checkbox(
			array(
				'id'          => Symbion_EU_Core::META_KEY_SET,
				'label'       => __( 'Ist Set (Non-EU Restriktion)', 'symbion-eu-restriction' ),
				'description' => __( 'Dieses Produkt wird außerhalb der EU nicht angezeigt', 'symbion-eu-restriction' ),
				'desc_tip'    => false,
				'value'       => $is_set ? 'yes' : 'no',
			)
		);

		echo '</div>';
	}

	/**
	 * Produkt-Feld speichern
	 *
	 * @param int $post_id Produkt-ID
	 */
	public function save_product_field( $post_id ) {
		$value = isset( $_POST[ Symbion_EU_Core::META_KEY_SET ] ) && 'yes' === $_POST[ Symbion_EU_Core::META_KEY_SET ] ? '1' : '';
		update_post_meta( $post_id, Symbion_EU_Core::META_KEY_SET, $value );

		// Cache invalidieren
		symbion_eu_restriction()->get_component( 'core' )->invalidate_cache();
	}

	/**
	 * Checkbox-Wert sanitieren
	 *
	 * @param mixed $value Wert
	 * @return string
	 */
	public function sanitize_checkbox( $value ) {
		return $value ? '1' : '';
	}
}

