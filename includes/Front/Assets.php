<?php
/**
 * Assets do front.
 *
 * @package SicaProductModels\Front
 */

namespace SicaProductModels\Front;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Assets.
 */
class Assets {

	/**
	 * Registra hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enfileira assets no single product.
	 *
	 * @return void
	 */
	public function enqueue(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$front_css_path = SPM_PATH . 'assets/css/front.css';
		$front_js_path  = SPM_PATH . 'assets/js/front.js';

		wp_enqueue_style(
			'spm-front',
			SPM_URL . 'assets/css/front.css',
			array(),
			file_exists( $front_css_path ) ? (string) filemtime( $front_css_path ) : SPM_VERSION
		);

		wp_enqueue_script(
			'spm-front',
			SPM_URL . 'assets/js/front.js',
			array(),
			file_exists( $front_js_path ) ? (string) filemtime( $front_js_path ) : SPM_VERSION,
			true
		);
	}
}