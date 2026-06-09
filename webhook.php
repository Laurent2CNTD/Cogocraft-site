<?php
// =============================================
// COGOCRAFT — GitHub Webhook
// Fichier : /var/www/cogocraft/webhook.php
// =============================================

$_secret = getenv('WEBHOOK_SECRET');
if (empty($_secret)) {
    http_response_code(500);
    error_log('[cogocraft] ERREUR : variable WEBHOOK_SECRET non définie');
    exit('Configuration error');
}
define('SECRET', $_secret);
define('DEPLOY_SCRIPT', '/var/www/cogocraft/deploy.sh');
define('LOG_FILE', '/var/log/cogocraft-deploy.log');

function log_msg($msg) {
    file_put_contents(LOG_FILE, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

// Vérification méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// Lecture payload
$payload = file_get_contents('php://input');
if (empty($payload)) {
    http_response_code(400);
    log_msg('Payload vide');
    exit('Bad Request');
}

// Vérification signature GitHub
$sig_header = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected   = 'sha256=' . hash_hmac('sha256', $payload, SECRET);
if (!hash_equals($expected, $sig_header)) {
    http_response_code(401);
    log_msg('Signature invalide');
    exit('Unauthorized');
}

// Vérification event push sur main
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
$data  = json_decode($payload, true);
$branch = $data['ref'] ?? '';

if ($event !== 'push' || $branch !== 'refs/heads/main') {
    http_response_code(200);
    exit('Ignored: not a push on main');
}

// Lancement du déploiement en arrière-plan
log_msg("Push reçu sur main — lancement deploy");
exec('sudo bash ' . DEPLOY_SCRIPT . ' >> ' . LOG_FILE . ' 2>&1 &');

http_response_code(200);
echo 'Deploy triggered';
log_msg("Réponse 200 envoyée");
?>
