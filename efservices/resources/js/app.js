import Pristine from "pristinejs";
import Toastify from 'toastify-js';
import TomSelect from 'tom-select';
import Pikaday from 'pikaday';
import moment from 'moment';
import 'pikaday/css/pikaday.css';

// Alpine.js ya está incluido en Livewire, no necesitamos cargarlo por separado
// Livewire se inicializa automáticamente

// jQuery ya está cargado desde CDN en base.blade.php
// Solo aseguramos que esté disponible globalmente
const $ = window.jQuery || window.$;

// Exponer Pristine, Pikaday y moment globalmente
window.Pristine = Pristine;
window.Pikaday = Pikaday;
window.moment = moment;
window.Toastify = Toastify;

// Validación en consola
if (typeof $ === "undefined") {
  console.error("jQuery no está disponible. Asegúrate de que se cargue desde el CDN.");
} else {
  // Asegurar que $ está disponible globalmente
  window.$ = window.jQuery = $;
  
  import("@left4code/tw-starter/dist/js/svg-loader");
  import("@left4code/tw-starter/dist/js/accordion");
  import("@left4code/tw-starter/dist/js/alert");
  import("@left4code/tw-starter/dist/js/dropdown");
  import("@left4code/tw-starter/dist/js/modal");
  import("@left4code/tw-starter/dist/js/tab");
}

// Importar unified-image-upload para que esté disponible globalmente
import "./unified-image-upload";

// Importar driver-datepicker para el formulario de registro
import "./components/driver-datepicker";

// Otros scripts
try {
  import("./vendors/chartjs");
  import("./vendors/tiny-slider");
  import("./vendors/tippy");
  import("./vendors/litepicker");
  import("./vendors/tom-select");
  import("./vendors/dropzone");
  import("./pages/notification");
  import("./vendors/tabulator");
  import("./vendors/lucide");
  import("./vendors/calendar/calendar");
  import("./vendors/select2");
  import("./vendors/calendar/plugins/interaction.js");
  import("./vendors/calendar/plugins/day-grid.js");
  import("./vendors/calendar/plugins/time-grid.js");
  import("./vendors/calendar/plugins/list.js");
  import("./ckeditor-classic");
} catch (error) {
  console.warn("Error al cargar librerías opcionales:", error);
}
