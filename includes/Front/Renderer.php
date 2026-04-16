<?php
/**
 * Renderização do seletor de modelos no front.
 *
 * @package SicaProductModels\Front
 */

namespace SicaProductModels\Front;

use SicaProductModels\Service\ProductModelService;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Renderer.
 */
class Renderer {

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
	 * Renderiza o módulo.
	 *
	 * @param int $product_id ID do produto.
	 * @return string
	 */
	public function render( int $product_id ): string {
		$payload      = $this->service->get_front_models_payload( $product_id );
		$active_model = $this->service->get_active_model_for_request( $product_id );
		$active_slug  = $active_model ? $active_model->get_slug() : '';
		$base_url     = $this->get_base_product_url( $product_id );

		if ( empty( $payload['models'] ) || ! is_array( $payload['models'] ) ) {
			return '';
		}

		ob_start();
		?>
		<div
			class="spm-front spm-front--selector-only"
			data-product-id="<?php echo esc_attr( (string) $product_id ); ?>"
			data-selected-slug="<?php echo esc_attr( $active_slug ); ?>"
		>
			<div class="spm-front__selector">
				<div class="spm-front__label"><?php esc_html_e( 'Selecione seu modelo', 'sica-product-models' ); ?></div>

				<div class="spm-front__options">
					<?php foreach ( $payload['models'] as $model ) : ?>
						<?php
						$model_slug = isset( $model['slug'] ) ? sanitize_title( (string) $model['slug'] ) : '';
						$model_name = isset( $model['name'] ) ? (string) $model['name'] : '';
						$model_url  = add_query_arg( 'spm_model', $model_slug, $base_url );
						$classes    = 'spm-front__option';

						if ( '' !== $active_slug && $active_slug === $model_slug ) {
							$classes .= ' is-active';
						}
						?>
						<a
							href="<?php echo esc_url( $model_url ); ?>"
							class="<?php echo esc_attr( $classes ); ?>"
							data-model-slug="<?php echo esc_attr( $model_slug ); ?>"
							data-model-url="<?php echo esc_url( $model_url ); ?>"
						>
							<?php echo esc_html( $model_name ); ?>
						</a>
					<?php endforeach; ?>

					<?php if ( '' !== $active_slug ) : ?>
						<a
							href="<?php echo esc_url( $base_url ); ?>"
							class="spm-front__reset"
							data-reset-url="<?php echo esc_url( $base_url ); ?>"
						>
							<?php esc_html_e( 'Voltar ao produto', 'sica-product-models' ); ?>
						</a>
					<?php endif; ?>
				</div>

				<div class="spm-front__hint">
					<?php if ( '' !== $active_slug ) : ?>
						<?php esc_html_e( 'Você está visualizando o modelo selecionado.', 'sica-product-models' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Clique em um modelo para abrir a versão correspondente do produto.', 'sica-product-models' ); ?>
					<?php endif; ?>
				</div>

				<input type="hidden" class="spm-front__selected-model" name="spm_selected_model" value="<?php echo esc_attr( $active_slug ); ?>" />
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Retorna a URL base do produto atual sem o parâmetro do modelo.
	 *
	 * @param int $product_id ID do produto.
	 * @return string
	 */
	protected function get_base_product_url( int $product_id ): string {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$current_url = '';

		if ( '' !== $request_uri ) {
			$current_url = home_url( $request_uri );
		}

		if ( '' === $current_url ) {
			$current_url = get_permalink( $product_id );
		}

		return remove_query_arg( 'spm_model', $current_url );
	}
}