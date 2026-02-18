function exportForGmail() {
    const core = document.getElementById('editor-core');
    if (!core) return;

    let temp = core.cloneNode(true);

    // 1. Nettoyage interface
    temp.querySelectorAll('.delete-block').forEach(el => el.remove());
    temp.querySelectorAll('[contenteditable]').forEach(el => el.removeAttribute('contenteditable'));

    // --- CAPTURE ALIGNEMENTS IMAGES ---
    const imgData = [];
    temp.querySelectorAll('.block-float img').forEach((img, i) => {
        const parent = img.closest('.block-float');
        imgData[i] = parent && parent.classList.contains('left') ? 'left' : 
                     parent && parent.classList.contains('right') ? 'right' : 'none';
    });

    // 2. RESET TOTAL
    temp.querySelectorAll('*').forEach(el => el.removeAttribute('style'));

    // 3. RÉINJECTION DESIGN SYSTEM
    const defaultPSize = designSystem['p'] ? designSystem['p'].fontSize : "18px";

    for (let tag in designSystem) {
        temp.querySelectorAll(tag).forEach(el => {
            if (el.classList.contains('has-lettrine')) return;
            el.style.fontSize = designSystem[tag].fontSize || "16px";
            el.style.fontFamily = "Arial, sans-serif";
            el.style.lineHeight = "1.6";
            el.style.color = "#333333";
            el.style.textAlign = "left";
            if (!el.closest('.col-item')) {
                el.style.marginBottom = "20px";
                el.style.display = "block";
            }
        });
    }

    // 4. LES LETTRINES
    temp.querySelectorAll('.has-lettrine').forEach(el => {
        const pureText = el.textContent.trim();
        if (!pureText) return;
        const first = pureText.charAt(0);
        const rest = pureText.slice(1);
        el.innerHTML = `
            <div style="text-align: left; line-height: 1.6 !important;">
                <span style="float: left; font-size: 72px; line-height: 55px; margin-top: 5px; margin-right: 12px; font-weight: bold; color: #000000; font-family: Georgia, serif; display: block;">${first}</span>
                <span style="font-size: ${defaultPSize}; line-height: 1.6 !important; color: #333333; font-family: Arial, sans-serif; display: inline;">${rest}</span>
                <div style="clear: both; line-height: 0;"></div>
            </div>`.trim();
        el.style.marginBottom = "20px";
        el.style.display = "block";
    });

    // 5. STRUCTURE DES COLONNES
    temp.querySelectorAll('.grid-wrapper').forEach(grid => {
        const cols = grid.querySelectorAll('.col-item');
        if (cols.length === 0) return;
        const widthPerCol = (100 / cols.length).toFixed(2);

        let tableHTML = `<table width="100%" border="0" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse;"><tr>`;
        cols.forEach(col => {
            tableHTML += `
                <td width="${widthPerCol}%" valign="top" style="width:${widthPerCol}%; padding:0 15px; vertical-align:top; text-align:left; line-height: 1.6; font-size: ${defaultPSize}; color: #333333; font-family: Arial, sans-serif;">
                    ${col.innerHTML}
                </td>`;
        });
        tableHTML += `</tr></table>`;
        grid.innerHTML = tableHTML;
        grid.style.marginBottom = "20px";
    });

    // 6. FIX IMAGES : URL ABSOLUES + ALIGNEMENT
    // On récupère l'URL racine de ton projet (ex: http://localhost/cms-2026-v5/)
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/');

    temp.querySelectorAll('img').forEach((img, i) => {
        // Transformation de l'URL pour Gmail
        if (!img.src.startsWith('http')) {
            const currentSrc = img.getAttribute('src');
            img.src = baseUrl + currentSrc;
        }

        // Style forcé pour le rendu
        img.style.maxWidth = "100%";
        img.style.height = "auto";
        
        if (img.closest('.block-float')) {
            img.style.maxWidth = "200px";
            img.style.display = "inline";
            if (imgData[i] === 'left') {
                img.style.float = "left";
                img.style.marginRight = "20px";
            } else if (imgData[i] === 'right') {
                img.style.float = "right";
                img.style.marginLeft = "20px";
            }
            img.style.marginBottom = "10px";
        }
    });

    const styleContent = `
        body { margin: 0; padding: 0; background: #f4f4f4; }
        .container { max-width: 800px; margin: 0 auto; background: #ffffff; padding: 40px; }
    `;

    // 7. BOUTON AVEC COPIE "RICH TEXT"
    const buttonHTML = `
        <div id="copy-tool" style="text-align:center; margin-top:40px; padding:20px; border-top:1px solid #eee; font-family:Arial, sans-serif;">
            <button id="btn-exec-copy" style="background:#4285f4; color:white; border:none; padding:15px 30px; border-radius:5px; cursor:pointer; font-weight:bold; font-size:16px;">
                📋 Copier pour Gmail
            </button>
        </div>`;

    // 8. OUVERTURE DU BLANK ET SCRIPT DE COPIE
    const win = window.open("", "_blank");
    if (win) {
        win.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <style>${styleContent}</style>
            </head>
            <body>
                <div class="container" id="mail-content">${temp.innerHTML}</div>
                ${buttonHTML}
                <script>
                    document.getElementById('btn-exec-copy').addEventListener('click', async function() {
                        const content = document.getElementById('mail-content');
                        
                        // Utilisation de l'API Clipboard pour garder le formattage HTML
                        const type = "text/html";
                        const blob = new Blob([content.innerHTML], { type });
                        const data = [new ClipboardItem({ [type]: blob })];

                        try {
                            await navigator.clipboard.write(data);
                            this.innerText = '✅ Contenu prêt !';
                            this.style.background = '#34a853';
                        } catch (err) {
                            // Fallback à l'ancienne si le navigateur bloque
                            const range = document.createRange();
                            range.selectNode(content);
                            window.getSelection().removeAllRanges();
                            window.getSelection().addRange(range);
                            document.execCommand('copy');
                            this.innerText = '✅ Copié !';
                        }
                    });
                <\/script>
            </body>
            </html>
        `);
        win.document.close();
    }
}