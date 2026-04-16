<?php
/**
 * Entidade de Modelo de Produto.
 *
 * @package SicaProductModels\Domain
 */

namespace SicaProductModels\Domain;

defined( 'ABSPATH' ) || exit;

/**
 * Classe ProductModel.
 */
class ProductModel {

	/**
	 * ID do modelo.
	 *
	 * @var int
	 */
	protected int $id = 0;

	/**
	 * ID do produto WooCommerce.
	 *
	 * @var int
	 */
	protected int $product_id = 0;

	/**
	 * ID do termo de atributo associado, se existir.
	 *
	 * @var int|null
	 */
	protected ?int $attribute_term_id = null;

	/**
	 * Nome do modelo.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * Slug do modelo.
	 *
	 * @var string
	 */
	protected string $slug = '';

	/**
	 * Descrição breve.
	 *
	 * @var string
	 */
	protected string $short_description = '';

	/**
	 * Descrição completa.
	 *
	 * @var string
	 */
	protected string $description = '';

	/**
	 * ID da imagem destacada/capa.
	 *
	 * @var int|null
	 */
	protected ?int $featured_image_id = null;

	/**
	 * IDs da galeria.
	 *
	 * @var array<int>
	 */
	protected array $gallery_image_ids = array();

	/**
	 * Ordem de exibição.
	 *
	 * @var int
	 */
	protected int $menu_order = 0;

	/**
	 * Status ativo.
	 *
	 * @var bool
	 */
	protected bool $is_active = true;

	/**
	 * Data de criação.
	 *
	 * @var string
	 */
	protected string $created_at = '';

	/**
	 * Data de atualização.
	 *
	 * @var string
	 */
	protected string $updated_at = '';

	/**
	 * Cria instância a partir de array.
	 *
	 * @param array<string, mixed> $data Dados brutos.
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$model = new self();

		$model->set_id( isset( $data['id'] ) ? (int) $data['id'] : 0 );
		$model->set_product_id( isset( $data['product_id'] ) ? (int) $data['product_id'] : 0 );
		$model->set_attribute_term_id(
			isset( $data['attribute_term_id'] ) && null !== $data['attribute_term_id']
				? (int) $data['attribute_term_id']
				: null
		);
		$model->set_name( isset( $data['name'] ) ? (string) $data['name'] : '' );
		$model->set_slug( isset( $data['slug'] ) ? (string) $data['slug'] : '' );
		$model->set_short_description( isset( $data['short_description'] ) ? (string) $data['short_description'] : '' );
		$model->set_description( isset( $data['description'] ) ? (string) $data['description'] : '' );
		$model->set_featured_image_id(
			isset( $data['featured_image_id'] ) && null !== $data['featured_image_id']
				? (int) $data['featured_image_id']
				: null
		);

		$gallery_ids = array();

		if ( isset( $data['gallery_image_ids'] ) ) {
			if ( is_array( $data['gallery_image_ids'] ) ) {
				$gallery_ids = $data['gallery_image_ids'];
			} elseif ( is_string( $data['gallery_image_ids'] ) && '' !== $data['gallery_image_ids'] ) {
				$decoded = json_decode( $data['gallery_image_ids'], true );

				if ( is_array( $decoded ) ) {
					$gallery_ids = $decoded;
				}
			}
		}

		$model->set_gallery_image_ids( $gallery_ids );
		$model->set_menu_order( isset( $data['menu_order'] ) ? (int) $data['menu_order'] : 0 );
		$model->set_is_active( isset( $data['is_active'] ) ? (bool) $data['is_active'] : true );
		$model->set_created_at( isset( $data['created_at'] ) ? (string) $data['created_at'] : '' );
		$model->set_updated_at( isset( $data['updated_at'] ) ? (string) $data['updated_at'] : '' );

		return $model;
	}

	/**
	 * Exporta dados para array.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'                => $this->get_id(),
			'product_id'        => $this->get_product_id(),
			'attribute_term_id' => $this->get_attribute_term_id(),
			'name'              => $this->get_name(),
			'slug'              => $this->get_slug(),
			'short_description' => $this->get_short_description(),
			'description'       => $this->get_description(),
			'featured_image_id' => $this->get_featured_image_id(),
			'gallery_image_ids' => $this->get_gallery_image_ids(),
			'menu_order'        => $this->get_menu_order(),
			'is_active'         => $this->is_active(),
			'created_at'        => $this->get_created_at(),
			'updated_at'        => $this->get_updated_at(),
		);
	}

	/**
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * @param int $id ID.
	 * @return void
	 */
	public function set_id( int $id ): void {
		$this->id = max( 0, $id );
	}

	/**
	 * @return int
	 */
	public function get_product_id(): int {
		return $this->product_id;
	}

	/**
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function set_product_id( int $product_id ): void {
		$this->product_id = max( 0, $product_id );
	}

	/**
	 * @return int|null
	 */
	public function get_attribute_term_id(): ?int {
		return $this->attribute_term_id;
	}

	/**
	 * @param int|null $attribute_term_id Term ID.
	 * @return void
	 */
	public function set_attribute_term_id( ?int $attribute_term_id ): void {
		$this->attribute_term_id = null === $attribute_term_id ? null : max( 0, $attribute_term_id );
	}

	/**
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * @param string $name Nome.
	 * @return void
	 */
	public function set_name( string $name ): void {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function get_slug(): string {
		return $this->slug;
	}

	/**
	 * @param string $slug Slug.
	 * @return void
	 */
	public function set_slug( string $slug ): void {
		$this->slug = $slug;
	}

	/**
	 * @return string
	 */
	public function get_short_description(): string {
		return $this->short_description;
	}

	/**
	 * @param string $short_description Descrição breve.
	 * @return void
	 */
	public function set_short_description( string $short_description ): void {
		$this->short_description = $short_description;
	}

	/**
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * @param string $description Descrição completa.
	 * @return void
	 */
	public function set_description( string $description ): void {
		$this->description = $description;
	}

	/**
	 * @return int|null
	 */
	public function get_featured_image_id(): ?int {
		return $this->featured_image_id;
	}

	/**
	 * @param int|null $featured_image_id ID da imagem.
	 * @return void
	 */
	public function set_featured_image_id( ?int $featured_image_id ): void {
		$this->featured_image_id = null === $featured_image_id ? null : max( 0, $featured_image_id );
	}

	/**
	 * @return array<int>
	 */
	public function get_gallery_image_ids(): array {
		return $this->gallery_image_ids;
	}

	/**
	 * @param array<mixed> $gallery_image_ids IDs da galeria.
	 * @return void
	 */
	public function set_gallery_image_ids( array $gallery_image_ids ): void {
		$this->gallery_image_ids = array_values(
			array_filter(
				array_map( 'absint', $gallery_image_ids )
			)
		);
	}

	/**
	 * @return int
	 */
	public function get_menu_order(): int {
		return $this->menu_order;
	}

	/**
	 * @param int $menu_order Ordem.
	 * @return void
	 */
	public function set_menu_order( int $menu_order ): void {
		$this->menu_order = $menu_order;
	}

	/**
	 * @return bool
	 */
	public function is_active(): bool {
		return $this->is_active;
	}

	/**
	 * @param bool $is_active Status.
	 * @return void
	 */
	public function set_is_active( bool $is_active ): void {
		$this->is_active = $is_active;
	}

	/**
	 * @return string
	 */
	public function get_created_at(): string {
		return $this->created_at;
	}

	/**
	 * @param string $created_at Data.
	 * @return void
	 */
	public function set_created_at( string $created_at ): void {
		$this->created_at = $created_at;
	}

	/**
	 * @return string
	 */
	public function get_updated_at(): string {
		return $this->updated_at;
	}

	/**
	 * @param string $updated_at Data.
	 * @return void
	 */
	public function set_updated_at( string $updated_at ): void {
		$this->updated_at = $updated_at;
	}
}