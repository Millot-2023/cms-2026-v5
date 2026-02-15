/**
 * PROJET-CMS-2026 - MOTEUR ÉDITEUR V3 (STRICT)
 * @author: Christophe Millot
 * Rôle : Pilotage du Canvas via Iframe + Design System Persistant
 */

const studio = {
    iframe: document.getElementById('viewport'),
    
    get canvas() {
        return this.iframe.contentDocument || this.iframe.contentWindow.document;
    },

    init() {
        console.log("Studio Engine V3 : Design System Actif.");
        this.bindEvents();
    },

    bindEvents() {
        // Bouton Sauvegarder
        const saveBtn = document.getElementById('btn-save');
        if (saveBtn) saveBtn.addEventListener('click', () => this.publish());

        // Slider Taille de Police (Design System)
        const sizeSlider = document.getElementById('slider-size');
        if (sizeSlider) {
            sizeSlider.addEventListener('input', (e) => {
                const size = e.target.value + 'px';
                this.applyStyleToSelection('fontSize', size);
            });
        }
    },

    // Applique le style dynamiquement à l'élément sélectionné ou au dernier bloc
    applyStyleToSelection(property, value) {
        const selection = this.canvas.getSelection();
        let target = null;

        if (selection.rangeCount > 0) {
            target = selection.anchorNode.parentElement;
        }

        // Si la sélection est bien dans la zone éditable
        if (target && target.closest('#paper-viewport')) {
            target.style[property] = value;
        }
    },

    // AJOUT : Bascule la classe lettrine
    toggleLettrine() {
        const sel = this.iframe.contentWindow.getSelection();
        if (sel.rangeCount > 0) {
            const range = sel.getRangeAt(0);
            let target = range.commonAncestorContainer;

            if (target.nodeType === 3) target = target.parentElement;

            const block = target.closest('p') || target.closest('.column');

            if (block) {
                block.classList.toggle('has-lettrine');
                console.log("Classe appliquée sur :", block.tagName);
            }
        } else {
            console.warn("Aucune sélection trouvée dans l'iframe.");
        }
    },
    




addBlock(tag) {
    const target = this.canvas.getElementById('editable-core');
    if (!target) return;

    const block = this.canvas.createElement(tag === 'image' ? 'div' : tag);
    
    if (tag === 'image') {
        block.className = "image-block-wrapper";
        // On utilise explicitement triggerContentImage pour les blocs ajoutés
        block.innerHTML = `<img src="../assets/img/image-template.png" 
                            onclick="window.parent.studio.triggerContentImage(this);" 
                            style="width:100%; border-radius:8px; margin:20px 0; cursor:pointer;">`;
    } else if (tag === 'p') {
        block.innerText = "Nouveau paragraphe éditable...";
    } else {
        block.innerText = "Nouveau titre " + tag.toUpperCase();
    }

    block.setAttribute('contenteditable', 'true');
    target.appendChild(block);
    
    setTimeout(() => block.focus(), 10);
    block.scrollIntoView({ behavior: 'smooth', block: 'center' });
},

// Cette méthode doit être unique pour le contenu interne
triggerContentImage(imgEl) {
    this.lastImgSelected = imgEl; // Capture la cible précise (Viewer ou Float)
    const input = window.parent.document.getElementById('inp-block-img');
    if (input) input.click();
},









    // Stocke temporairement l'image en cours de modification
    setCurrentImgTarget(imgEl) {
        this.lastImgSelected = imgEl;
    },

    publish() {
        const slug = document.getElementById('inp-slug').value;
        const title = this.canvas.getElementById('editable-title').innerText;
        const html = this.canvas.getElementById('editable-core').innerHTML;
        const summary = document.getElementById('inp-summary').value;
        
        // On récupère la valeur de cover stockée globalement dans editor.php
        const cover = window.coverData || '';

        const designSettings = {
            mainTitleSize: this.canvas.getElementById('editable-title').style.fontSize || 'default'
        };

        const formData = new FormData();
        formData.append('slug', slug);
        formData.append('title', title);
        formData.append('summary', summary);
        formData.append('htmlContent', html);
        formData.append('cover', cover);
        formData.append('designSystem', JSON.stringify(designSettings));

        const btn = document.getElementById('btn-save');
        const originalText = btn.innerText;
        btn.innerText = "SAUVEGARDE...";
        btn.disabled = true;

        fetch('save.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                alert("Confirmation : " + data.message);
                btn.innerText = originalText;
                btn.disabled = false;
            })
            .catch(err => {
                console.error("Erreur Studio:", err);
                btn.innerText = originalText;
                btn.disabled = false;
            });
    }
};

studio.init();
window.studio = studio;