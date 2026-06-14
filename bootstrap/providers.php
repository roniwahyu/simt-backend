<?php

return [
    App\Providers\AppServiceProvider::class,
    // ⚙️ Provider modul TIDAK didaftarkan manual di sini.
    // nwidart/laravel-modules mendaftarkan provider modul secara otomatis
    // berdasarkan status di modules_statuses.json (activator: file).
    // Ini menjadikan modul benar-benar plug & play: nonaktifkan modul →
    // route/view/provider-nya ikut tidak ter-register. (lihat config/modules.php)
];
