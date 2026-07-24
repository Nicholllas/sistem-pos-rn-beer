import "bootstrap/dist/js/bootstrap.bundle.min.js";
import "admin-lte/dist/js/adminlte.min.js";
import "./admin/crud.js";
import "./admin/purchases.js";
import "./admin/inventory.js";

import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.start();
