<?php
/**
 * Rotina de desativação do plugin.
 *
 * @package SicaProductModels\Core
 */

namespace SicaProductModels\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Deactivator.
 */
class Deactivator {

	/**
	 * Executa na desativação.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Intencionalmente vazio na V1.
		// Nada é removido na desativação.
	}
}