<?php

function base_url($uri = ""){
    return BASE_URL.$uri;
}
function getCurrentUrl() {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . $host . $uri;
}
function sanitizeText($input) {
    return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
}
function sanitizeSlug($input) {
    $slug = strtolower($input);
    $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    $slug = preg_replace('/\-+/', '-', $slug);
    return trim($slug, '-');
}