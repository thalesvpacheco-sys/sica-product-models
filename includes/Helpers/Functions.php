<?php
/**
 * Funções globais auxiliares do plugin.
 *
 * @package SicaProductModels\Helpers
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'spm_get_models' ) ) {
	/**
	 * Retorna modelos de um produto.
	 *
	 * @param int  $product_id  ID do produto.
	 * @param bool $active_only Apenas ativos.
	 * @return array<int, \SicaProductModels\Domain\ProductModel>
	 */
	function spm_get_models( int $product_id, bool $active_only = false ): array {
		$service = new \SicaProductModels\Service\ProductModelService();

		return $service->get_models_by_product_id( $product_id, $active_only );
	}
}

if ( ! function_exists( 'spm_get_model' ) ) {
	/**
	 * Retorna um modelo pelo ID.
	 *
	 * @param int $id ID do modelo.
	 * @return \SicaProductModels\Domain\ProductModel|null
	 */
	function spm_get_model( int $id ): ?\SicaProductModels\Domain\ProductModel {
		$service = new \SicaProductModels\Service\ProductModelService();

		return $service->get_model( $id );
	}
}

if ( ! function_exists( 'spm_get_model_by_slug' ) ) {
	/**
	 * Retorna um modelo pelo slug dentro de um produto.
	 *
	 * @param int    $product_id ID do produto.
	 * @param string $slug       Slug do modelo.
	 * @return \SicaProductModels\Domain\ProductModel|null
	 */
	function spm_get_model_by_slug( int $product_id, string $slug ): ?\SicaProductModels\Domain\ProductModel {
		$service = new \SicaProductModels\Service\ProductModelService();

		return $service->get_model_by_slug( $product_id, $slug );
	}
}