<?php
/**
 * Desinstalação do plugin.
 *
 * Executado quando o plugin é deletado via painel WordPress.
 *
 * @package SicaProductModels
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Remove a tabela customizada.
$table_name = $wpdb->prefix . 'sica_product_models';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

// Remove options do plugin.
delete_option( 'spm_schema_version' );