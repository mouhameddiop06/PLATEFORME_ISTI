<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} 
/**
 * Fonctions utilitaires pour la plateforme ISTI
 */

/**
 * Nettoie et sécurise les données entrées par l'utilisateur
 * @param string $data Données à nettoyer
 * @return string Données nettoyées
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Vérifie si l'email est valide
 * @param string $email Email à vérifier
 * @return bool True si l'email est valide, false sinon
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Vérifie si un numéro de téléphone est valide
 * @param string $phone Numéro de téléphone à vérifier
 * @return bool True si le numéro est valide, false sinon
 */
function isValidPhone($phone) {
    // Format simple pour numéro sénégalais: commence par 7 et suivi de 8 chiffres
    return preg_match('/^7[0-9]{8}$/', $phone);
}

/**
 * Génère un message d'alerte formaté
 * @param string $message Le message à afficher
 * @param string $type Le type d'alerte (success, error, warning, info)
 * @return string HTML formaté pour l'alerte
 */
function alert($message, $type = 'error') {
    $colors = [
        'success' => 'bg-green-100 border-green-500 text-green-700',
        'error' => 'bg-red-100 border-red-500 text-red-700',
        'warning' => 'bg-yellow-100 border-yellow-500 text-yellow-700',
        'info' => 'bg-blue-100 border-blue-500 text-blue-700'
    ];
    
    $colorClass = isset($colors[$type]) ? $colors[$type] : $colors['info'];
    
    return "<div class=\"{$colorClass} px-4 py-3 rounded relative mb-4 border\" role=\"alert\">
                <span class=\"block sm:inline\">{$message}</span>
            </div>";
}

/**
 * Redirection avec un message
 * @param string $url URL de destination
 * @param string $message Message à afficher
 * @param string $type Type d'alerte
 */
function redirectWithMessage($url, $message, $type = 'info') {
    $_SESSION['alert_message'] = $message;
    $_SESSION['alert_type'] = $type;
    header("Location: {$url}");
    exit;
}

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool True si l'utilisateur est connecté, false sinon
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur possède un rôle spécifique
 * @param string $role Le rôle à vérifier
 * @return bool True si l'utilisateur a le rôle, false sinon
 */
function hasRole($role) {
    if (!isLoggedIn() || !isset($_SESSION['user_roles'])) {
        return false;
    }
    
    return in_array($role, $_SESSION['user_roles']);
}