<?php
/**
 * Classe principal do plugin.
 *
 * @package SicaProductModels\Core
 */

namespace SicaProductModels\Core;

use SicaProductModels\Admin\Assets as AdminAssets;
use SicaProductModels\Admin\ProductModelsPanel;
use SicaProductModels\Admin\ProductModelsSaveHandler;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Plugin.
 */
class Plugin {

	/**
	 * Loader central do plugin.
	 *
	 * @var Loader
	 */
	protected Loader $loader;

	/**
	 * Construtor.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->set_locale();
		$this->define_core_hooks();
		$this->define_admin_hooks();
		$this->define_front_hooks();
	}

	/**
	 * Carrega dependências principais.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		require_once SPM_INC_PATH . 'Helpers/Sanitizer.php';
		require_once SPM_INC_PATH . 'Helpers/Functions.php';

		require_once SPM_INC_PATH . 'Domain/ProductModel.php';
		require_once SPM_INC_PATH . 'Repository/ProductModelRepository.php';
		require_once SPM_INC_PATH . 'Service/ProductModelService.php';

		require_once SPM_INC_PATH . 'Admin/Assets.php';
		require_once SPM_INC_PATH . 'Admin/MetaBoxView.php';
		require_once SPM_INC_PATH . 'Admin/ProductModelsPanel.php';
		require_once SPM_INC_PATH . 'Admin/ProductModelsSaveHandler.php';

		$this->require_if_exists( SPM_INC_PATH . 'Front/Assets.php' );
		$this->require_if_exists( SPM_INC_PATH . 'Front/Renderer.php' );
		$this->require_if_exists( SPM_INC_PATH . 'Front/ProductPage.php' );
		$this->require_if_exists( SPM_INC_PATH . 'Front/Shortcodes.php' );

		$this->loader = new Loader();
	}

	/**
	 * Faz require de um arquivo se ele existir.
	 *
	 * @param string $file Caminho completo.
	 * @return void
	 */
	private function require_if_exists( string $file ): void {
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}

	/**
	 * Registra textdomain.
	 *
	 * @return void
	 */
	private function set_locale(): void {
		$this->loader->add_action( 'plugins_loaded', $this, 'load_textdomain' );
	}

	/**
	 * Registra hooks centrais.
	 *
	 * @return void
	 */
	private function define_core_hooks(): void {
		$this->loader->add_action( 'admin_notices', $this, 'maybe_show_missing_woocommerce_notice' );
		$this->loader->add_action( 'init', $this, 'maybe_upgrade_database' );
	}

	/**
	 * Registra hooks do admin.
	 *
	 * @return void
	 */
	private function define_admin_hooks(): void {
		if ( ! is_admin() ) {
			return;
		}

		$admin_assets = new AdminAssets();
		$admin_assets->hooks();

		$panel = new ProductModelsPanel();
		$panel->hooks();

		$save_handler = new ProductModelsSaveHandler();
		$save_handler->hooks();
	}

	/**
	 * Registra hooks do front.
	 *
	 * @return void
	 */
	private function define_front_hooks(): void {
		if ( is_admin() ) {
			return;
		}

		if ( class_exists( '\SicaProductModels\Front\Assets' ) ) {
			$front_assets = new \SicaProductModels\Front\Assets();
			$front_assets->hooks();
		}

		if ( class_exists( '\SicaProductModels\Front\ProductPage' ) ) {
			$product_page = new \SicaProductModels\Front\ProductPage();
			$product_page->hooks();
		}

		if ( class_exists( '\SicaProductModels\Front\Shortcodes' ) ) {
			$shortcodes = new \SicaProductModels\Front\Shortcodes();
			$shortcodes->hooks();
		}
	}

	/**
	 * Executa o loader.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->loader->run();
	}

	/**
	 * Carrega traduções do plugin.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'sica-product-models',
			false,
			dirname( SPM_BASENAME ) . '/languages/'
		);
	}

	/**
	 * Verifica se WooCommerce está ativo.
	 *
	 * @return bool
	 */
	public function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Exibe aviso no admin se WooCommerce não estiver ativo.
	 *
	 * @return void
	 */
	public function maybe_show_missing_woocommerce_notice(): void {
		if ( $this->is_woocommerce_active() ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'Sica Product Models requer que o WooCommerce esteja instalado e ativo.', 'sica-product-models' )
		);
	}

	/**
	 * Atualiza schema do banco caso necessário.
	 *
	 * @return void
	 */
	public function maybe_upgrade_database(): void {
		$installed_version = get_option( Installer::SCHEMA_VERSION_OPTION );

		if ( Installer::SCHEMA_VERSION !== $installed_version ) {
			Installer::install();
		}
	}
}