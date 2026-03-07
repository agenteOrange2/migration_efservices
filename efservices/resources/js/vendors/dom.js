import dom from "@left4code/tw-starter/dist/js/dom";

// Solo asignar dom si jQuery no está definido
// jQuery tiene prioridad porque raze.js y otros scripts lo necesitan
if (typeof window.$ === 'undefined') {
    window.$ = dom;
}

// Exportar dom para uso interno si es necesario
window.twDom = dom;
