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

if ( ! defined( 'SPM_FORCE_UNINSTALL' ) || SPM_FORCE_UNINSTALL !== true ) {
	delete_option( 'spm_schema_version' );
	return;
}

$table_name = $wpdb->prefix . 'sica_product_models';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

delete_option( 'spm_schema_version' );