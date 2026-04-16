<?php
/**
 * View da meta box de modelos.
 *
 * @package SicaProductModels\Admin
 */

namespace SicaProductModels\Admin;

use SicaProductModels\Domain\ProductModel;

defined( 'ABSPATH' ) || exit;

/**
 * Classe MetaBoxView.
 */
class MetaBoxView {

	/**
	 * Renderiza a meta box.
	 *
	 * @param array<string, mixed> $context Contexto.
	 * @return void
	 */
	public function render( array $context ): void {
		$post_id         = isset( $context['post_id'] ) ? (int) $context['post_id'] : 0;
		$models          = isset( $context['models'] ) && is_array( $context['models'] ) ? $context['models'] : array();
		$attribute_terms = isset( $context['attribute_terms'] ) && is_array( $context['attribute_terms'] ) ? $context['attribute_terms'] : array();

		wp_nonce_field( 'spm_save_models', 'spm_models_nonce' );
		?>
		<div class="spm-admin" data-product-id="<?php echo esc_attr( (string) $post_id ); ?>">
			<p class="spm-admin__intro">
				<?php esc_html_e( 'Cadastre os modelos internos deste produto. Cada modelo pode ter nome, slug, textos próprios, imagem capa, galeria e vínculo opcional com atributo existente.', 'sica-product-models' ); ?>
			</p>

			<div class="spm-admin__toolbar">
				<button type="button" class="button button-primary spm-add-model">
					<?php esc_html_e( 'Adicionar modelo', 'sica-product-models' ); ?>
				</button>
			</div>

			<div class="spm-models-list">
				<?php
				if ( ! empty( $models ) ) {
					foreach ( $models as $index => $model ) {
						if ( $model instanceof ProductModel ) {
							$this->render_model_card( $model, (int) $index, $attribute_terms );
						}
					}
				}
				?>
			</div>

			<div class="spm-description-editor-panel">
				<div class="spm-description-editor-panel__header">
					<h3><?php esc_html_e( 'Editor da descrição completa', 'sica-product-models' ); ?></h3>
					<p class="spm-description-editor-panel__selected">
						<span class="spm-selected-model-label"><?php esc_html_e( 'Nenhum modelo selecionado.', 'sica-product-models' ); ?></span>
					</p>
				</div>

				<div class="spm-description-editor-panel__body">
					<?php
					wp_editor(
						'',
						'spm_shared_description_editor',
						array(
							'textarea_name' => 'spm_shared_description_editor',
							'textarea_rows' => 12,
							'media_buttons' => true,
							'teeny'         => false,
							'quicktags'     => true,
						)
					);
					?>
				</div>

				<div class="spm-description-editor-panel__footer">
					<button type="button" class="button button-primary spm-apply-description">
						<?php esc_html_e( 'Aplicar descrição ao modelo', 'sica-product-models' ); ?>
					</button>
				</div>
			</div>

			<div class="spm-model-template" style="display:none;">
				<?php $this->render_model_card_template( $attribute_terms ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Renderiza card de modelo existente.
	 *
	 * @param ProductModel                     $model Modelo.
	 * @param int                              $index Índice.
	 * @param array<int, array<string, mixed>> $attribute_terms Termos.
	 * @return void
	 */
	protected function render_model_card( ProductModel $model, int $index, array $attribute_terms ): void {
		$this->render_model_card_fields(
			array(
				'row_index'         => $index,
				'id'                => $model->get_id(),
				'attribute_term_id' => $model->get_attribute_term_id(),
				'name'              => $model->get_name(),
				'slug'              => $model->get_slug(),
				'short_description' => $model->get_short_description(),
				'description'       => $model->get_description(),
				'featured_image_id' => $model->get_featured_image_id(),
				'gallery_image_ids' => $model->get_gallery_image_ids(),
				'menu_order'        => $model->get_menu_order(),
				'is_active'         => $model->is_active(),
				'attribute_terms'   => $attribute_terms,
			)
		);
	}

	/**
	 * Template HTML do card.
	 *
	 * @param array<int, array<string, mixed>> $attribute_terms Termos.
	 * @return void
	 */
	protected function render_model_card_template( array $attribute_terms ): void {
		$this->render_model_card_fields(
			array(
				'row_index'         => '__ROW_INDEX__',
				'id'                => 0,
				'attribute_term_id' => '',
				'name'              => '',
				'slug'              => '',
				'short_description' => '',
				'description'       => '',
				'featured_image_id' => '',
				'gallery_image_ids' => array(),
				'menu_order'        => 0,
				'is_active'         => true,
				'attribute_terms'   => $attribute_terms,
			),
			true
		);
	}

	/**
	 * Renderiza os campos do card.
	 *
	 * @param array<string, mixed> $data Dados.
	 * @param bool                 $is_template Se é template.
	 * @return void
	 */
	protected function render_model_card_fields( array $data, bool $is_template = false ): void {
		$row_index         = $data['row_index'];
		$id                = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$attribute_term_id = $data['attribute_term_id'];
		$name              = (string) $data['name'];
		$slug              = (string) $data['slug'];
		$short_description = (string) $data['short_description'];
		$description       = (string) $data['description'];
		$featured_image_id = ! empty( $data['featured_image_id'] ) ? (int) $data['featured_image_id'] : 0;
		$gallery_image_ids = is_array( $data['gallery_image_ids'] ) ? $data['gallery_image_ids'] : array();
		$menu_order        = isset( $data['menu_order'] ) ? (int) $data['menu_order'] : 0;
		$is_active         = ! empty( $data['is_active'] );
		$attribute_terms   = isset( $data['attribute_terms'] ) && is_array( $data['attribute_terms'] ) ? $data['attribute_terms'] : array();

		$row_attr          = (string) $row_index;
		$input_name_prefix = 'spm_models[' . $row_index . ']';

		$featured_image_html = '';
		$gallery_csv         = '';

		if ( ! $is_template ) {
			$featured_image_url  = $featured_image_id ? wp_get_attachment_image_url( $featured_image_id, 'thumbnail' ) : '';
			$featured_image_html = $featured_image_url ? '<img src="' . esc_url( $featured_image_url ) . '" alt="" />' : '';
			$gallery_csv         = implode( ',', array_map( 'absint', $gallery_image_ids ) );
		}

		$description_summary = '' !== trim( wp_strip_all_tags( $description ) )
			? __( 'Conteúdo preenchido', 'sica-product-models' )
			: __( 'Sem descrição completa', 'sica-product-models' );

		$short_description_summary = '' !== trim( wp_strip_all_tags( $short_description ) )
			? __( 'Breve descrição aplicada', 'sica-product-models' )
			: __( 'Sem descrição breve', 'sica-product-models' );

		$short_description_summary_class = '' !== trim( wp_strip_all_tags( $short_description ) )
			? 'is-applied'
			: 'is-empty';
		?>
		<div class="spm-model-card" data-row-index="<?php echo esc_attr( $row_attr ); ?>">
			<div class="spm-model-card__header">
				<div class="spm-model-card__drag">⋮⋮</div>
				<div class="spm-model-card__title">
					<strong class="spm-model-card__title-text">
						<?php echo '' !== $name ? esc_html( $name ) : esc_html__( 'Novo modelo', 'sica-product-models' ); ?>
					</strong>
					<span class="spm-model-card__slug-preview">
						<?php echo '' !== $slug ? esc_html( $slug ) : ''; ?>
					</span>
				</div>
				<div class="spm-model-card__actions">
					<button type="button" class="button-link spm-edit-description">
						<?php esc_html_e( 'Editar descrição completa', 'sica-product-models' ); ?>
					</button>
					<button type="button" class="button-link-delete spm-remove-model">
						<?php esc_html_e( 'Remover', 'sica-product-models' ); ?>
					</button>
				</div>
			</div>

			<div class="spm-model-card__body">
				<input type="hidden" name="<?php echo esc_attr( $input_name_prefix ); ?>[id]" value="<?php echo esc_attr( (string) $id ); ?>" />
				<input type="hidden" class="spm-menu-order-field" name="<?php echo esc_attr( $input_name_prefix ); ?>[menu_order]" value="<?php echo esc_attr( (string) $menu_order ); ?>" />
				<textarea class="spm-description-hidden" name="<?php echo esc_attr( $input_name_prefix ); ?>[description]" rows="1" hidden><?php echo esc_textarea( $description ); ?></textarea>

				<div class="spm-grid spm-grid--2">
					<div class="spm-field">
						<label><?php esc_html_e( 'Nome do modelo', 'sica-product-models' ); ?></label>
						<input type="text" class="regular-text spm-model-name" name="<?php echo esc_attr( $input_name_prefix ); ?>[name]" value="<?php echo esc_attr( $name ); ?>" />
					</div>

					<div class="spm-field">
						<label><?php esc_html_e( 'Slug interno', 'sica-product-models' ); ?></label>
						<input type="text" class="regular-text spm-model-slug" name="<?php echo esc_attr( $input_name_prefix ); ?>[slug]" value="<?php echo esc_attr( $slug ); ?>" />
					</div>
				</div>

				<div class="spm-grid spm-grid--2">
					<div class="spm-field">
						<label><?php esc_html_e( 'Atributo vinculado (opcional)', 'sica-product-models' ); ?></label>
						<select name="<?php echo esc_attr( $input_name_prefix ); ?>[attribute_term_id]">
							<option value=""><?php esc_html_e( 'Selecione', 'sica-product-models' ); ?></option>
							<?php foreach ( $attribute_terms as $term ) : ?>
								<option value="<?php echo esc_attr( (string) $term['id'] ); ?>" <?php selected( (string) $attribute_term_id, (string) $term['id'] ); ?>>
									<?php echo esc_html( $term['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="spm-field spm-field--checkbox">
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $input_name_prefix ); ?>[is_active]" value="1" <?php checked( $is_active ); ?> />
							<?php esc_html_e( 'Modelo ativo', 'sica-product-models' ); ?>
						</label>
					</div>
				</div>

				<div class="spm-field">
					<label><?php esc_html_e( 'Descrição breve', 'sica-product-models' ); ?></label>
					<textarea
						rows="4"
						class="spm-short-description-input"
						name="<?php echo esc_attr( $input_name_prefix ); ?>[short_description]"
					><?php echo esc_textarea( $short_description ); ?></textarea>
					<textarea
						class="spm-short-description-hidden"
						name="<?php echo esc_attr( $input_name_prefix ); ?>[short_description_persisted]"
						rows="1"
						hidden
					><?php echo esc_textarea( $short_description ); ?></textarea>

					<div class="spm-short-description-tools">
						<button type="button" class="button spm-apply-short-description">
							<?php esc_html_e( 'Aplicar descrição breve', 'sica-product-models' ); ?>
						</button>

						<div class="spm-short-description-status">
							<span class="spm-short-description-status__text <?php echo esc_attr( $short_description_summary_class ); ?>">
								<?php echo esc_html( $short_description_summary ); ?>
							</span>
						</div>
					</div>

					<div class="spm-short-description-feedback" aria-live="polite"></div>
				</div>

				<div class="spm-field">
					<label><?php esc_html_e( 'Descrição completa', 'sica-product-models' ); ?></label>
					<div class="spm-description-summary">
						<span class="spm-description-summary__text"><?php echo esc_html( $description_summary ); ?></span>
					</div>
				</div>

				<div class="spm-grid spm-grid--2">
					<div class="spm-field">
						<label><?php esc_html_e( 'Imagem capa', 'sica-product-models' ); ?></label>

						<div class="spm-media-box">
							<input type="hidden" class="spm-featured-image-id" name="<?php echo esc_attr( $input_name_prefix ); ?>[featured_image_id]" value="<?php echo esc_attr( (string) $featured_image_id ); ?>" />
							<div class="spm-media-preview">
								<?php echo wp_kses_post( $featured_image_html ); ?>
							</div>
							<div class="spm-media-actions">
								<button type="button" class="button spm-upload-featured-image"><?php esc_html_e( 'Selecionar imagem', 'sica-product-models' ); ?></button>
								<button type="button" class="button spm-remove-featured-image"><?php esc_html_e( 'Remover imagem', 'sica-product-models' ); ?></button>
							</div>
						</div>
					</div>

					<div class="spm-field">
						<label><?php esc_html_e( 'Galeria de imagens', 'sica-product-models' ); ?></label>

						<div class="spm-gallery-box">
							<input type="hidden" class="spm-gallery-image-ids" name="<?php echo esc_attr( $input_name_prefix ); ?>[gallery_image_ids]" value="<?php echo esc_attr( $gallery_csv ); ?>" />
							<div class="spm-gallery-preview">
								<?php
								if ( ! $is_template ) {
									foreach ( $gallery_image_ids as $gallery_image_id ) {
										$image_url = wp_get_attachment_image_url( (int) $gallery_image_id, 'thumbnail' );

										if ( ! $image_url ) {
											continue;
										}
										?>
										<img src="<?php echo esc_url( $image_url ); ?>" alt="" />
										<?php
									}
								}
								?>
							</div>
							<div class="spm-media-actions">
								<button type="button" class="button spm-upload-gallery"><?php esc_html_e( 'Selecionar galeria', 'sica-product-models' ); ?></button>
								<button type="button" class="button spm-remove-gallery"><?php esc_html_e( 'Limpar galeria', 'sica-product-models' ); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}