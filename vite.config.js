import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/css/admin.css",
                "resources/css/receipt.css",
                "resources/css/pos.css",
                "resources/css/dashboard.css",
                "resources/css/categories.css",
                "resources/js/app.js",
                "resources/js/admin/pos.js",
                "resources/js/admin/categories.js",
            ],
            refresh: true,
        }),
    ],
});
