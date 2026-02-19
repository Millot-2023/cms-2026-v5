<?php
/**
 * PROJET-CMS-2026 - MOTEUR DE SAUVEGARDE V4 (Modifiée pour Upload Physique)
 */

header('Content-Type: application/json');
require_once '../core/config.php';

// Sécurité locale
$is_local = ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['SERVER_NAME'] === 'localhost');
if (!$is_local) {
    echo json_encode(['status' => 'error', 'message' => 'Accès refusé.']);
    exit;
}

// ---------------------------------------------------------
// AJOUT : Traitement de l'upload d'image physique (Standard $_FILES)
// ---------------------------------------------------------
if (isset($_POST['action']) && $_POST['action'] === 'upload_image' && isset($_FILES['image'])) {
    $slug = $_POST['slug'] ?? '';
    $dir = "../content/" . $slug;
    
    if (!file_exists($dir)) { 
        mkdir($dir, 0777, true); 
    }

    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $fileName = "img_" . time() . "." . $ext;
    $target = $dir . "/" . $fileName;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        // On retourne l'URL relative pour l'affichage dans l'éditeur
        echo json_encode(["success" => true, "url" => "content/" . $slug . "/" . $fileName, "fileName" => $fileName]);
    } else {
        echo json_encode(["success" => false, "message" => "Erreur lors du déplacement du fichier."]);
    }
    exit; 
}
// ---------------------------------------------------------

// 1. Récupération des données
$data = $_POST;
$slug = $data['slug'] ?? '';

if (empty($slug)) {
    echo json_encode(['status' => 'error', 'message' => 'Le slug est manquant.']);
    exit;
}

// On garde le slug exact envoyé par l'éditeur (qui correspond au nom du dossier)
$dir = "../content/" . $slug;

if (!file_exists($dir)) { 
    mkdir($dir, 0777, true); 
}

$file_path = $dir . "/data.php";

// 2. Récupération des données existantes
$existingData = [];
if (file_exists($file_path)) {
    $loaded = @include $file_path;
    if (is_array($loaded)) { $existingData = $loaded; }
}

// 3. Traitement du Design System (On garde l'existant si vide)
$ds = $data['designSystem'] ?? [];
if(is_string($ds)) { $ds = json_decode($ds, true); }
if(empty($ds)) { $ds = $existingData['designSystem'] ?? []; }

// 4. Gestion de la cover (Rétro-compatibilité Base64 conservée)
$coverValue = $data['cover'] ?? ($existingData['cover'] ?? '');

if (strpos($coverValue, 'data:image') === 0) {
    list($type, $imgData) = explode(';', $coverValue);
    list(, $imgData)      = explode(',', $imgData);
    $imgData = base64_decode($imgData);
    $ext = (strpos($type, 'png') !== false) ? 'png' : 'jpg';
    $fileName = "cover." . $ext;
    file_put_contents($dir . "/" . $fileName, $imgData);
    $coverValue = $fileName;
}

// 5. Préparation du tableau final
$finalData = [
    'title'        => $data['title'] ?? ($existingData['title'] ?? 'Sans titre'),
    'cover'        => $coverValue,
    'category'     => $data['category'] ?? ($existingData['category'] ?? 'Design'),
    'date'         => $existingData['date'] ?? date('d.m.Y'),
    'updated'      => date('Y-m-d H:i:s'),
    'summary'      => $data['summary'] ?? ($existingData['summary'] ?? ''),
    'designSystem' => $ds,
    'htmlContent'  => $data['htmlContent'] ?? ''
];

// 6. Écriture du fichier
$content_file = "<?php\n/** Fichier généré par Studio CMS - " . date('d.m.Y H:i') . " **/\n";
$content_file .= "return " . var_export($finalData, true) . ";\n";
$content_file .= "?>";

if (file_put_contents($file_path, $content_file)) {
    echo json_encode(["status" => "success", "message" => "Projet enregistré avec succès dans " . $slug]);
} else {
    echo json_encode(["status" => "error", "message" => "Erreur d'écriture dans : " . $file_path]);
}