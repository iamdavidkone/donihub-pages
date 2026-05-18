<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function field(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

function clean_header(string $value): string
{
    return trim(str_replace(["\r", "\n"], ' ', $value));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'message' => 'Méthode non autorisée.']);
}

if (field('website') !== '') {
    respond(200, ['ok' => true]);
}

$fullName = field('fullName');
$email = field('email');
$phone = field('phone');
$profile = field('profile');
$subject = field('subject');
$message = field('message');

$allowedProfiles = ['Étudiant', 'Chercheur', 'Professionnel', 'ONG', 'Institution', 'Autre'];

if ($fullName === '' || $email === '' || $phone === '' || $profile === '' || $subject === '' || $message === '') {
    respond(422, ['ok' => false, 'message' => 'Veuillez compléter tous les champs requis.']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ['ok' => false, 'message' => 'Veuillez saisir une adresse e-mail valide.']);
}

if (!in_array($profile, $allowedProfiles, true)) {
    respond(422, ['ok' => false, 'message' => 'Veuillez sélectionner un profil valide.']);
}

if (strlen($fullName) > 120 || strlen($email) > 160 || strlen($phone) > 60 || strlen($subject) > 160 || strlen($message) > 3000) {
    respond(422, ['ok' => false, 'message' => 'Certains champs sont trop longs.']);
}

$to = 'info@donihub.com';
$safeName = clean_header($fullName);
$safeSubject = clean_header($subject);
$mailSubject = 'DoniHub - ' . $safeSubject;
$encodedSubject = '=?UTF-8?B?' . base64_encode($mailSubject) . '?=';

$body = implode("\n", [
    'Nouvelle inscription à la liste d’attente DoniHub',
    '',
    'Nom complet : ' . $fullName,
    'Email : ' . $email,
    'Téléphone : ' . $phone,
    'Profil : ' . $profile,
    'Objet : ' . $subject,
    '',
    'Message :',
    $message
]);

$headers = [
    'From: DoniHub <no-reply@donihub.com>',
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8'
];

$sent = @mail($to, $encodedSubject, wordwrap($body, 78), implode("\r\n", $headers));

if (!$sent) {
    respond(500, ['ok' => false, 'message' => 'Le message n’a pas pu être envoyé pour le moment.']);
}

respond(200, ['ok' => true]);
