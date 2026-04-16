<?php
/**
 * Salvamento dos modelos no produto.
 *
 * @package SicaProductModels\Admin
 */

namespace SicaProductModels\Admin;

use SicaProductModels\Service\ProductModelService;

defined( 'ABSPATH' ) || exit;

/**
 * Classe ProductModelsSaveHandler.
 */
class ProductModelsSaveHandler {

	/**
	 * Service.
	 *
	 * @var ProductModelService
	 */
	protected ProductModelService $service;

	/**
	 * Construtor.
	 *
	 * @param ProductModelService|null $service Service.
	 */
	public function __construct( ?ProductModelService $service = null ) {
		$this->service = $service ?? new ProductModelService();
	}

	/**
	 * Registra hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'save_post_product', array( $this, 'save' ), 20, 2 );
	}

	/**
	 * Salva modelos do produto.
	 *
	 * @param int      $post_id ID do post.
	 * @param \WP_Post $post Post.
	 * @return void
	 */
	public function save( int $post_id, \WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( 'product' !== $post->post_type ) {
			return;
		}

		if ( ! isset( $_POST['spm_models_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['spm_models_nonce'] ) ), 'spm_save_models' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$posted_models = isset( $_POST['spm_models'] ) && is_array( $_POST['spm_models'] )
			? wp_unslash( $_POST['spm_models'] )
			: array();

		$existing_models = $this->service->get_models_by_product_id( $post_id, false );
		$existing_ids    = array();

		foreach ( $existing_models as $existing_model ) {
			$existing_ids[] = $existing_model->get_id();
		}

		$saved_ids = array();
		$order     = 0;

		foreach ( $posted_models as $posted_model ) {
			if ( ! is_array( $posted_model ) ) {
				continue;
			}

			$name = isset( $posted_model['name'] ) ? sanitize_text_field( (string) $posted_model['name'] ) : '';

			if ( '' === $name ) {
				continue;
			}

			$gallery_ids = array();

			if ( isset( $posted_model['gallery_image_ids'] ) && '' !== trim( (string) $posted_model['gallery_image_ids'] ) ) {
				$gallery_csv = (string) $posted_model['gallery_image_ids'];
				$gallery_ids = array_values(
					array_filter(
						array_map(
							'absint',
							array_map(
								'trim',
								explode( ',', $gallery_csv )
							)
						),
						function ( $v ) {
							return $v > 0;
						}
					)
				);
			}

			$short_description_value = '';

			if ( array_key_exists( 'short_description_persisted', $posted_model ) ) {
				$short_description_value = (string) $posted_model['short_description_persisted'];
			} elseif ( isset( $posted_model['short_description'] ) ) {
				$short_description_value = (string) $posted_model['short_description'];
			}

			$model = $this->service->create_model_from_data(
				array(
					'id'                => isset( $posted_model['id'] ) ? absint( $posted_model['id'] ) : 0,
					'product_id'        => $post_id,
					'attribute_term_id' => isset( $posted_model['attribute_term_id'] ) && '' !== (string) $posted_model['attribute_term_id']
						? absint( $posted_model['attribute_term_id'] )
						: null,
					'name'              => $name,
					'slug'              => isset( $posted_model['slug'] ) ? (string) $posted_model['slug'] : '',
					'short_description' => $short_description_value,
					'description'       => isset( $posted_model['description'] ) ? (string) $posted_model['description'] : '',
					'featured_image_id' => isset( $posted_model['featured_image_id'] ) ? absint( $posted_model['featured_image_id'] ) : null,
					'gallery_image_ids' => $gallery_ids,
					'menu_order'        => $order,
					'is_active'         => ! empty( $posted_model['is_active'] ),
				)
			);

			$result = $this->service->save_model( $model );

			if ( ! empty( $result['success'] ) && ! empty( $result['id'] ) ) {
				$saved_ids[] = (int) $result['id'];
			}

			$order++;
		}

		$ids_to_delete = array_diff( $existing_ids, $saved_ids );

		foreach ( $ids_to_delete as $delete_id ) {
			$this->service->delete_model( (int) $delete_id );
		}

		ProductModelService::clear_models_cache( $post_id );
	}
}