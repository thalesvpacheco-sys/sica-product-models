<?php
/**
 * Shortcodes do front.
 *
 * @package SicaProductModels\Front
 */

namespace SicaProductModels\Front;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Shortcodes.
 */
class Shortcodes {

	/**
	 * Renderer.
	 *
	 * @var Renderer
	 */
	protected Renderer $renderer;

	/**
	 * Construtor.
	 *
	 * @param Renderer|null $renderer Renderer.
	 */
	public function __construct( ?Renderer $renderer = null ) {
		$this->renderer = $renderer ?? new Renderer();
	}

	/**
	 * Registra hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_shortcode( 'sica_product_models', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Renderiza shortcode.
	 *
	 * @param array<string, mixed> $atts Atributos.
	 * @return string
	 */
	public function render_shortcode( array $atts = array() ): string {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return '';
		}

		global $product, $post;

		$product_id = 0;

		if ( $product && method_exists( $product, 'get_id' ) ) {
			$product_id = (int) $product->get_id();
		} elseif ( $post instanceof \WP_Post && 'product' === $post->post_type ) {
			$product_id = (int) $post->ID;
		}

		if ( $product_id <= 0 ) {
			return '';
		}

		return $this->renderer->render( $product_id );
	}
}