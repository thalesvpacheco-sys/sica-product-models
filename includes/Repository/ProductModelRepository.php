<?php
/**
 * Repositório dos modelos de produto.
 *
 * @package SicaProductModels\Repository
 */

namespace SicaProductModels\Repository;

use SicaProductModels\Core\Installer;
use SicaProductModels\Domain\ProductModel;

defined( 'ABSPATH' ) || exit;

/**
 * Classe ProductModelRepository.
 */
class ProductModelRepository {

	/**
	 * Nome da tabela.
	 *
	 * @var string
	 */
	protected string $table_name;

	/**
	 * Construtor.
	 */
	public function __construct() {
		$this->table_name = Installer::get_table_name();
	}

	/**
	 * Busca um modelo pelo ID.
	 *
	 * @param int $id ID do modelo.
	 * @return ProductModel|null
	 */
	public function find( int $id ): ?ProductModel {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE id = %d LIMIT 1",
				$id
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		return ProductModel::from_array( $row );
	}

	/**
	 * Lista modelos por produto.
	 *
	 * @param int  $product_id ID do produto.
	 * @param bool $active_only Retornar apenas ativos.
	 * @return array<int, ProductModel>
	 */
	public function find_by_product_id( int $product_id, bool $active_only = false ): array {
		global $wpdb;

		$sql  = "SELECT * FROM {$this->table_name} WHERE product_id = %d";
		$args = array( $product_id );

		if ( $active_only ) {
			$sql .= ' AND is_active = 1';
		}

		$sql .= ' ORDER BY menu_order ASC, id ASC';

		$prepared = $wpdb->prepare( $sql, $args );
		$rows     = $wpdb->get_results( $prepared, ARRAY_A );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$models = array();

		foreach ( $rows as $row ) {
			$models[] = ProductModel::from_array( $row );
		}

		return $models;
	}

	/**
	 * Busca um modelo por slug dentro do produto.
	 *
	 * @param int    $product_id ID do produto.
	 * @param string $slug Slug do modelo.
	 * @return ProductModel|null
	 */
	public function find_by_product_slug( int $product_id, string $slug ): ?ProductModel {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE product_id = %d AND slug = %s LIMIT 1",
				$product_id,
				$slug
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return null;
		}

		return ProductModel::from_array( $row );
	}

	/**
	 * Insere um modelo.
	 *
	 * @param ProductModel $model Modelo.
	 * @return int ID inserido.
	 */
	public function insert( ProductModel $model ): int {
		global $wpdb;

		$now  = current_time( 'mysql' );
		$data = $this->prepare_database_data( $model );

		$data['created_at'] = $now;
		$data['updated_at'] = $now;

		$result = $wpdb->insert(
			$this->table_name,
			$data,
			array_merge( $this->get_insert_formats(), array( '%s', '%s' ) )
		);

		if ( false === $result ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Atualiza um modelo existente.
	 *
	 * @param ProductModel $model Modelo.
	 * @return bool
	 */
	public function update( ProductModel $model ): bool {
		global $wpdb;

		if ( $model->get_id() <= 0 ) {
			return false;
		}

		$data               = $this->prepare_database_data( $model );
		$data['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->update(
			$this->table_name,
			$data,
			array( 'id' => $model->get_id() ),
			array_merge( $this->get_update_formats(), array( '%s' ) ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Salva um modelo, inserindo ou atualizando.
	 *
	 * @param ProductModel $model Modelo.
	 * @return int ID do modelo salvo.
	 */
	public function save( ProductModel $model ): int {
		if ( $model->get_id() > 0 ) {
			$updated = $this->update( $model );

			return $updated ? $model->get_id() : 0;
		}

		return $this->insert( $model );
	}

	/**
	 * Exclui um modelo por ID.
	 *
	 * @param int $id ID.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table_name,
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Exclui todos os modelos de um produto.
	 *
	 * @param int $product_id ID do produto.
	 * @return bool
	 */
	public function delete_by_product_id( int $product_id ): bool {
		global $wpdb;

		$result = $wpdb->delete(
			$this->table_name,
			array( 'product_id' => $product_id ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Verifica se já existe slug no mesmo produto.
	 *
	 * @param int      $product_id ID do produto.
	 * @param string   $slug Slug.
	 * @param int|null $exclude_id ID para ignorar.
	 * @return bool
	 */
	public function slug_exists( int $product_id, string $slug, ?int $exclude_id = null ): bool {
		global $wpdb;

		$sql  = "SELECT COUNT(*) FROM {$this->table_name} WHERE product_id = %d AND slug = %s";
		$args = array( $product_id, $slug );

		if ( null !== $exclude_id && $exclude_id > 0 ) {
			$sql   .= ' AND id != %d';
			$args[] = $exclude_id;
		}

		$count = (int) $wpdb->get_var( $wpdb->prepare( $sql, $args ) );

		return $count > 0;
	}

	/**
	 * Prepara dados para salvar no banco.
	 *
	 * @param ProductModel $model Modelo.
	 * @return array<string, mixed>
	 */
	protected function prepare_database_data( ProductModel $model ): array {
		return array(
			'product_id'        => $model->get_product_id(),
			'attribute_term_id' => $model->get_attribute_term_id(),
			'name'              => $model->get_name(),
			'slug'              => $model->get_slug(),
			'short_description' => $model->get_short_description(),
			'description'       => $model->get_description(),
			'featured_image_id' => $model->get_featured_image_id(),
			'gallery_image_ids' => wp_json_encode(
				array_values(
					array_filter(
						array_map( 'absint', $model->get_gallery_image_ids() ),
						function ( $v ) {
							return $v > 0;
						}
					)
				)
			),
			'menu_order'        => $model->get_menu_order(),
			'is_active'         => $model->is_active() ? 1 : 0,
		);
	}

	/**
	 * Formats para insert.
	 *
	 * @return array<int, string>
	 */
	protected function get_insert_formats(): array {
		return array(
			'%d',
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
			'%d',
			'%d',
		);
	}

	/**
	 * Formats para update.
	 *
	 * @return array<int, string>
	 */
	protected function get_update_formats(): array {
		return array(
			'%d',
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
			'%d',
			'%d',
		);
	}
}