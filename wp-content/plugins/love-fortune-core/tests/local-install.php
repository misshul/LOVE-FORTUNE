<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){exit;}
define('WP_INSTALLING',true);
$_SERVER['HTTP_HOST']='localhost:8086';$_SERVER['REQUEST_URI']='/';
require '/var/www/html/wp-load.php';
if(wp_get_environment_type()!=='local'){throw new RuntimeException('LOCAL_ONLY');}
require_once ABSPATH.'wp-admin/includes/upgrade.php';
require_once ABSPATH.'wp-admin/includes/plugin.php';
if(!is_blog_installed()){wp_install('Compatibility validation','local-validation','synthetic@example.invalid',false,'',bin2hex(random_bytes(32)));}
$r=activate_plugin('love-fortune-core/love-fortune-core.php');if(is_wp_error($r)){throw new RuntimeException('ACTIVATION_FAILED');}
global $wp_version,$wpdb;
$out=['PHP_VERSION'=>PHP_VERSION,'PHP_INT_SIZE'=>PHP_INT_SIZE,'WordPress'=>$wp_version,'MySQL'=>$wpdb->get_var('SELECT VERSION()')];
foreach(['memory_limit','max_execution_time','max_input_time','post_max_size'] as $key){$out[$key]=ini_get($key);}
echo json_encode($out,JSON_THROW_ON_ERROR).PHP_EOL;
