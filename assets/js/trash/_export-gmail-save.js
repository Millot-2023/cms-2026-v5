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

    // 5. STRUCTURE DES COLONNES (RESPONSIVE FORCÉ)
    temp.querySelectorAll('.grid-wrapper').forEach(grid => {
        const cols = grid.querySelectorAll('.col-item');
        if (cols.length === 0) return;
        const widthPerCol = (100 / cols.length).toFixed(2);

        // Ajout de la classe mobile-table pour le CSS
        let tableHTML = `<table class="mobile-table" width="100%" border="0" cellpadding="0" cellspacing="0" style="width:100%; border-collapse:collapse;"><tr>`;
        cols.forEach(col => {
            tableHTML += `
                <td class="mobile-col" width="${widthPerCol}%" valign="top" style="width:${widthPerCol}%; padding:0 15px; vertical-align:top; text-align:left; line-height: 1.6; font-size: ${defaultPSize}; color: #333333; font-family: Arial, sans-serif;">
                    ${col.innerHTML}
                </td>`;
        });
        tableHTML += `</tr></table>`;
        grid.innerHTML = tableHTML;
        grid.style.marginBottom = "20px";
    });

    // 6. FLOAT DES IMAGES (RESPONSIVE FORCÉ)
    temp.querySelectorAll('.block-float img').forEach((img, i) => {
        img.className = "mobile-img"; // Classe pour le responsive
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
                <div class="container">${temp.innerHTML}</div>
            </body>
            </html>
        `);
        win.document.close();
    }








    


    
}