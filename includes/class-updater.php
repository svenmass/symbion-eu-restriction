<?php
/**
 * Auto-Updater für GitHub Releases
 *
 * @package SymbionEURestriction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Updater-Klasse
 */
class Symbion_EU_Updater {

	/**
	 * GitHub Repository
	 *
	 * @var string
	 */
	private $repo = 'svenmass/symbion-eu-restriction';

	/**
	 * Plugin-Slug
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Plugin-Datei
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Aktuelle Version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Singleton-Instanz
	 *
	 * @var Symbion_EU_Updater
	 */
	private static $instance = null;

	/**
	 * Singleton-Instanz abrufen
	 *
	 * @return Symbion_EU_Updater
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
		$this->plugin_slug = plugin_basename( SYMBION_EU_FILE );
		$this->plugin_file = SYMBION_EU_FILE;
		$this->version     = SYMBION_EU_VERSION;

		// Update-Hooks
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
	}

	/**
	 * GitHub Release-Infos abrufen
	 *
	 * @return object|false
	 */
	private function get_release_info() {
		$transient_key = 'symbion_eu_github_release';
		$cached        = get_transient( $transient_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$url      = "https://api.github.com/repos/{$this->repo}/releases/latest";
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( ! $data || ! isset( $data->tag_name ) ) {
			return false;
		}

		// Cache für 12 Stunden
		set_transient( $transient_key, $data, 12 * HOUR_IN_SECONDS );

		return $data;
	}

	/**
	 * Update prüfen
	 *
	 * @param object $transient Transient-Objekt
	 * @return object
	 */
	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_release_info();
		if ( ! $release ) {
			return $transient;
		}

		// Version vergleichen (entferne 'v' Prefix)
		$latest_version = ltrim( $release->tag_name, 'v' );

		if ( version_compare( $this->version, $latest_version, '<' ) ) {
			$plugin_data = array(
				'slug'        => dirname( $this->plugin_slug ),
				'new_version' => $latest_version,
				'url'         => "https://github.com/{$this->repo}",
				'package'     => $this->get_download_url( $release ),
				'tested'      => '6.8',
				'requires'    => '6.0',
			);

			$transient->response[ $this->plugin_slug ] = (object) $plugin_data;
		}

		return $transient;
	}

	/**
	 * Download-URL aus Release extrahieren
	 *
	 * @param object $release Release-Objekt
	 * @return string
	 */
	private function get_download_url( $release ) {
		// Suche nach ZIP-Asset
		if ( isset( $release->assets ) && is_array( $release->assets ) ) {
			foreach ( $release->assets as $asset ) {
				if ( isset( $asset->name ) && strpos( $asset->name, '.zip' ) !== false ) {
					return $asset->browser_download_url;
				}
			}
		}

		// Fallback: Zipball-URL
		return isset( $release->zipball_url ) ? $release->zipball_url : '';
	}

	/**
	 * Plugin-Informationen bereitstellen
	 *
	 * @param false|object|array $result API-Result
	 * @param string             $action API-Action
	 * @param object             $args   API-Args
	 * @return false|object|array
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || dirname( $this->plugin_slug ) !== $args->slug ) {
			return $result;
		}

		$release = $this->get_release_info();
		if ( ! $release ) {
			return $result;
		}

		$plugin_info = array(
			'name'          => 'Symbion EU Restriction',
			'slug'          => dirname( $this->plugin_slug ),
			'version'       => ltrim( $release->tag_name, 'v' ),
			'author'        => '<a href="https://symbion.dev">symbion.dev</a>',
			'homepage'      => "https://github.com/{$this->repo}",
			'requires'      => '6.0',
			'tested'        => '6.8',
			'downloaded'    => 0,
			'last_updated'  => $release->published_at,
			'sections'      => array(
				'description' => 'Intelligente Geo-Filterung für WooCommerce Set-Produkte. Blendet Set-Produkte und Kategorien für Non-EU Besucher automatisch aus.',
				'changelog'   => $this->parse_changelog( $release ),
			),
			'download_link' => $this->get_download_url( $release ),
		);

		return (object) $plugin_info;
	}

	/**
	 * Changelog aus Release-Body parsen
	 *
	 * @param object $release Release-Objekt
	 * @return string
	 */
	private function parse_changelog( $release ) {
		if ( ! isset( $release->body ) || empty( $release->body ) ) {
			return '<p>Siehe <a href="https://github.com/' . $this->repo . '/releases" target="_blank">GitHub Releases</a> für Details.</p>';
		}

		// Markdown zu HTML (basic)
		$changelog = wp_kses_post( $release->body );
		$changelog = wpautop( $changelog );

		return $changelog;
	}

	/**
	 * Nach Installation
	 *
	 * @param bool  $response   Installation Response
	 * @param array $hook_extra Hook Extra Data
	 * @param array $result     Installation Result
	 * @return bool
	 */
	public function after_install( $response, $hook_extra, $result ) {
		global $wp_filesystem;

		// Prüfen ob es unser Plugin ist
		$plugin = isset( $hook_extra['plugin'] ) ? $hook_extra['plugin'] : '';
		if ( $plugin !== $this->plugin_slug ) {
			return $response;
		}

		// Korrekten Plugin-Ordner-Namen sicherstellen
		$proper_destination = WP_PLUGIN_DIR . '/' . dirname( $this->plugin_slug );
		$wp_filesystem->move( $result['destination'], $proper_destination );
		$result['destination'] = $proper_destination;

		// Plugin reaktivieren wenn es vorher aktiv war
		if ( is_plugin_active( $this->plugin_slug ) ) {
			activate_plugin( $this->plugin_slug );
		}

		return $response;
	}
}

