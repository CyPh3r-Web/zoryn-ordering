<?php
/**
 * Normalize DB-stored asset paths for pages under users/ (one level below project root).
 * DB values like assets/zoryn/products/foo.jpg or assets/images/products/foo.jpg must become
 * ../assets/... or the browser requests users/assets/... (404).
 * Filename is URL-encoded for characters such as & and spaces.
 */
if (!function_exists('image_path_for_users_folder')) {
    function image_path_for_users_folder($path) {
        if ($path === null || trim((string) $path) === '') {
            return null;
        }
        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');
        if (strpos($path, '../') === 0) {
            $norm = $path;
        } elseif (strpos($path, 'assets/') === 0) {
            $norm = '../' . $path;
        } else {
            $norm = '../' . $path;
        }
        $dir = dirname($norm);
        $base = basename($norm);
        if ($base === '' || $base === '.') {
            return $norm;
        }
        return $dir . '/' . rawurlencode($base);
    }
}
