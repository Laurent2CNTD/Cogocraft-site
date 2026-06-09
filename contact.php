<?php
// =============================================
// COGOCRAFT — Formulaire de contact
// =============================================
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method Not Allowed']));
}

// Honeypot anti-spam : ce champ doit rester vide
if (!empty($_POST['website'])) {
    exit(json_encode(['success' => true]));
}

// Rate limiting : 3 envois max par fenêtre de 10 minutes
$now = time();
if (!isset($_SESSION['cc_times'])) {
    $_SESSION['cc_times'] = [];
}
$_SESSION['cc_times'] = array_values(
    array_filter($_SESSION['cc_times'], fn($t) => $now - $t < 600)
);
if (count($_SESSION['cc_times']) >= 3) {
    http_response_code(429);
    exit(json_encode([
        'success' => false,
        'message' => 'Trop de demandes. Veuillez réessayer dans quelques minutes.'
    ]));
}

// Récupération et validation des champs requis
$nom     = trim($_POST['nom']     ?? '');
$email   = trim($_POST['email']   ?? '');
$tel     = trim($_POST['tel']     ?? '');
$besoin  = trim($_POST['besoin']  ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($nom) || empty($email)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Nom et email requis.']));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Adresse email invalide.']));
}

// Sanitisation
$nom     = htmlspecialchars($nom,     ENT_QUOTES | ENT_HTML5, 'UTF-8');
$email   = htmlspecialchars($email,   ENT_QUOTES | ENT_HTML5, 'UTF-8');
$tel     = htmlspecialchars($tel,     ENT_QUOTES | ENT_HTML5, 'UTF-8');
$besoin  = htmlspecialchars($besoin,  ENT_QUOTES | ENT_HTML5, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');

// Construction de l'email
$to      = 'contact@cogocraft.com';
$subject = '=?UTF-8?B?' . base64_encode("Demande de devis — $nom") . '?=';

$body  = "Nom / Société : $nom\n";
$body .= "Email : $email\n";
if ($tel)    $body .= "Téléphone : $tel\n";
if ($besoin) $body .= "Besoin : $besoin\n";
$body .= "\nMessage :\n$message\n";
$body .= "\n---\nEnvoyé depuis cogocraft.com le " . date('d/m/Y à H:i');

$headers  = "From: noreply@cogocraft.com\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: CogoCraft/1.0\r\n";

$sent = mail($to, $subject, $body, $headers);

if ($sent) {
    $_SESSION['cc_times'][] = $now;
    exit(json_encode([
        'success' => true,
        'message' => 'Message envoyé. Je vous réponds sous 24h.'
    ]));
} else {
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'message' => "Erreur lors de l'envoi. Appelez directement au 06 15 61 49 38."
    ]));
}
