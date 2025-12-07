<?php
/**
 * Plugin Name: Transcriptions Sync
 * Plugin URI: 
 * Description: Manages musical transcriptions synced from Contentful via Make.com
 * Version: 1.0.0
 * Author: NPC Agency
 * Author URI: https://npc-agency.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: transcriptions-sync
 * Domain Path: /languages
 *
 * @package TranscriptionsSync
 */

namespace TranscriptionsSync;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'TRANSCRIPTIONS_SYNC_VERSION', '1.0.0' );
define( 'TRANSCRIPTIONS_SYNC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TRANSCRIPTIONS_SYNC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TRANSCRIPTIONS_SYNC_PLUGIN_FILE', __FILE__ );

/**
 * Main plugin class
 */
class Plugin {

	/**
	 * Single instance of the plugin
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Database handler
	 *
	 * @var Database
	 */
	public $database;

	/**
	 * API handler
	 *
	 * @var API
	 */
	public $api;

	/**
	 * Renderer handler
	 *
	 * @var Renderer
	 */
	public $renderer;

	/**
	 * Admin handler
	 *
	 * @var Admin
	 */
	public $admin;

	/**
	 * Get plugin instance
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load plugin dependencies
	 */
	private function load_dependencies() {
		require_once TRANSCRIPTIONS_SYNC_PLUGIN_DIR . 'includes/class-database.php';
		require_once TRANSCRIPTIONS_SYNC_PLUGIN_DIR . 'includes/class-api.php';
		require_once TRANSCRIPTIONS_SYNC_PLUGIN_DIR . 'includes/class-renderer.php';
		require_once TRANSCRIPTIONS_SYNC_PLUGIN_DIR . 'includes/class-admin.php';
	}

	/**
	 * Initialize WordPress hooks
	 */
	private function init_hooks() {
		register_activation_hook( TRANSCRIPTIONS_SYNC_PLUGIN_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( TRANSCRIPTIONS_SYNC_PLUGIN_FILE, array( $this, 'deactivate' ) );

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'rest_api_init', array( $this, 'init_rest_api' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		// Register taxonomy to ensure rewrite rules are created.
		$this->register_taxonomy();

		// Flush rewrite rules.
		flush_rewrite_rules();

		// Log activation.
		error_log( 'Transcriptions Sync plugin activated' );
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Flush rewrite rules.
		flush_rewrite_rules();

		// Log deactivation.
		error_log( 'Transcriptions Sync plugin deactivated' );
	}

	/**
	 * Initialize plugin
	 */
	public function init() {
		// Register Maqam taxonomy.
		$this->register_taxonomy();

		// Initialize components.
		$this->database = new Database();
		$this->renderer = new Renderer();
		$this->admin    = new Admin();
	}

	/**
	 * Initialize REST API
	 */
	public function init_rest_api() {
		if ( ! isset( $this->database ) ) {
			$this->database = new Database();
		}
		$this->api = new API( $this->database );
	}

	/**
	 * Register Maqam taxonomy
	 */
	private function register_taxonomy() {
		$labels = array(
			'name'              => _x( 'Maqams', 'taxonomy general name', 'transcriptions-sync' ),
			'singular_name'     => _x( 'Maqam', 'taxonomy singular name', 'transcriptions-sync' ),
			'search_items'      => __( 'Search Maqams', 'transcriptions-sync' ),
			'all_items'         => __( 'All Maqams', 'transcriptions-sync' ),
			'parent_item'       => __( 'Parent Maqam', 'transcriptions-sync' ),
			'parent_item_colon' => __( 'Parent Maqam:', 'transcriptions-sync' ),
			'edit_item'         => __( 'Edit Maqam', 'transcriptions-sync' ),
			'update_item'       => __( 'Update Maqam', 'transcriptions-sync' ),
			'add_new_item'      => __( 'Add New Maqam', 'transcriptions-sync' ),
			'new_item_name'     => __( 'New Maqam Name', 'transcriptions-sync' ),
			'menu_name'         => __( 'Maqams', 'transcriptions-sync' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'maqam' ),
		);

		register_taxonomy( 'maqam', array( 'page' ), $args );
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets() {
		wp_enqueue_style(
			'transcriptions-sync-frontend',
			TRANSCRIPTIONS_SYNC_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			TRANSCRIPTIONS_SYNC_VERSION
		);

		wp_enqueue_script(
			'transcriptions-sync-frontend',
			TRANSCRIPTIONS_SYNC_PLUGIN_URL . 'assets/js/frontend.js',
			array(),
			TRANSCRIPTIONS_SYNC_VERSION,
			true
		);
	}
}

/**
 * Initialize the plugin
 */
function transcriptions_sync_init() {
	return Plugin::get_instance();
}

// Start the plugin.
add_action( 'plugins_loaded', __NAMESPACE__ . '\transcriptions_sync_init' );
