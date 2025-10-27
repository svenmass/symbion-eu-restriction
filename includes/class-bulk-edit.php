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

		// Bulk Actions (Dropdown) - VORSICHTIG implementiert
		add_filter( 'bulk_actions-edit-product', array( $this, 'add_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-product', array( $this, 'handle_bulk_actions' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'bulk_action_notices' ) );

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
	 * Bulk Actions zum Dropdown hinzufügen
	 *
	 * @param array $actions Bulk Actions
	 * @return array
	 */
	public function add_bulk_actions( $actions ) {
		$actions['symbion_mark_as_set']   = __( 'Als Set markieren (Non-EU)', 'symbion-eu-restriction' );
		$actions['symbion_unmark_as_set'] = __( 'Set-Markierung entfernen', 'symbion-eu-restriction' );
		return $actions;
	}

	/**
	 * Bulk Actions verarbeiten
	 *
	 * @param string $redirect_to Redirect-URL
	 * @param string $action      Action-Name
	 * @param array  $post_ids    Post-IDs
	 * @return string
	 */
	public function handle_bulk_actions( $redirect_to, $action, $post_ids ) {
		// Nur unsere Actions verarbeiten
		if ( 'symbion_mark_as_set' !== $action && 'symbion_unmark_as_set' !== $action ) {
			return $redirect_to;
		}

		$is_set = ( 'symbion_mark_as_set' === $action );
		$count  = 0;

		// WICHTIG: Keine WP_Query hier - direkt Meta-Daten ändern
		foreach ( $post_ids as $post_id ) {
			// Nur Produkte verarbeiten
			if ( 'product' !== get_post_type( $post_id ) ) {
				continue;
			}

			// Meta-Wert setzen/löschen (direkt, ohne WC_Product zu laden)
			if ( $is_set ) {
				update_post_meta( $post_id, Symbion_EU_Core::META_KEY_SET, '1' );
			} else {
				delete_post_meta( $post_id, Symbion_EU_Core::META_KEY_SET );
			}
			
			$count++;
		}

		// Cache invalidieren (nur einmal am Ende)
		if ( $count > 0 ) {
			delete_transient( 'symbion_eu_set_product_ids' );
			delete_transient( 'symbion_eu_set_only_categories' );
		}

		// Redirect mit Erfolgs-Nachricht
		$redirect_to = add_query_arg(
			array(
				'symbion_bulk_action' => $action,
				'changed'             => $count,
			),
			$redirect_to
		);

		return $redirect_to;
	}

	/**
	 * Bulk Action Notices anzeigen
	 */
	public function bulk_action_notices() {
		if ( ! isset( $_GET['symbion_bulk_action'] ) || ! isset( $_GET['changed'] ) ) {
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['symbion_bulk_action'] ) );
		$count  = absint( $_GET['changed'] );

		if ( $count === 0 ) {
			return;
		}

		$message = '';
		if ( 'symbion_mark_as_set' === $action ) {
			$message = sprintf(
				/* translators: %d: Anzahl der Produkte */
				_n(
					'%d Produkt als Set markiert (Non-EU Restriktion aktiv).',
					'%d Produkte als Sets markiert (Non-EU Restriktion aktiv).',
					$count,
					'symbion-eu-restriction'
				),
				$count
			);
		} elseif ( 'symbion_unmark_as_set' === $action ) {
			$message = sprintf(
				/* translators: %d: Anzahl der Produkte */
				_n(
					'Set-Markierung von %d Produkt entfernt.',
					'Set-Markierung von %d Produkten entfernt.',
					$count,
					'symbion-eu-restriction'
				),
				$count
			);
		}

		if ( $message ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>✓</strong> ' . esc_html( $message ) . '</p></div>';
		}
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
			
			// Nach Name-Spalte einfügen
			if ( 'name' === $key ) {
				$new_columns['symbion_eu_set'] = __( 'EU Restriktion', 'symbion-eu-restriction' );
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
			echo '<span class="symbion-eu-set-badge" style="display: inline-block; padding: 4px 8px; background: #d63638; color: #fff; border-radius: 3px; font-size: 11px; font-weight: 600; text-transform: uppercase;">Set (Non-EU)</span>';
		} else {
			echo '<span style="color: #8c8f94;">—</span>';
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

