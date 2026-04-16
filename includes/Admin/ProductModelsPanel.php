<?php
/**
 * Painel admin dos modelos.
 *
 * @package SicaProductModels\Admin
 */

namespace SicaProductModels\Admin;

use SicaProductModels\Service\ProductModelService;

defined( 'ABSPATH' ) || exit;

/**
 * Classe ProductModelsPanel.
 */
class ProductModelsPanel {

	/**
	 * Service.
	 *
	 * @var ProductModelService
	 */
	protected ProductModelService $service;

	/**
	 * View.
	 *
	 * @var MetaBoxView
	 */
	protected MetaBoxView $view;

	/**
	 * Construtor.
	 *
	 * @param ProductModelService|null $service Service.
	 * @param MetaBoxView|null         $view View.
	 */
	public function __construct( ?ProductModelService $service = null, ?MetaBoxView $view = null ) {
		$this->service = $service ?? new ProductModelService();
		$this->view    = $view ?? new MetaBoxView();
	}

	/**
	 * Registra hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
	}

	/**
	 * Registra meta box nos produtos.
	 *
	 * @return void
	 */
	public function register_meta_box(): void {
		add_meta_box(
			'spm_product_models',
			__( 'Modelos de Produto', 'sica-product-models' ),
			array( $this, 'render_meta_box' ),
			'product',
			'normal',
			'default'
		);
	}

	/**
	 * Renderiza meta box.
	 *
	 * @param \WP_Post $post Post atual.
	 * @return void
	 */
	public function render_meta_box( \WP_Post $post ): void {
		$models          = $this->service->get_models_by_product_id( (int) $post->ID, false );
		$attribute_terms = $this->get_model_attribute_terms( (int) $post->ID );

		$this->view->render(
			array(
				'post_id'         => (int) $post->ID,
				'models'          => $models,
				'attribute_terms' => $attribute_terms,
			)
		);
	}

	/**
	 * Busca termos do atributo "modelo" presentes no produto.
	 *
	 * @param int $product_id ID do produto.
	 * @return array<int, array<string, mixed>>
	 */
	protected function get_model_attribute_terms( int $product_id ): array {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return array();
		}

		$attributes = $product->get_attributes();
		$terms_data = array();

		foreach ( $attributes as $attribute ) {
			if ( ! $attribute->is_taxonomy() ) {
				continue;
			}

			$attribute_name = $attribute->get_name();
			$label          = wc_attribute_label( $attribute_name );

			$normalized_candidates = array(
				sanitize_title( $label ),
				sanitize_title( str_replace( 'pa_', '', $attribute_name ) ),
			);

			if ( ! in_array( 'modelo', $normalized_candidates, true ) && ! in_array( 'modelos', $normalized_candidates, true ) ) {
				continue;
			}

			$terms = wc_get_product_terms(
				$product_id,
				$attribute_name,
				array(
					'fields' => 'all',
				)
			);

			foreach ( $terms as $term ) {
				$terms_data[] = array(
					'id'   => (int) $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
					'tax'  => $attribute_name,
				);
			}
		}

		return $terms_data;
	}
}