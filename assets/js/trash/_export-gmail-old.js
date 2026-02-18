function exportForGmail() {
    const core = document.getElementById('editor-core');
    if (!core) return;

    let temp = core.cloneNode(true);

    // 1. Nettoyage interface
    temp.querySelectorAll('.delete-block').forEach(el => el.remove());
    temp.querySelectorAll('[contenteditable]').forEach(el => el.removeAttribute('contenteditable'));

    // 2. RESET TOTAL
    temp.querySelectorAll('*').forEach(el => el.removeAttribute('style'));

    // 3. RÉINJECTION DESIGN SYSTEM (Colonnes standards)
    for (let tag in designSystem) {
        temp.querySelectorAll(tag).forEach(el => {
            if (!el.classList.contains('has-lettrine')) {
                el.style.fontSize = designSystem[tag].fontSize;
                el.style.fontFamily = "Arial, sans-serif";
                el.style.lineHeight = "1.6";
                el.style.marginBottom = "20px";
                el.style.color = "#333333";
                el.style.display = "block";
            }
        });
    }

    // 4. STRUCTURE DES COLONNES
    temp.querySelectorAll('.grid-wrapper').forEach(grid => {
        grid.style.width = "100%";
        grid.style.display = "block";
        grid.style.fontSize = "0"; 
        const cols = grid.querySelectorAll('.col-item');
        cols.forEach(col => {
            col.style.display = "inline-block";
            col.style.verticalAlign = "top";
            col.style.width = (100 / cols.length) + "%";
            col.style.boxSizing = "border-box";
            col.style.padding = "0 10px";
            
            // Sécurité : On force le style Arial/Taille sur les colonnes standards ici aussi
            if (!col.classList.contains('has-lettrine')) {
                col.style.fontFamily = "Arial, sans-serif";
                col.style.fontSize = designSystem['p'] ? designSystem['p'].fontSize : "16px";
                col.style.lineHeight = "1.6";
                col.style.color = "#333333";
            }
        });
    });

    // 5. LA LETTRINE
    temp.querySelectorAll('.has-lettrine').forEach(el => {
        const pureText = el.textContent.trim();
        const first = pureText.charAt(0);
        const rest = pureText.slice(1);
        const pSize = designSystem['p'] ? designSystem['p'].fontSize : "18px";
        const lhPx = Math.round(parseInt(pSize) * 1.6) + "px";

        el.style.cssText = `display:inline-block; vertical-align:top; width:33.3333%; box-sizing:border-box; padding:0 10px; font-family: Arial, sans-serif;`;

        el.innerHTML = `
            <div style="line-height: ${lhPx} !important; font-family: Arial, sans-serif;">
                <span style="float: left; font-size: 72px; line-height: 60px; margin-top: 5px; margin-right: 12px; font-weight: bold; color: #000000; font-family: Georgia, serif; display: block;">
                    ${first}
                </span>
                <span style="font-size: ${pSize}; line-height: ${lhPx} !important; color: #333333; font-family: Arial, sans-serif; display: inline;">
                    ${rest}
                </span>
                <div style="clear: both; line-height: 0;"></div>
            </div>
        `.trim();
    });

    // 6. RENDU FINAL
    const style = `
        <style>
            body { margin:0; padding:40px; background:#f4f4f4; }
            .container { max-width: 800px; margin: 0 auto; background: #ffffff; padding: 40px; border: 1px solid #ddd; }
            img { max-width: 100%; height: auto; display: block; }
        </style>
    `;

    const win = window.open("", "_blank");
    win.document.write(`<html><head>${style}</head><body><div class="container">${temp.innerHTML}</div></body></html>`);
    win.document.close();
}