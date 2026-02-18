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














// 5. STRUCTURE DES COLONNES (STRATÉGIE DE SURVIE GMAIL)
    temp.querySelectorAll('.grid-wrapper').forEach(grid => {
        const cols = grid.querySelectorAll('.col-item');
        if (cols.length === 0) return;

        // Pour Gmail, on oublie le côte-à-côte si le responsive ne suit pas.
        // On transforme chaque colonne en une TABLE de 100% de large.
        // Elles seront l'une sous l'autre par défaut, ce qui est propre partout.
        let finalHTML = '';
        
        cols.forEach(col => {
            finalHTML += `
                <table width="100%" border="0" cellpadding="0" cellspacing="0" style="width:100%; margin-bottom:20px; border-collapse:collapse;">
                    <tr>
                        <td style="padding:10px; text-align:left; font-size: ${defaultPSize}; color: #333333; font-family: Arial, sans-serif; line-height: 1.6;">
                            ${col.innerHTML}
                        </td>
                    </tr>
                </table>`;
        });
        
        grid.innerHTML = finalHTML;
    });
















    // 6. FLOAT DES IMAGES (RESPONSIVE FORCÉ)
    temp.querySelectorAll('.block-float img').forEach((img, i) => {
        img.className = "mobile-img";
        img.style.maxWidth = "200px"; 
        img.style.height = "auto";
        img.style.display = "inline";
        if (imgData[i] === 'left') {
            img.style.float = "left";
            img.style.marginRight = "20px";
        } else if (imgData[i] === 'right') {
            img.style.float = "right";
            img.style.marginLeft = "20px";
        }
        img.style.marginBottom = "10px";
    });

    const styleContent = `
        body { margin: 0; padding: 0; background: #f4f4f4; }
        .container { max-width: 800px; margin: 0 auto; background: #ffffff; padding: 40px; }
        @media only screen and (max-width: 600px) {
            .container { padding: 20px !important; width: 100% !important; box-sizing: border-box !important; }
            .mobile-table, .mobile-table tbody, .mobile-table tr { display: block !important; width: 100% !important; }
            .mobile-col { display: block !important; width: 100% !important; padding: 10px 0 !important; box-sizing: border-box !important; }
            .mobile-img { display: block !important; float: none !important; margin: 0 auto 20px auto !important; max-width: 100% !important; width: 100% !important; height: auto !important; }
        }
    `;

    // 7. PRÉPARATION DU BOUTON
    const buttonHTML = `
        <div id="copy-tool" style="text-align:center; margin-top:40px; padding:20px; border-top:1px solid #eee; font-family:Arial, sans-serif;">
            <button onclick="
                const range = document.createRange();
                range.selectNode(document.getElementById('mail-content'));
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                document.execCommand('copy');
                this.innerText = '✅ Copié !';
                this.style.background = '#34a853';
            " style="background:#4285f4; color:white; border:none; padding:15px 30px; border-radius:5px; cursor:pointer; font-weight:bold; font-size:16px;">
                Copier pour Gmail
            </button>
        </div>`;

    // 8. OUVERTURE UNIQUE DU BLANK
    const win = window.open("", "_blank");
    if (win) {
        win.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>${styleContent}</style>
            </head>
            <body>
                <div class="container" id="mail-content">${temp.innerHTML}</div>
                ${buttonHTML}
            </body>
            </html>
        `);
        win.document.close();
    }
}