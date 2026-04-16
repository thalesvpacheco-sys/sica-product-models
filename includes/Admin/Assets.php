<?php
/**
 * Assets do admin.
 *
 * @package SicaProductModels\Admin
 */

namespace SicaProductModels\Admin;

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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enfileira assets do admin.
	 *
	 * @param string $hook_suffix Hook atual.
	 * @return void
	 */
	public function enqueue( string $hook_suffix ): void {
		global $post;

		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		if ( ! $post || 'product' !== get_post_type( $post ) ) {
			return;
		}

		$admin_css_path = SPM_PATH . 'assets/css/admin.css';
		$admin_js_path  = SPM_PATH . 'assets/js/admin.js';

		wp_enqueue_media();
		wp_enqueue_editor();
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_style(
			'spm-admin',
			SPM_URL . 'assets/css/admin.css',
			array(),
			file_exists( $admin_css_path ) ? (string) filemtime( $admin_css_path ) : SPM_VERSION
		);

		wp_enqueue_script(
			'spm-admin',
			SPM_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable', 'editor', 'quicktags', 'media-editor' ),
			file_exists( $admin_js_path ) ? (string) filemtime( $admin_js_path ) : SPM_VERSION,
			true
		);

		wp_localize_script(
			'spm-admin',
			'spmAdmin',
			array(
				'mediaTitle'              => __( 'Selecionar imagem', 'sica-product-models' ),
				'mediaButton'             => __( 'Usar imagem', 'sica-product-models' ),
				'galleryTitle'            => __( 'Selecionar galeria', 'sica-product-models' ),
				'galleryButton'           => __( 'Usar galeria', 'sica-product-models' ),
				'addModelLabel'           => __( 'Novo modelo', 'sica-product-models' ),
				'removeConfirm'           => __( 'Remover este modelo?', 'sica-product-models' ),
				'editDescriptionLabel'    => __( 'Editar descrição completa', 'sica-product-models' ),
				'editorPanelTitle'        => __( 'Editor da descrição completa', 'sica-product-models' ),
				'editorEmptyState'        => __( 'Selecione um modelo para editar a descrição completa.', 'sica-product-models' ),
				'editorApplyButton'       => __( 'Aplicar descrição ao modelo', 'sica-product-models' ),
				'editorSelectedPrefix'    => __( 'Editando:', 'sica-product-models' ),
				'editorContentSummary'    => __( 'Conteúdo preenchido', 'sica-product-models' ),
				'editorContentEmpty'      => __( 'Sem descrição completa', 'sica-product-models' ),
				'shortDescriptionApplied' => __( 'Breve descrição aplicada', 'sica-product-models' ),
				'shortDescriptionEmpty'   => __( 'Sem descrição breve', 'sica-product-models' ),
				'shortDescriptionPending' => __( 'Alteração não aplicada', 'sica-product-models' ),
				'shortDescriptionFeedback'=> __( 'Descrição breve aplicada com sucesso.', 'sica-product-models' ),
			)
		);
	}
}