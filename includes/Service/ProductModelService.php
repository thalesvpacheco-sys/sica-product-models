<?php
/**
 * Regras de negócio dos modelos de produto.
 *
 * @package SicaProductModels\Service
 */

namespace SicaProductModels\Service;

use SicaProductModels\Domain\ProductModel;
use SicaProductModels\Repository\ProductModelRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Classe ProductModelService.
 */
class ProductModelService {

	/**
	 * Repositório.
	 *
	 * @var ProductModelRepository
	 */
	protected ProductModelRepository $repository;

	/**
	 * Construtor.
	 *
	 * @param ProductModelRepository|null $repository Repositório.
	 */
	public function __construct( ?ProductModelRepository $repository = null ) {
		$this->repository = $repository ?? new ProductModelRepository();
	}

	/**
	 * Lista modelos por produto.
	 *
	 * @param int  $product_id ID do produto.
	 * @param bool $active_only Apenas ativos.
	 * @return array<int, ProductModel>
	 */
	public function get_models_by_product_id( int $product_id, bool $active_only = false ): array {
		if ( $product_id <= 0 ) {
			return array();
		}

		return $this->repository->find_by_product_id( $product_id, $active_only );
	}

	/**
	 * Retorna modelo ativo inicial do produto.
	 *
	 * @param int $product_id ID do produto.
	 * @return ProductModel|null
	 */
	public function get_default_model_by_product_id( int $product_id ): ?ProductModel {
		$models = $this->get_models_by_product_id( $product_id, true );

		if ( empty( $models ) ) {
			return null;
		}

		return $models[0];
	}

	/**
	 * Busca um modelo por ID.
	 *
	 * @param int $id ID.
	 * @return ProductModel|null
	 */
	public function get_model( int $id ): ?ProductModel {
		if ( $id <= 0 ) {
			return null;
		}

		return $this->repository->find( $id );
	}

	/**
	 * Busca um modelo por slug dentro do produto.
	 *
	 * @param int    $product_id ID do produto.
	 * @param string $slug Slug.
	 * @return ProductModel|null
	 */
	public function get_model_by_slug( int $product_id, string $slug ): ?ProductModel {
		$slug = sanitize_title( $slug );

		if ( $product_id <= 0 || '' === $slug ) {
			return null;
		}

		return $this->repository->find_by_product_slug( $product_id, $slug );
	}

	/**
	 * Retorna o slug solicitado via query string.
	 *
	 * @return string
	 */
	public function get_requested_model_slug(): string {
		if ( ! isset( $_GET['spm_model'] ) ) {
			return '';
		}

		return sanitize_title( wp_unslash( (string) $_GET['spm_model'] ) );
	}

	/**
	 * Busca um modelo ativo por slug.
	 *
	 * @param int    $product_id ID do produto.
	 * @param string $slug Slug do modelo.
	 * @return ProductModel|null
	 */
	public function get_active_model_by_slug( int $product_id, string $slug ): ?ProductModel {
		$model = $this->get_model_by_slug( $product_id, $slug );

		if ( ! $model || ! $model->is_active() ) {
			return null;
		}

		return $model;
	}

	/**
	 * Busca o modelo ativo referente à requisição atual.
	 *
	 * @param int $product_id ID do produto.
	 * @return ProductModel|null
	 */
	public function get_active_model_for_request( int $product_id ): ?ProductModel {
		$slug = $this->get_requested_model_slug();

		if ( '' === $slug ) {
			return null;
		}

		return $this->get_active_model_by_slug( $product_id, $slug );
	}

	/**
	 * Salva um modelo com compatibilidade para ProductModel ou array.
	 *
	 * @param ProductModel|array<string, mixed> $model_or_data Modelo ou dados.
	 * @return array<string, mixed>
	 */
	public function save_model( $model_or_data ): array {
		if ( $model_or_data instanceof ProductModel ) {
			$model = $model_or_data;
		} elseif ( is_array( $model_or_data ) ) {
			$model = $this->create_model_from_data( $model_or_data );
		} else {
			return array(
				'success' => false,
				'message' => __( 'Dados inválidos para salvar o modelo.', 'sica-product-models' ),
				'id'      => 0,
			);
		}

		$product_id = $model->get_product_id();
		$name       = trim( $model->get_name() );
		$slug       = sanitize_title( $model->get_slug() ?: $name );

		if ( $product_id <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'Produto inválido para o modelo.', 'sica-product-models' ),
				'id'      => 0,
			);
		}

		if ( '' === $name ) {
			return array(
				'success' => false,
				'message' => __( 'O nome do modelo é obrigatório.', 'sica-product-models' ),
				'id'      => 0,
			);
		}

		if ( '' === $slug ) {
			return array(
				'success' => false,
				'message' => __( 'Não foi possível gerar um slug válido para o modelo.', 'sica-product-models' ),
				'id'      => 0,
			);
		}

		if ( $this->repository->slug_exists( $product_id, $slug, $model->get_id() ) ) {
			return array(
				'success' => false,
				'message' => __( 'Já existe um modelo com este slug neste produto.', 'sica-product-models' ),
				'id'      => 0,
			);
		}

		$model->set_name( $name );
		$model->set_slug( $slug );

		$saved_id = $this->repository->save( $model );

		if ( $saved_id <= 0 ) {
			return array(
				'success' => false,
				'message' => __( 'Não foi possível salvar o modelo.', 'sica-product-models' ),
				'id'      => 0,
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Modelo salvo com sucesso.', 'sica-product-models' ),
			'id'      => $saved_id,
		);
	}

	/**
	 * Remove um modelo.
	 *
	 * @param int $id ID do modelo.
	 * @return bool
	 */
	public function delete_model( int $id ): bool {
		return $this->repository->delete( $id );
	}

	/**
	 * Verifica se o conteúdo já possui parágrafos/quebras explícitas.
	 *
	 * @param string $content Conteúdo.
	 * @return bool
	 */
	protected function content_has_paragraph_markup( string $content ): bool {
		return 1 === preg_match( '/<(p|br)\b/i', $content );
	}

	/**
	 * Normaliza conteúdo aplicando sanitização e formatação base (sem shortcodes).
	 *
	 * @param string $content Conteúdo bruto.
	 * @return string
	 */
	private function normalize_content_base( string $content ): string {
		$content = trim( $content );

		if ( '' === $content ) {
			return '';
		}

		$content = wp_kses_post( $content );
		$content = shortcode_unautop( $content );

		if ( ! $this->content_has_paragraph_markup( $content ) ) {
			$content = wpautop( $content );
		}

		return trim( $content );
	}

	/**
	 * Normaliza o conteúdo vindo do editor para armazenamento.
	 *
	 * @param string $content Conteúdo bruto.
	 * @return string
	 */
	protected function normalize_editor_content_for_storage( string $content ): string {
		return $this->normalize_content_base( $content );
	}

	/**
	 * Cria uma instância de modelo a partir de array simples.
	 *
	 * @param array<string, mixed> $data Dados.
	 * @return ProductModel
	 */
	public function create_model_from_data( array $data ): ProductModel {
		$short_description = isset( $data['short_description'] ) ? (string) $data['short_description'] : '';
		$description       = isset( $data['description'] ) ? (string) $data['description'] : '';

		return ProductModel::from_array(
			array(
				'id'                => isset( $data['id'] ) ? (int) $data['id'] : 0,
				'product_id'        => isset( $data['product_id'] ) ? (int) $data['product_id'] : 0,
				'attribute_term_id' => isset( $data['attribute_term_id'] ) ? (int) $data['attribute_term_id'] : null,
				'name'              => isset( $data['name'] ) ? sanitize_text_field( (string) $data['name'] ) : '',
				'slug'              => isset( $data['slug'] ) ? sanitize_title( (string) $data['slug'] ) : '',
				'short_description' => $this->normalize_editor_content_for_storage( $short_description ),
				'description'       => $this->normalize_editor_content_for_storage( $description ),
				'featured_image_id' => isset( $data['featured_image_id'] ) ? absint( $data['featured_image_id'] ) : null,
				'gallery_image_ids' => isset( $data['gallery_image_ids'] ) && is_array( $data['gallery_image_ids'] )
					? array_map( 'absint', $data['gallery_image_ids'] )
					: array(),
				'menu_order'        => isset( $data['menu_order'] ) ? (int) $data['menu_order'] : 0,
				'is_active'         => ! empty( $data['is_active'] ),
			)
		);
	}

	/**
	 * Prepara HTML para exibição no front preservando formatação.
	 *
	 * @param string $content Conteúdo salvo.
	 * @return string
	 */
	protected function prepare_front_html( string $content ): string {
		return do_shortcode( $this->normalize_content_base( $content ) );
	}

	/**
	 * Prepara conteúdo bruto para exibição no front.
	 *
	 * @param string $content Conteúdo bruto.
	 * @return string
	 */
	public function prepare_content_for_front( string $content ): string {
		return $this->prepare_front_html( $content );
	}

	/**
	 * Retorna HTML pronto da descrição breve do modelo.
	 *
	 * @param ProductModel $model Modelo.
	 * @return string
	 */
	public function get_model_short_description_html( ProductModel $model ): string {
		return $this->prepare_front_html( (string) $model->get_short_description() );
	}

	/**
	 * Retorna HTML pronto da descrição completa do modelo.
	 *
	 * @param ProductModel $model Modelo.
	 * @return string
	 */
	public function get_model_description_html( ProductModel $model ): string {
		return $this->prepare_front_html( (string) $model->get_description() );
	}

	/**
	 * Monta payload para o front.
	 *
	 * @param int $product_id ID do produto.
	 * @return array<string, mixed>
	 */
	public function get_front_models_payload( int $product_id ): array {
		$models           = $this->get_models_by_product_id( $product_id, true );
		$prepared         = array();
		$selected_slug    = '';
		$first_model_slug = '';
		$active_model     = $this->get_active_model_for_request( $product_id );

		if ( $active_model ) {
			$selected_slug = $active_model->get_slug();
		}

		foreach ( $models as $model ) {
			$featured_image = null;
			$featured_id    = $model->get_featured_image_id();

			if ( $featured_id ) {
				$featured_url = wp_get_attachment_image_url( $featured_id, 'large' );

				if ( $featured_url ) {
					$featured_image = array(
						'id'  => $featured_id,
						'url' => $featured_url,
					);
				}
			}

			$gallery = array();

			foreach ( $model->get_gallery_image_ids() as $gallery_id ) {
				$gallery_url = wp_get_attachment_image_url( $gallery_id, 'thumbnail' );
				$full_url    = wp_get_attachment_image_url( $gallery_id, 'large' );

				if ( ! $gallery_url || ! $full_url ) {
					continue;
				}

				$gallery[] = array(
					'id'       => $gallery_id,
					'url'      => $gallery_url,
					'full_url' => $full_url,
				);
			}

			$short_description_raw = (string) $model->get_short_description();
			$description_raw       = (string) $model->get_description();

			$prepared[] = array(
				'id'                         => $model->get_id(),
				'name'                       => $model->get_name(),
				'slug'                       => $model->get_slug(),
				'short_description'          => wp_kses_post( $short_description_raw ),
				'short_description_rendered' => $this->prepare_front_html( $short_description_raw ),
				'description'                => wp_kses_post( $description_raw ),
				'description_rendered'       => $this->prepare_front_html( $description_raw ),
				'featured_image'             => $featured_image,
				'gallery'                    => $gallery,
			);

			if ( '' === $first_model_slug ) {
				$first_model_slug = $model->get_slug();
			}
		}

		if ( '' === $selected_slug ) {
			$selected_slug = $first_model_slug;
		}

		return array(
			'productId'    => $product_id,
			'selectedSlug' => $selected_slug,
			'models'       => $prepared,
		);
	}
}