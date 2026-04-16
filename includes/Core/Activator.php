<?php
/**
 * Rotina de ativação do plugin.
 *
 * @package SicaProductModels\Core
 */

namespace SicaProductModels\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Activator.
 */
class Activator {

	/**
	 * Executa na ativação.
	 *
	 * @return void
	 */
	public static function activate(): void {
		Installer::install();
	}
}