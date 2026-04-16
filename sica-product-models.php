<?php
/**
 * Plugin Name: Sica Product Models
 * Plugin URI: https://sica.com.br
 * Description: Plugin próprio para gerenciar modelos de produto no WooCommerce.
 * Version: 0.1.0
 * Author: Grifo Agency
 * Author URI: https://grifo.agency
 * Text Domain: sica-product-models
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 *
 * @package SicaProductModels
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SPM_VERSION' ) ) {
	define( 'SPM_VERSION', '0.1.0' );
}

if ( ! defined( 'SPM_FILE' ) ) {
	define( 'SPM_FILE', __FILE__ );
}

if ( ! defined( 'SPM_BASENAME' ) ) {
	define( 'SPM_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'SPM_PATH' ) ) {
	define( 'SPM_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SPM_URL' ) ) {
	define( 'SPM_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'SPM_INC_PATH' ) ) {
	define( 'SPM_INC_PATH', SPM_PATH . 'includes/' );
}

require_once SPM_INC_PATH . 'Core/Loader.php';
require_once SPM_INC_PATH . 'Core/Installer.php';
require_once SPM_INC_PATH . 'Core/Activator.php';
require_once SPM_INC_PATH . 'Core/Deactivator.php';
require_once SPM_INC_PATH . 'Core/Plugin.php';

/**
 * Declara compatibilidade com recursos do WooCommerce.
 *
 * @return void
 */
function spm_declare_woocommerce_compatibility(): void {
	if ( ! class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		return;
	}

	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
		'custom_order_tables',
		SPM_FILE,
		true
	);

	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
		'cart_checkout_blocks',
		SPM_FILE,
		true
	);
}

add_action( 'before_woocommerce_init', 'spm_declare_woocommerce_compatibility' );

/**
 * Executa na ativação do plugin.
 *
 * @return void
 */
function spm_activate(): void {
	\SicaProductModels\Core\Activator::activate();
}

/**
 * Executa na desativação do plugin.
 *
 * @return void
 */
function spm_deactivate(): void {
	\SicaProductModels\Core\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'spm_activate' );
register_deactivation_hook( __FILE__, 'spm_deactivate' );

/**
 * Inicializa o plugin.
 *
 * @return void
 */
function spm_run(): void {
	$plugin = new \SicaProductModels\Core\Plugin();
	$plugin->run();
}

spm_run();