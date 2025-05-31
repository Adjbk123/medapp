<?php
/**
 * Fonctions de sécurité pour l'application MedApp
 * Inclut la gestion des tokens CSRF et autres fonctionnalités de sécurité
 */

/**
 * Génère un token CSRF et le stocke en session
 * 
 * @return string Le token CSRF généré
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie si un token CSRF est valide
 * 
 * @param string $token Le token CSRF à vérifier
 * @return bool True si le token est valide, false sinon
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Régénère un nouveau token CSRF
 * Utile après une soumission de formulaire réussie pour éviter les problèmes de réutilisation
 * 
 * @return string Le nouveau token CSRF
 */
function regenerateCSRFToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

/**
 * Affiche un champ caché avec le token CSRF pour un formulaire
 * 
 * @return string Le HTML du champ caché avec le token CSRF
 */
function csrfTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Nettoie les données d'entrée pour prévenir les attaques XSS
 * 
 * @param string $data Les données à nettoyer
 * @return string Les données nettoyées
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Vérifie si la requête est une requête AJAX
 * 
 * @return bool True si c'est une requête AJAX, false sinon
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Note: Les fonctions requireLogin() et requireRole() sont déjà définies dans session.php
// Nous ne les redéclarons pas ici pour éviter les erreurs
