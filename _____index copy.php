<?php
/**
 * PROJET-CMS-2026 - ARCHITECTURE NETTOYÉE
 * Focus : Correction structurelle et restauration des accès (Version Stabilisée)
 * @author: Christophe Millot
 */
require_once 'core/config.php';
require_once 'includes/header.php'; 
require_once 'includes/hero.php'; 
?>

<div class="master-grid">
    <main id="main">
        <h1 class="section-title">Gestion des Projets</h1>

        <div class="grid-container">
            
            <div class="grid-block">
                <div class="card-image">
                    <img src="<?php echo ASSETS_URL; ?>img/image-template.png" alt="Modèle de projet">
                </div>
                
                <div class="card-content">
                    <header class="article-header">
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">
                            <span class="category" style="font-weight: bold; color: #888;">SYSTÈME</span>
                            <p class="date" style="margin: 0; color: #666;"><?php echo date('d.m.Y'); ?></p>
                        </div>
                        <h1 class="main-article-title">Nouveau Projet</h1>
                    </header>
                    <p>Initialiser un nouvel article ou une nouvelle étude de cas dans le CMS.</p>
                    <div class="card-footer" style="margin-top: auto;">
                        <a href="<?php echo BASE_URL; ?>admin/editor.php?project=nouveau-projet-<?php echo time(); ?>" class="btn-create" style="display: block; text-align: center; padding: 0.8rem; border-radius: 8px; text-decoration: none; font-weight: bold; border: 1px solid #444;">
                            CRÉER
                        </a>
                    </div>
                </div>
            </div>

            <?php
            $content_path = 'content/';
            if (is_dir($content_path)) {
                // On liste les dossiers du premier niveau uniquement
                $folders = array_diff(scandir($content_path), array('..', '.', '_trash'));
                
                foreach ($folders as $folder) {
                    $project_dir = $content_path . $folder;
                    if (!is_dir($project_dir)) continue;

                    $data_file = $project_dir . '/data.php';
                    
                    // Initialisation des données par défaut (si data.php est absent)
                    $title = $folder;
                    $category = "PROJET";
                    $date = date("d.m.Y", filemtime($project_dir));
                    $cover = "";
                    $summary = "Dossier de projet : " . $folder;

                    // On charge data.php seulement s'il existe
                    if (file_exists($data_file)) {
                        $data_loaded = include $data_file;
                        if (is_array($data_loaded)) {
                            $title    = $data_loaded['title'] ?? $folder;
                            $category = $data_loaded['category'] ?? "Non classé";
                            if (!empty($data_loaded['date']) && $data_loaded['date'] !== "--/--/--") {
                                $date = $data_loaded['date'];
                            }
                            $cover    = $data_loaded['cover'] ?? "";
                            $summary  = $data_loaded['summary'] ?? $summary;
                        }
                    }

                    // GESTION DE L'IMAGE
                    $image_src = ASSETS_URL . "img/image-template.png";
                    if (!empty($cover)) {
                        if (strpos($cover, 'data:image') === 0) {
                            $image_src = $cover;
                        } else {
                            $image_src = BASE_URL . "content/" . $folder . "/" . basename($cover);
                        }
                    }
                    ?>
                    <article class="grid-block" style="position: relative;">
                        <a href="javascript:void(0);" 
                           onclick="confirmDelete('<?php echo addslashes($folder); ?>')" 
                           class="btn-trash-overlay" 
                           title="Supprimer définitivement">×</a>

                        <div class="card-image">
                            <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($title); ?>">
                        </div>
                        
                        <div class="card-content">
                            <header class="article-header">
                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <span class="category" style="font-weight: bold; color: #888;"><?php echo htmlspecialchars($category); ?></span>
                                    <p class="date" style="margin: 0; color: #666;"><?php echo htmlspecialchars($date); ?></p>
                                </div>
                                <h1 class="main-article-title"><?php echo htmlspecialchars($title); ?></h1>
                            </header>
                            
                            <p><?php echo mb_strimwidth(strip_tags($summary), 0, 140, "..."); ?></p>
                            
                            <div class="card-footer" style="display: flex; align-items: center; gap: 10px; margin-top: auto;">
                                <div style="display: flex; flex: 1; gap: 10px;">
                                    <a href="<?php echo BASE_URL; ?>admin/editor.php?project=<?php echo urlencode($folder); ?>" class="btn-open" style="flex: 1; text-align: center; border: 1px solid #555; padding: 0.6rem; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.75rem;">
                                        ÉDITER
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>article.php?slug=<?php echo urlencode($folder); ?>" class="btn-open" style="flex: 1; text-align: center; border: 1px solid #555; padding: 0.6rem; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 0.75rem;">
                                        LIRE
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php
                }
            }
            ?>
        </div> 
    </main>
</div>

<script>
function confirmDelete(slug) {
    const confirmation = confirm("ALERTE SÉCURITÉ - Christophe :\n\nConfirmer la suppression du dossier [" + slug + "] ?");
    if (confirmation) {
        window.location.href = "<?php echo BASE_URL; ?>admin/delete.php?project=" + encodeURIComponent(slug);
    }
}
</script>

<style>
.btn-trash-overlay {
    position: absolute; top: 10px; right: 10px; width: 28px; height: 28px; background: #000; color: #fff;
    text-decoration: none; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 20px; font-weight: bold; z-index: 100; transition: all 0.2s ease; border: 1px solid rgba(255,255,255,0.2);
}
.btn-trash-overlay:hover { background: #ff0000; transform: scale(1.1); }
</style>

<?php require_once 'includes/footer.php'; ?>