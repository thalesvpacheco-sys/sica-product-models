<?php
/**
 * Integração do módulo com a página do produto.
 *
 * @package SicaProductModels\Front
 */

namespace SicaProductModels\Front;

use SicaProductModels\Domain\ProductModel;
use SicaProductModels\Service\ProductModelService;

defined( 'ABSPATH' ) || exit;

/**
 * Classe ProductPage.
 */
class ProductPage {

	/**
	 * Renderer.
	 *
	 * @var Renderer
	 */
	protected Renderer $renderer;

	/**
	 * Service.
	 *
	 * @var ProductModelService
	 */
	protected ProductModelService $service;

	/**
	 * ID do produto atual.
	 *
	 * @var int
	 */
	protected int $current_product_id = 0;

	/**
	 * Modelo ativo da requisição.
	 *
	 * @var ProductModel|null
	 */
	protected ?ProductModel $current_model = null;

	/**
	 * Controle para registrar filtros só uma vez.
	 *
	 * @var bool
	 */
	protected bool $runtime_filters_registered = false;

	/**
	 * Construtor.
	 *
	 * @param Renderer|null            $renderer Renderer.
	 * @param ProductModelService|null $service Service.
	 */
	public function __construct( ?Renderer $renderer = null, ?ProductModelService $service = null ) {
		$this->renderer = $renderer ?? new Renderer();
		$this->service  = $service ?? new ProductModelService();
	}

	/**
	 * Registra hooks.
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'wp', array( $this, 'bootstrap_runtime_state' ), 20 );
		add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_models_module' ), 8 );
	}

	/**
	 * Prepara o estado runtime do produto/modelo atual.
	 *
	 * @return void
	 */
	public function bootstrap_runtime_state(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$product_id = $this->get_current_product_id();

		if ( $product_id <= 0 ) {
			return;
		}

		$this->current_product_id = $product_id;
		$this->current_model      = $this->service->get_active_model_for_request( $product_id );

		if ( ! $this->current_model ) {
			return;
		}

		$this->register_runtime_filters();
	}

	/**
	 * Registra filtros de runtime do produto atual.
	 *
	 * @return void
	 */
	protected function register_runtime_filters(): void {
		if ( $this->runtime_filters_registered ) {
			return;
		}

		add_filter( 'woocommerce_product_get_image_id', array( $this, 'filter_product_image_id' ), 10, 2 );
		add_filter( 'woocommerce_product_get_gallery_image_ids', array( $this, 'filter_product_gallery_image_ids' ), 10, 2 );
		add_filter( 'woocommerce_product_get_short_description', array( $this, 'filter_product_short_description' ), 10, 2 );
		add_filter( 'woocommerce_product_get_description', array( $this, 'filter_product_description' ), 10, 2 );
		add_filter( 'woocommerce_short_description', array( $this, 'filter_woocommerce_short_description' ), 20 );
		add_filter( 'post_thumbnail_id', array( $this, 'filter_post_thumbnail_id' ), 10, 2 );
		add_filter( 'the_content', array( $this, 'filter_the_content' ), 20 );
		add_filter( 'get_the_excerpt', array( $this, 'filter_get_the_excerpt' ), 20, 2 );
		add_filter( 'the_excerpt', array( $this, 'filter_the_excerpt' ), 20 );

		$this->runtime_filters_registered = true;
	}

	/**
	 * Retorna o ID do produto atual.
	 *
	 * @return int
	 */
	protected function get_current_product_id(): int {
		$product_id = (int) get_queried_object_id();

		if ( $product_id > 0 ) {
			return $product_id;
		}

		global $post;

		if ( $post instanceof \WP_Post && 'product' === $post->post_type ) {
			return (int) $post->ID;
		}

		return 0;
	}

	/**
	 * Verifica se o produto informado é o produto atual da página.
	 *
	 * @param mixed $product Produto.
	 * @return bool
	 */
	protected function is_current_product( $product ): bool {
		return $product instanceof \WC_Product && (int) $product->get_id() === $this->current_product_id;
	}

	/**
	 * Verifica se o post informado é o produto atual da página.
	 *
	 * @param mixed $maybe_post Post.
	 * @return bool
	 */
	protected function is_current_product_post( $maybe_post ): bool {
		if ( $maybe_post instanceof \WP_Post ) {
			return 'product' === $maybe_post->post_type && (int) $maybe_post->ID === $this->current_product_id;
		}

		if ( is_numeric( $maybe_post ) ) {
			return (int) $maybe_post === $this->current_product_id;
		}

		global $post;

		return $post instanceof \WP_Post
			&& 'product' === $post->post_type
			&& (int) $post->ID === $this->current_product_id;
	}

	/**
	 * Verifica se existe modelo ativo válido no contexto atual.
	 *
	 * @return bool
	 */
	protected function has_active_model_context(): bool {
		if ( ! $this->current_model ) {
			return false;
		}

		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return false;
		}

		return $this->current_product_id > 0;
	}

	/**
	 * Retorna o ID de imagem principal do modelo atual.
	 *
	 * @return int
	 */
	protected function get_current_model_image_id(): int {
		if ( ! $this->current_model ) {
			return 0;
		}

		$featured_image_id = (int) $this->current_model->get_featured_image_id();

		if ( $featured_image_id > 0 ) {
			return $featured_image_id;
		}

		$gallery_ids = $this->current_model->get_gallery_image_ids();

		if ( ! empty( $gallery_ids ) ) {
			return (int) reset( $gallery_ids );
		}

		return 0;
	}

	/**
	 * Filtra a imagem principal do produto atual.
	 *
	 * @param int         $image_id ID atual.
	 * @param \WC_Product $product Produto.
	 * @return int
	 */
	public function filter_product_image_id( int $image_id, \WC_Product $product ): int {
		if ( ! $this->current_model || ! $this->is_current_product( $product ) ) {
			return $image_id;
		}

		$model_image_id = $this->get_current_model_image_id();

		return $model_image_id > 0 ? $model_image_id : $image_id;
	}

	/**
	 * Filtra a galeria do produto atual.
	 *
	 * @param array<int, mixed> $gallery_ids Galeria atual.
	 * @param \WC_Product       $product Produto.
	 * @return array<int>
	 */
	public function filter_product_gallery_image_ids( array $gallery_ids, \WC_Product $product ): array {
		if ( ! $this->current_model || ! $this->is_current_product( $product ) ) {
			return $gallery_ids;
		}

		return array_values( array_filter( array_map( 'absint', $this->current_model->get_gallery_image_ids() ) ) );
	}

	/**
	 * Filtra a descrição breve no getter do produto.
	 *
	 * @param string      $value Valor atual.
	 * @param \WC_Product $product Produto.
	 * @return string
	 */
	public function filter_product_short_description( string $value, \WC_Product $product ): string {
		if ( ! $this->current_model || ! $this->is_current_product( $product ) ) {
			return $value;
		}

		return (string) $this->current_model->get_short_description();
	}

	/**
	 * Filtra a descrição completa do produto atual.
	 *
	 * @param string      $value Valor atual.
	 * @param \WC_Product $product Produto.
	 * @return string
	 */
	public function filter_product_description( string $value, \WC_Product $product ): string {
		if ( ! $this->current_model || ! $this->is_current_product( $product ) ) {
			return $value;
		}

		return (string) $this->current_model->get_description();
	}

	/**
	 * Filtra a descrição breve no caminho nativo do WooCommerce.
	 *
	 * @param string $short_description Conteúdo atual.
	 * @return string
	 */
	public function filter_woocommerce_short_description( string $short_description ): string {
		if ( ! $this->has_active_model_context() ) {
			return $short_description;
		}

		return $this->service->get_model_short_description_html( $this->current_model );
	}

	/**
	 * Filtra thumbnail do post do produto atual para casos fora do getter do produto.
	 *
	 * @param int   $thumbnail_id ID atual.
	 * @param mixed $post Post ou ID.
	 * @return int
	 */
	public function filter_post_thumbnail_id( int $thumbnail_id, $post ): int {
		if ( ! $this->current_model || ! $this->is_current_product_post( $post ) ) {
			return $thumbnail_id;
		}

		$model_image_id = $this->get_current_model_image_id();

		return $model_image_id > 0 ? $model_image_id : $thumbnail_id;
	}

	/**
	 * Filtra o conteúdo principal do produto atual.
	 *
	 * @param string $content Conteúdo.
	 * @return string
	 */
	public function filter_the_content( string $content ): string {
		if ( ! is_singular( 'product' ) ) {
			return $content;
		}

		if ( (int) get_queried_object_id() !== $this->current_product_id ) {
			return $content;
		}

		if ( ! $this->current_model ) {
			return $content;
		}

		global $post;

		if ( ! $this->is_current_product_post( $post ) ) {
			return $content;
		}

		return $this->service->get_model_description_html( $this->current_model );
	}

	/**
	 * Filtra o excerpt cru do produto atual.
	 *
	 * @param string            $excerpt Excerpt atual.
	 * @param \WP_Post|int|null $post Post.
	 * @return string
	 */
	public function filter_get_the_excerpt( string $excerpt, $post ): string {
		if ( ! is_singular( 'product' ) ) {
			return $excerpt;
		}

		if ( ! $this->current_model || ! $this->is_current_product_post( $post ) ) {
			return $excerpt;
		}

		return $this->service->get_model_short_description_html( $this->current_model );
	}

	/**
	 * Filtra o excerpt renderizado.
	 *
	 * @param string $excerpt Excerpt atual.
	 * @return string
	 */
	public function filter_the_excerpt( string $excerpt ): string {
		if ( ! $this->has_active_model_context() ) {
			return $excerpt;
		}

		return $this->service->get_model_short_description_html( $this->current_model );
	}

	/**
	 * Renderiza o módulo no single product.
	 *
	 * @return void
	 */
	public function render_models_module(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		global $product;

		if ( ! $product || ! method_exists( $product, 'get_id' ) ) {
			return;
		}

		$output = $this->renderer->render( (int) $product->get_id() );

		if ( '' === $output ) {
			return;
		}

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}