<?php
/**
 * Bulk Edit Support
 *
 * @package SymbionEURestriction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bulk Edit-Klasse
 */
class Symbion_EU_Bulk_Edit {

	/**
	 * Singleton-Instanz
	 *
	 * @var Symbion_EU_Bulk_Edit
	 */
	private static $instance = null;

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return Symbion_EU_Bulk_Edit
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
		// Quick Edit
		add_action( 'woocommerce_product_quick_edit_end', array( $this, 'render_quick_edit' ) );
		add_action( 'woocommerce_product_quick_edit_save', array( $this, 'save_quick_edit' ) );

		// Bulk Edit
		add_action( 'woocommerce_product_bulk_edit_end', array( $this, 'render_bulk_edit' ) );
		add_action( 'woocommerce_product_bulk_edit_save', array( $this, 'save_bulk_edit' ) );

		// Admin-Spalte
		add_filter( 'manage_product_posts_columns', array( $this, 'add_column' ) );
		add_action( 'manage_product_posts_custom_column', array( $this, 'render_column' ), 10, 2 );

		// Admin-Scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Quick Edit-Feld rendern
	 */
	public function render_quick_edit() {
		?>
		<br class="clear" />
		<div class="inline-edit-group">
			<label class="alignleft">
				<span class="title"><?php esc_html_e( 'EU Restriktion', 'symbion-eu-restriction' ); ?></span>
				<span class="input-text-wrap">
					<select name="<?php echo esc_attr( Symbion_EU_Core::META_KEY_SET ); ?>" class="symbion-eu-quick-edit">
						<option value=""><?php esc_html_e( '— Keine Änderung —', 'symbion-eu-restriction' ); ?></option>
						<option value="no"><?php esc_html_e( 'Kein Set', 'symbion-eu-restriction' ); ?></option>
						<option value="yes"><?php esc_html_e( 'Ist Set', 'symbion-eu-restriction' ); ?></option>
					</select>
				</span>
			</label>
		</div>
		<?php
	}

	/**
	 * Bulk Edit-Feld rendern
	 */
	public function render_bulk_edit() {
		?>
		<br class="clear" />
		<div class="inline-edit-group">
			<label class="alignleft">
				<span class="title"><?php esc_html_e( 'EU Restriktion', 'symbion-eu-restriction' ); ?></span>
				<span class="input-text-wrap">
					<select name="<?php echo esc_attr( Symbion_EU_Core::META_KEY_SET ); ?>" class="symbion-eu-bulk-edit">
						<option value=""><?php esc_html_e( '— Keine Änderung —', 'symbion-eu-restriction' ); ?></option>
						<option value="no"><?php esc_html_e( 'Kein Set', 'symbion-eu-restriction' ); ?></option>
						<option value="yes"><?php esc_html_e( 'Ist Set', 'symbion-eu-restriction' ); ?></option>
					</select>
				</span>
			</label>
		</div>
		<?php
	}

	/**
	 * Quick Edit speichern
	 *
	 * @param WC_Product $product Produkt
	 */
	public function save_quick_edit( $product ) {
		if ( ! isset( $_REQUEST[ Symbion_EU_Core::META_KEY_SET ] ) ) {
			return;
		}

		$value = sanitize_text_field( wp_unslash( $_REQUEST[ Symbion_EU_Core::META_KEY_SET ] ) );
		
		if ( 'yes' === $value ) {
			$product->update_meta_data( Symbion_EU_Core::META_KEY_SET, '1' );
		} elseif ( 'no' === $value ) {
			$product->update_meta_data( Symbion_EU_Core::META_KEY_SET, '' );
		}

		$product->save();
		
		// Cache invalidieren
		symbion_eu_restriction()->get_component( 'core' )->invalidate_cache();
	}

	/**
	 * Bulk Edit speichern
	 *
	 * @param WC_Product $product Produkt
	 */
	public function save_bulk_edit( $product ) {
		$this->save_quick_edit( $product );
	}

	/**
	 * Admin-Spalte hinzufügen
	 *
	 * @param array $columns Spalten
	 * @return array
	 */
	public function add_column( $columns ) {
		$new_columns = array();
		
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			
			// Nach Thumbnail einfügen
			if ( 'thumb' === $key ) {
				$new_columns['symbion_eu_set'] = '<span class="dashicons dashicons-admin-site-alt3" title="' . esc_attr__( 'EU Restriktion', 'symbion-eu-restriction' ) . '"></span>';
			}
		}
		
		return $new_columns;
	}

	/**
	 * Admin-Spalte rendern
	 *
	 * @param string $column  Spalten-Name
	 * @param int    $post_id Post-ID
	 */
	public function render_column( $column, $post_id ) {
		if ( 'symbion_eu_set' !== $column ) {
			return;
		}

		$core = symbion_eu_restriction()->get_component( 'core' );
		if ( ! $core ) {
			return;
		}

		if ( $core->is_product_set( $post_id ) ) {
			echo '<span class="dashicons dashicons-yes-alt" style="color: #d63638;" title="' . esc_attr__( 'Ist Set (EU-Restriktion aktiv)', 'symbion-eu-restriction' ) . '"></span>';
		} else {
			echo '<span class="dashicons dashicons-minus" style="color: #dcdcde;" title="' . esc_attr__( 'Kein Set', 'symbion-eu-restriction' ) . '"></span>';
		}
	}

	/**
	 * Admin-Scripts laden
	 *
	 * @param string $hook Hook-Name
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'edit.php' !== $hook || ! isset( $_GET['post_type'] ) || 'product' !== $_GET['post_type'] ) {
			return;
		}

		wp_enqueue_script(
			'symbion-eu-bulk-edit',
			SYMBION_EU_URL . 'assets/js/bulk-edit.js',
			array( 'jquery' ),
			SYMBION_EU_VERSION,
			true
		);
	}
}

