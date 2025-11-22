<?php
/**
 * CSRF Protection System
 *
 * This script handles the generation and validation of CSRF tokens.
 */

class CSRF {
    /**
     * Generates a new CSRF token and stores it in the session.
     * If a token already exists, it will not be overwritten unless forced.
     *
     * @param bool $force Overwrite existing token if true.
     * @return string The CSRF token.
     */
    public static function generateToken($force = false) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if ($force || !isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validates a given CSRF token against the one stored in the session.
     * Uses hash_equals for timing-attack-safe comparison.
     *
     * @param string|null $token The token from the request (POST body or header).
     * @return bool True if the token is valid, false otherwise.
     */
    public static function validateToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Refresh the CSRF token (force new generation)
     * @return string The new token
     */
    public static function refreshToken() {
        return self::generateToken(true);
    }

    /**
     * Get a hidden input field with the CSRF token
     * @return string HTML input field
     */
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Get a meta tag containing the CSRF token (useful for front-end frameworks)
     * @return string HTML meta tag
     */
    public static function getMetaTag() {
        $token = self::generateToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Retrieve the current token value (generates one if missing)
     * @return string
     */
    public static function getToken() {
        return self::generateToken();
    }
}

// Backward compatibility functions

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generate_csrf_token($force = false) {
    return CSRF::generateToken($force);
}

function validate_csrf_token($token) {
    return CSRF::validateToken($token);
}

/**
 * A wrapper function to protect state-changing requests (POST, PUT, DELETE).
 * It checks for the token in 'X-CSRF-TOKEN' header or a '_csrf' POST field.
 * If validation fails, it terminates the script.
 */
function protect_from_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $token = null;
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        } elseif (isset($_POST['_csrf'])) {
            $token = $_POST['_csrf'];
        } elseif (isset($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        }

        if (!CSRF::validateToken($token)) {
            // Respond with an error and terminate
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'CSRF token validation failed.']);
            exit;
        }
    }
}
?>