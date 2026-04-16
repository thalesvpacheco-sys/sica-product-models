<?php
/**
 * Loader central de hooks do plugin.
 *
 * @package SicaProductModels\Core
 */

namespace SicaProductModels\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Loader.
 */
class Loader {

	/**
	 * Lista de actions registradas.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected array $actions = array();

	/**
	 * Lista de filters registrados.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	protected array $filters = array();

	/**
	 * Adiciona uma action à fila.
	 *
	 * @param string $hook Nome do hook.
	 * @param object $component Instância da classe.
	 * @param string $callback Método a ser executado.
	 * @param int    $priority Prioridade.
	 * @param int    $accepted_args Quantidade de argumentos aceitos.
	 *
	 * @return void
	 */
	public function add_action(
		string $hook,
		object $component,
		string $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$this->actions[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}

	/**
	 * Adiciona um filter à fila.
	 *
	 * @param string $hook Nome do hook.
	 * @param object $component Instância da classe.
	 * @param string $callback Método a ser executado.
	 * @param int    $priority Prioridade.
	 * @param int    $accepted_args Quantidade de argumentos aceitos.
	 *
	 * @return void
	 */
	public function add_filter(
		string $hook,
		object $component,
		string $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$this->filters[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}

	/**
	 * Registra todos os hooks no WordPress.
	 *
	 * @return void
	 */
	public function run(): void {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}