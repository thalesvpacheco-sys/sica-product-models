<?php
/**
 * Helper de sanitização centralizado.
 *
 * @package SicaProductModels\Helpers
 */

namespace SicaProductModels\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Sanitizer.
 */
class Sanitizer {

	/**
	 * Sanitiza o nome de um modelo.
	 *
	 * @param string $value Valor bruto.
	 * @return string
	 */
	public static function sanitize_model_name( string $value ): string {
		return sanitize_text_field( $value );
	}

	/**
	 * Sanitiza o slug de um modelo.
	 *
	 * @param string $value Valor bruto.
	 * @return string
	 */
	public static function sanitize_model_slug( string $value ): string {
		return sanitize_title( $value );
	}

	/**
	 * Sanitiza conteúdo HTML (permite tags seguras).
	 *
	 * @param string $value Valor bruto.
	 * @return string
	 */
	public static function sanitize_html_content( string $value ): string {
		return wp_kses_post( $value );
	}

	/**
	 * Sanitiza array de IDs de imagens, removendo zeros e negativos.
	 *
	 * @param array<mixed> $ids IDs brutos.
	 * @return array<int>
	 */
	public static function sanitize_image_ids( array $ids ): array {
		return array_values(
			array_filter(
				array_map( 'absint', $ids ),
				function ( $v ) {
					return $v > 0;
				}
			)
		);
	}

	/**
	 * Sanitiza uma string CSV de IDs de imagens para array.
	 *
	 * @param string $csv CSV bruto (ex: "12,45,0,7").
	 * @return array<int>
	 */
	public static function sanitize_gallery_csv( string $csv ): array {
		$csv = trim( $csv );

		if ( '' === $csv ) {
			return array();
		}

		return self::sanitize_image_ids(
			array_map( 'trim', explode( ',', $csv ) )
		);
	}
}