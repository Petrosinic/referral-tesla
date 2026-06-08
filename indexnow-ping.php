<?php
/**
 * IndexNow – Notifica automatica Bing (e altri motori compatibili)
 * Sito: https://referral-tesla.it/
 * Chiave: 11a61d2f6c484360a4c8959fe1f6a829
 *
 * UTILIZZO:
 *   - Manuale: apri https://referral-tesla.it/indexnow-ping.php nel browser (protetto da token)
 *   - Cron: aggiungi al cron del server → es. ogni volta che aggiorni il contenuto
 *   - Automatico: includi send_indexnow() nel tuo CMS dopo ogni salvataggio
 *
 * PROTEZIONE: passa ?token=CAMBIA_QUESTA_STRINGA nell'URL per evitare abusi
 */

define('INDEXNOW_KEY',  '11a61d2f6c484360a4c8959fe1f6a829');
define('SITE_HOST',     'referral-tesla.it');
define('SECRET_TOKEN',  'CAMBIA_QUESTA_STRINGA'); // <-- modifica prima di caricare

// --- Elenco URL da notificare ---
$urls = [
    'https://referral-tesla.it/',
    // Aggiungi qui altri URL se hai più pagine
    // 'https://referral-tesla.it/altra-pagina/',
];

// --- Protezione accesso diretto ---
if (php_sapi_name() !== 'cli') {
    $token = $_GET['token'] ?? '';
    if ($token !== SECRET_TOKEN) {
        http_response_code(403);
        die('Accesso non autorizzato. Passa ?token=... nell\'URL.');
    }
}

// --- Funzione di notifica ---
function send_indexnow(array $urls): array {
    $endpoint = 'https://api.indexnow.org/indexnow';

    $payload = json_encode([
        'host'    => SITE_HOST,
        'key'     => INDEXNOW_KEY,
        'keyLocation' => 'https://' . SITE_HOST . '/' . INDEXNOW_KEY . '.txt',
        'urlList' => $urls,
    ]);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    return [
        'http_code' => $httpCode,
        'response'  => $response,
        'error'     => $error,
    ];
}

// --- Esecuzione ---
$result = send_indexnow($urls);

if (php_sapi_name() === 'cli') {
    // Output CLI
    echo "HTTP {$result['http_code']}\n";
    echo $result['error'] ? "Errore: {$result['error']}\n" : "Notifica inviata.\n";
} else {
    // Output browser
    header('Content-Type: text/plain; charset=utf-8');
    if ($result['http_code'] === 200 || $result['http_code'] === 202) {
        echo "✅ IndexNow notificato con successo (HTTP {$result['http_code']})\n";
        echo "URL notificati:\n" . implode("\n", $urls);
    } else {
        echo "❌ Errore HTTP {$result['http_code']}\n";
        echo $result['error'] ?: $result['response'];
    }
}
