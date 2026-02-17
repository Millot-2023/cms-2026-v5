<?php
/**
 * CMS-2026 v4.0 - Configuration racine (Version Robuste)
 * @author: Christophe Millot
 */

// 1. Protocole
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// 2. Calcul du dossier projet
// On détecte dynamiquement le dossier parent (cms-2026-v4)
$script_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$root_folder = preg_replace('#/(core|admin|includes).*$#i', '', $script_path);
$root_folder = rtrim($root_folder, '/') . '/';

// 3. Définition des constantes
define('SITE_NAME', 'CMS-2026 v5.0'); // Mis à jour en v4.0
define('BASE_URL', $protocol . $host . $root_folder); 
define('ASSETS_URL', BASE_URL . 'assets/');
define('INC_PATH', __DIR__ . '/../includes/');

// Gestion des dates
date_default_timezone_set('Europe/Paris');
setlocale(LC_TIME, 'fr_FR.utf8', 'fra');
?>