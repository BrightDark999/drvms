<?php

/**
 * Asset Helper – builds URLs for CSS/JS/images under public/assets.
 * Keeps views clean and paths consistent for WAMP deployment.
 */

if (! function_exists('asset_url')) {
    /**
     * @param string $path Path relative to public/ (e.g. "assets/css/app.css")
     */
    function asset_url(string $path = ''): string
    {
        return base_url(ltrim($path, '/'));
    }
}

if (! function_exists('asset_versioned')) {
    /**
     * Asset URL with file modification time — busts browser cache after deploys.
     *
     * @param string $path Path relative to public/ (e.g. "assets/js/public-home.js")
     */
    function asset_versioned(string $path): string
    {
        $path = ltrim($path, '/');
        $url  = asset_url($path);
        $full = rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        if (is_file($full)) {
            $url .= '?v=' . (string) filemtime($full);
        }

        return $url;
    }
}

if (! function_exists('adminlte_asset')) {
    /**
     * Shortcut for AdminLTE files under public/assets/adminlte/
     */
    function adminlte_asset(string $path = ''): string
    {
        return asset_url('assets/adminlte/' . ltrim($path, '/'));
    }
}
