<?php
/**
 * Responsável pela instalação e atualização de schema do plugin.
 *
 * @package SicaProductModels\Core
 */

namespace SicaProductModels\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Installer.
 */
class Installer {

	/**
	 * Nome da option de versão do schema.
	 *
	 * @var string
	 */
	public const SCHEMA_VERSION_OPTION = 'spm_schema_version';

	/**
	 * Versão atual do schema.
	 *
	 * @var string
	 */
	public const SCHEMA_VERSION = '0.1.0';

	/**
	 * Retorna o nome da tabela principal.
	 *
	 * @return string
	 */
	public static function get_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'sica_product_models';
	}

	/**
	 * Executa instalação/atualização do schema.
	 *
	 * @return void
	 */
	public static function install(): void {
		self::create_tables();
		update_option( self::SCHEMA_VERSION_OPTION, self::SCHEMA_VERSION );
	}

	/**
	 * Cria/atualiza tabelas do plugin.
	 *
	 * @return void
	 */
	protected static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			product_id BIGINT UNSIGNED NOT NULL,
			attribute_term_id BIGINT UNSIGNED NULL DEFAULT NULL,
			name VARCHAR(200) NOT NULL,
			slug VARCHAR(200) NOT NULL,
			short_description TEXT NULL,
			description LONGTEXT NULL,
			featured_image_id BIGINT UNSIGNED NULL DEFAULT NULL,
			gallery_image_ids LONGTEXT NULL,
			menu_order INT NOT NULL DEFAULT 0,
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY product_id (product_id),
			KEY product_id_order (product_id, menu_order),
			UNIQUE KEY product_slug (product_id, slug)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}