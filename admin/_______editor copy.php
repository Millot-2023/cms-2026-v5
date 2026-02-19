<?php
/**
 * PROJET-CMS-2026 - ÉDITEUR DESIGN SYSTEM (VERSION STABILISÉE)
 * @author: Christophe Millot
 */

// 1. Chargement de la configuration centrale
require_once '../core/config.php';

$is_local = ($_SERVER['REMOTE_ADDR'] === '127.0.0.1' || $_SERVER['SERVER_NAME'] === 'localhost');
if (!$is_local) { die("Acces reserve."); exit; }

$content_dir = "../content/";
$trash_dir   = "../content/_trash/";

// --- LOGIQUE DE GESTION DE LA CORBEILLE ---
if (isset($_GET['action']) && isset($_GET['slug'])) {
    $action = $_GET['action'];
    $slug   = $_GET['slug'];

    if ($action === 'restore') {
        $parts = explode('_', $slug, 2);
        $original_name = isset($parts[1]) ? $parts[1] : $slug;
        if (rename($trash_dir . $slug, $content_dir . $original_name)) {
            header('Location: ' . BASE_URL . 'index.php?status=restored');
            exit;
        }
    }

    if ($action === 'purge') {
        $target = $trash_dir . $slug;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if (rmdir($target)) {
            header('Location: trash.php?status=purged');
            exit;
        }
    }
}

$slug = isset($_GET['project']) ? $_GET['project'] : '';
if (empty($slug)) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Valeurs par défaut
$title = "Titre du Projet";
$category = "Design";
$summary = "";
$cover = ""; 
$htmlContent = "";
$designSystemArray = [ 
    'h1' => [ 'fontSize' => '64px' ], 
    'h2' => [ 'fontSize' => '42px' ], 
    'h3' => [ 'fontSize' => '30px' ], 
    'h4' => [ 'fontSize' => '24px' ], 
    'h5' => [ 'fontSize' => '18px' ], 
    'p' =>  [ 'fontSize' => '18px' ] 
];

// --- CHARGEMENT HYBRIDE (ANCIENNE ET NOUVELLE MÉTHODE) ---
$data_path = $content_dir . $slug . '/data.php';
if (file_exists($data_path)) {
    $data_loaded = include $data_path;
    
    if (is_array($data_loaded)) {
        $title = $data_loaded['title'] ?? $title;
        $category = $data_loaded['category'] ?? $category;
        $summary = $data_loaded['summary'] ?? $summary;
        $cover = $data_loaded['cover'] ?? $cover;
        $htmlContent = $data_loaded['htmlContent'] ?? $htmlContent;
        $designSystemArray = $data_loaded['designSystem'] ?? $designSystemArray;
    } else {
        if (isset($title)) { $title = $title; }
        if (isset($content)) { $htmlContent = $content; }
        if (isset($designSystem)) { $designSystemArray = $designSystem; }
    }
}

$cover_path = "";
if (!empty($cover)) {
    $cover_path = (strpos($cover, 'data:image') === 0) ? $cover : $content_dir . $slug . '/' . $cover;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ÉDITEUR - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700;900&display=swap">
    <style id="dynamic-styles"></style>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #007bff; border-radius: 10px; }
        :root {
            --sidebar-bg: #000000;
            --sidebar-border: #333333;
            --sidebar-text: #ffffff;
            --sidebar-muted: #666666;
            --sidebar-input: #1a1a1a;
            --canvas-bg: #1a1a1a;
            --accent: #ffffff;
        }
        body.light-mode { --canvas-bg: #e0e0e0; --accent: #000000; }
        html, body { 
            margin: 0; padding: 0; height: 100vh; 
            overflow: hidden; 
            font-family: 'Inter', sans-serif; background-color: var(--canvas-bg); 
            color: var(--accent);
        }
        .sidebar { 
            position: fixed; top: 0; left: 0; bottom: 0; width: 340px; 
            background-color: #000000; border-right: 1px solid var(--sidebar-border); 
            display: flex; flex-direction: column; z-index: 1000; color: #ffffff;
            transition: transform 0.3s ease;
        }
        body.sidebar-hidden .sidebar { transform: translateX(-340px); }
        .sidebar-header { padding: 40px 25px 25px; border-bottom: 1px solid var(--sidebar-border); display: flex; align-items: center; gap: 15px; }
        .sidebar-header h2 { font-size: 10px; letter-spacing: 3px; text-transform: uppercase; margin: 0; color: var(--sidebar-muted); flex-grow: 1; }
        .sidebar-scroll { flex-grow: 1; overflow-y: auto; overflow-x: hidden; padding: 20px 25px; }
        .sidebar-footer { padding: 25px; border-top: 1px solid var(--sidebar-border); background-color: #000000; display: flex; flex-direction: column; gap: 10px; }
        #main-title, .paper h1 {
            font-size: 2.5rem !important; 
            line-height: 1.2 !important;
            white-space: normal !important; 
            overflow: visible !important;
            text-overflow: clip !important;
            width: 100% !important;
            display: block !important;
            margin-bottom: 1.5rem !important;
        }
        .admin-input { width: 100%; background-color: var(--sidebar-input); border: 1px solid var(--sidebar-border); color: var(--sidebar-text); padding: 12px; margin-bottom: 12px; font-size: 11px; border-radius: 4px; outline: none; display: block; }
        .section-label { font-size: 9px; color: var(--sidebar-muted); text-transform: uppercase; margin-top: 25px; margin-bottom: 10px; display: block; }
        .preview-card-container {
            width: 100%; aspect-ratio: 16/9; background: #111; border: 1px solid var(--sidebar-border);
            border-radius: 4px; overflow: hidden; margin-bottom: 8px; display: flex; align-items: center; justify-content: center;
        }
        .preview-card-container img { width: 100%; height: 100%; object-fit: cover; }
        .tool-btn { 
            background-color: var(--sidebar-input); border: 1px solid var(--sidebar-border); 
            color: var(--sidebar-muted); height: 40px; cursor: pointer; font-size: 10px; font-weight: bold;
            border-radius: 4px; transition: 0.2s; text-transform: uppercase;
            display: flex; align-items: center; justify-content: center; width: 100%;
        }
        .tool-btn:hover { border-color: #555; color: #fff; }
        .color-wrapper {
            position: relative; width: 100%; height: 40px; border: 1px solid var(--sidebar-border); 
            border-radius: 4px; overflow: hidden; cursor: pointer; 
            background: conic-gradient(red, yellow, lime, aqua, blue, magenta, red);
        }
        .color-wrapper input[type="color"] { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        .gauge-row { background-color: var(--sidebar-input); padding: 15px; border-radius: 6px; margin-bottom: 10px; border: 1px solid var(--sidebar-border); width: 100%; }
        .gauge-info { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 10px; color: var(--sidebar-muted); }
        .gauge-data { color: var(--sidebar-text); font-family: monospace; }
        input[type="range"] { width: 100%; accent-color: #fff; display: block; cursor: pointer; }
        .canvas { 
            position: absolute; top: 0; left: 340px; right: 0; bottom: 0;
            overflow-y: auto; overflow-x: hidden; padding: 40px 20px;
            transition: left 0.3s ease;
        }
        body.sidebar-hidden .canvas { left: 0; }
        .paper-toolbar { max-width: 850px; margin: 0 auto 15px auto; display: flex; justify-content: center; gap: 10px; }
        .btn-view {
            background: var(--sidebar-input); border: 1px solid var(--sidebar-border); color: var(--sidebar-muted);
            padding: 8px 15px; border-radius: 4px; font-size: 10px; cursor: pointer; text-transform: uppercase;
        }
        .btn-view.active { color: #fff; border-color: #fff; }
        .paper { 
            width: 100%; max-width: 850px; background: #ffffff; color: #000000; min-height: 1100px; padding: 100px; 
            box-shadow: 0 40px 100px rgba(0,0,0,0.5); margin: 0 auto; position: relative;
        }
        .block-container { position: relative; margin-bottom: 5px; width: 100%; clear: both; }
        .delete-block { position: absolute; left: -18px; top: 0; background: #ff4d4d; color: white; width: 18px; height: 18px; border-radius: 2px; display: flex; align-items: center; justify-content: center; font-size: 9px; cursor: pointer; opacity: 0; z-index: 10; }
        .block-container:hover .delete-block { opacity: 1; }
        .editor-grid { display: flex; flex-wrap: wrap; width: 100%; }
        .editor-grid > div { flex: 1; min-width: 200px; }
        .image-placeholder img { max-width: 100%; height: auto; display: block; }
        .sidebar-trigger { position: fixed; top: 20px; left: 20px; z-index: 500; background: var(--accent); color: var(--canvas-bg); border: none; width: 40px; height: 40px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn-publish { background:#fff; color:#000; border:none; padding:15px; font-weight:900; cursor:pointer; text-transform:uppercase; }
        .btn-exit { color:var(--sidebar-muted); text-align:center; font-size:10px; text-decoration:none; border:1px solid var(--sidebar-border); padding:10px; border-radius:4px; text-transform:uppercase; font-weight:bold; }
        .grid-structure { display: flex; flex-direction: column; gap: 8px; }
        .row-h { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
        .row-styles { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .row-align { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
        .row-float { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 8px; }
    </style>
</head>
<body class="dark-mode"> 
    <button class="sidebar-trigger" onclick="toggleSidebar()">☰</button>

    <aside class="sidebar">
        <div class="sidebar-header">
            <span style="color:#ff4d4d; cursor:pointer; font-weight:bold;" onclick="toggleSidebar()">✕</span>
            <h2>PROJET STUDIO</h2>
            <div class="theme-toggle" onclick="toggleTheme()" id="t-icon" style="cursor:pointer">🌙</div>
        </div>

        <div class="sidebar-scroll">
            <span class="section-label">APERÇU CARTE</span>
            <div class="preview-card-container" id="preview-container">
                <?php if(!empty($cover_path)): ?>
                    <img src="<?php echo $cover_path; ?>" id="img-cover-preview">
                <?php else: ?>
                    <span style="font-size:8px; color:#444;">AUCUNE IMAGE</span>
                <?php endif; ?>
            </div>
            <button class="tool-btn" style="height:30px; font-size:9px;" onclick="document.getElementById('inp-cover').click()">Changer l'image</button>
            <input type="file" id="inp-cover" style="display:none;" onchange="handleCoverChange(this)">

            <span class="section-label">MÉTADONNÉES</span>
            <input type="text" id="inp-slug" class="admin-input" value="<?php echo htmlspecialchars($slug); ?>" readonly>
            <textarea id="inp-summary" class="admin-input" placeholder="Résumé" style="height:60px;"><?php echo htmlspecialchars($summary); ?></textarea>

            <span class="section-label">TYPOGRAPHIE</span>
            <div class="grid-structure">
                <div class="row-h">
                    <button class="tool-btn" onclick="addBlock('h1', 'Titre H1')">H1</button>
                    <button class="tool-btn" onclick="addBlock('h2', 'Titre H2')">H2</button>
                    <button class="tool-btn" onclick="addBlock('h3', 'Titre H3')">H3</button>
                    <button class="tool-btn" onclick="addBlock('h4', 'Titre H4')">H4</button>
                    <button class="tool-btn" onclick="addBlock('h5', 'Titre H5')">H5</button>
                </div>
                <button class="tool-btn" onclick="addBlock('p')">Paragraphe</button>
                <div class="row-styles">
                    <button class="tool-btn" onclick="execStyle('bold')">B</button>
                    <button class="tool-btn" onclick="execStyle('italic')">I</button>
                    <div class="color-wrapper"><input type="color" oninput="changeTextColor(this.value)"></div>
                </div>
                <div class="row-align">
                    <button class="tool-btn" onclick="execStyle('justifyLeft')">L</button>
                    <button class="tool-btn" onclick="execStyle('justifyCenter')">C</button>
                    <button class="tool-btn" onclick="execStyle('justifyRight')">R</button>
                    <button class="tool-btn" onclick="execStyle('justifyFull')">J</button>
                </div>
            </div>

            <span class="section-label">RÉGLAGES : <span id="target-label" style="color:#fff">H1</span></span>
            <div class="gauge-row">
                <div class="gauge-info"><span>TAILLE POLICE</span><span class="gauge-data"><span id="val-size">64</span>px</span></div>
                <input type="range" id="slider-size" min="8" max="120" value="64" oninput="updateStyle('fontSize', this.value+'px', 'val-size')">
            </div>

            <span class="section-label">DISPOSITION (FLOAT)</span>
            <div class="row-float">
                <button class="tool-btn" onclick="addFloatBlock('left')">GAUCHE</button>
                <button class="tool-btn" onclick="addFloatBlock('full')">LARGE</button>
                <button class="tool-btn" onclick="addFloatBlock('right')">DROITE</button>
            </div>
            <div class="gauge-row">
                <div class="gauge-info"><span>IMAGE WIDTH</span><span class="gauge-data"><span id="val-img-width">40</span>%</span></div>
                <input type="range" id="slider-img-width" min="10" max="100" value="40" oninput="updateImageWidth(this.value)">
            </div>

            <span class="section-label">STRUCTURE (GRILLE)</span>
            <div class="row-h" style="margin-bottom:8px;">
                <button class="tool-btn" onclick="addGridBlock(2)">COL 2</button>
                <button class="tool-btn" onclick="addGridBlock(3)">COL 3</button>
            </div>
            <div class="gauge-row">
                <div class="gauge-info"><span>GUTTER</span><span class="gauge-data"><span id="val-gutter">20</span>px</span></div>
                <input type="range" id="slider-gutter" min="0" max="100" value="20" oninput="updateGutter(this.value)">
            </div>
        </div>

        <div class="sidebar-footer">
            <button onclick="publishProject()" class="btn-publish">PUBLIER</button>
            <a href="<?php echo BASE_URL; ?>index.php" class="btn-exit">QUITTER</a>
        </div>
    </aside>

    <main class="canvas">
        <div class="paper-toolbar">
            <button class="btn-view" onclick="setPaperWidth('375px', this)">Mobile</button>
            <button class="btn-view" onclick="setPaperWidth('768px', this)">Tablette</button>
            <button class="btn-view active" onclick="setPaperWidth('850px', this)">Desktop</button>
        </div>
        <article class="paper" id="paper">
            <div class="block-container">
                <div class="delete-block" onclick="this.parentElement.remove()">✕</div>
                <h1 id="main-title" contenteditable="true" onfocus="setTarget('h1')"><?php echo htmlspecialchars($title); ?></h1>
            </div>
            <div id="editor-core"><?php echo $htmlContent; ?></div>
        </article>
    </main>

    <script>
    let currentTag = 'h1';
    let currentImageElement = null;
    let designSystem = <?php echo json_encode($designSystemArray); ?>;
    let currentGutter = '20px';
    // CORRECTIF : Récupération de l'image de couverture existante
    let coverData = "<?php echo (strpos($cover, 'data:image') === 0) ? $cover : $cover_path; ?>"; 
    const LOREM_TEXT = "Lorem ipsum dolor sit amet, consectetur adipiscing elit...";

    function renderStyles() {
        let css = "";
        for (let tag in designSystem) {
            css += `.paper ${tag}, #main-title { font-size: ${designSystem[tag].fontSize}; margin-bottom:0.5em; outline:none; }\n`;
        }
        css += `.editor-grid { gap: ${currentGutter}; }`;
        document.getElementById('dynamic-styles').innerHTML = css;
    }

    // --- CORRECTIF DE PERSISTANCE : RÉACTIVATION DES ÉVÉNEMENTS ---
    function attachEventsToExistingBlocks() {
        // Rétablir les focus sur les titres et paragraphes
        document.querySelectorAll('#editor-core [contenteditable="true"]').forEach(el => {
            el.onfocus = () => setTarget(el.tagName.toLowerCase());
        });

        // Rétablir les événements sur les images existantes
        document.querySelectorAll('.image-placeholder').forEach(placeholder => {
            const img = placeholder.querySelector('img');
            if (img) {
                img.onclick = (event) => { event.stopPropagation(); setTarget('img', placeholder); };
                img.ondblclick = (event) => { event.stopPropagation(); triggerUpload(placeholder); };
            } else {
                placeholder.onclick = () => { setTarget('img', placeholder); };
                placeholder.ondblclick = () => { triggerUpload(placeholder); };
            }
        });
    }

    function setPaperWidth(w, btn) {
        document.querySelectorAll('.btn-view').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const p = document.getElementById('paper');
        p.style.maxWidth = w;
        p.style.padding = (w === '850px') ? '100px' : '40px 20px';
    }

    function handleCoverChange(input) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                coverData = e.target.result;
                document.getElementById('preview-container').innerHTML = `<img src="${coverData}">`;
            };
            reader.readAsDataURL(file);
        }
    }

    function updateStyle(prop, val, displayId) {
        if(designSystem[currentTag]) {
            designSystem[currentTag][prop] = val; 
            document.getElementById(displayId).innerText = val.replace('px', ''); 
            renderStyles();
        }
    }

    function updateGutter(val) {
        currentGutter = val + 'px';
        document.getElementById('val-gutter').innerText = val;
        renderStyles();
    }

    function updateImageWidth(val) {
        if(currentImageElement) {
            currentImageElement.style.width = val + '%';
            document.getElementById('val-img-width').innerText = val;
        }
    }

    function setTarget(tag, imgEl = null) {
        currentTag = tag;
        currentImageElement = imgEl;
        document.getElementById('target-label').innerText = tag.toUpperCase();
        if(designSystem[tag]) {
            let val = parseInt(designSystem[tag].fontSize);
            document.getElementById('slider-size').value = val;
            document.getElementById('val-size').innerText = val;
        }
    }

    function addBlock(tag, txt = LOREM_TEXT) {
        const container = document.createElement('div');
        container.className = 'block-container';
        container.innerHTML = `<div class="delete-block" onclick="this.parentElement.remove()">✕</div><${tag} contenteditable="true" onfocus="setTarget('${tag}')">${txt}</${tag}>`;
        document.getElementById('editor-core').appendChild(container);
        container.querySelector(tag).focus();
    }

    function addFloatBlock(type) {
        const container = document.createElement('div');
        container.className = 'block-container';
        let width = (type === 'full') ? "100%" : "40%";
        let style = (type === 'left') ? `float:left; margin:0 20px 10px 0; width:${width};` : (type === 'right') ? `float:right; margin:0 0 10px 20px; width:${width};` : `width:${width}; margin-bottom:20px; clear:both;`;
        
        container.innerHTML = `
            <div class="delete-block" onclick="this.parentElement.remove()">✕</div>
            <div class="image-placeholder" onclick="setTarget('img', this); event.stopPropagation();" ondblclick="triggerUpload(this)" 
                 style="${style} background:#eee; aspect-ratio:16/9; display:flex; align-items:center; justify-content:center; cursor:pointer; overflow:hidden; position:relative;">
                IMAGE <input type="file" style="display:none;" onchange="handleImageSelect(this)">
            </div>
            <p contenteditable="true" onfocus="setTarget('p')">${LOREM_TEXT}</p>`;
        document.getElementById('editor-core').appendChild(container);
    }

    function addGridBlock(cols) {
        const container = document.createElement('div');
        container.className = 'block-container';
        let items = "";
        for(let i=0; i<cols; i++) {
            items += `<div style="flex:1"><p contenteditable="true" onfocus="setTarget('p')">${LOREM_TEXT}</p></div>`;
        }
        container.innerHTML = `<div class="delete-block" onclick="this.parentElement.remove()">✕</div><div class="editor-grid">${items}</div>`;
        document.getElementById('editor-core').appendChild(container);
    }

    function triggerUpload(el) { el.querySelector('input').click(); }

    function handleImageSelect(input) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const placeholder = input.parentElement;
                placeholder.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:cover;"><input type="file" style="display:none;" onchange="handleImageSelect(this)">`;
                const img = placeholder.querySelector('img');
                img.onclick = (event) => { event.stopPropagation(); setTarget('img', placeholder); };
                img.ondblclick = (event) => { event.stopPropagation(); triggerUpload(placeholder); };
                setTarget('img', placeholder);
            };
            reader.readAsDataURL(file);
        }
    }

    function toggleSidebar() { document.body.classList.toggle('sidebar-hidden'); }
    function execStyle(cmd) { document.execCommand(cmd, false, null); }
    function changeTextColor(color) { document.execCommand('foreColor', false, color); }
    function toggleTheme() { 
        document.body.classList.toggle('light-mode'); 
        document.getElementById('t-icon').innerText = document.body.classList.contains('light-mode') ? '☀️' : '🌙';
    }

    function publishProject() {
        const formData = new FormData();
        let titleElement = document.getElementById('main-title');
        const projectTitle = titleElement ? titleElement.innerText : "Nouveau Projet";

        formData.append('slug', document.getElementById('inp-slug').value);
        formData.append('designSystem', JSON.stringify(designSystem));
        formData.append('htmlContent', document.getElementById('editor-core').innerHTML);
        formData.append('title', projectTitle); 
        formData.append('summary', document.getElementById('inp-summary').value);
        formData.append('coverImage', coverData); 
        
        fetch('save.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            alert(res.message);
        })
        .catch(err => {
            console.error(err);
            alert("Erreur de liaison !");
        });
    }

    // INITIALISATION : Rendu et Rattachement des événements
    window.addEventListener('DOMContentLoaded', () => { 
        renderStyles(); 
        attachEventsToExistingBlocks();
    });
    </script>
</body>
</html>