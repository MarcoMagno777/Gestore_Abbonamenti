<?php
// Simple CLI tester for API routes.
// Usage: php scripts/test_routes.php [BASE_URL]
// Example: php scripts/test_routes.php http://localhost:8080

// replace previous base detection with a robust one
$base = $argv[1] ?? getenv('BASE_URL') ?? '';
if (empty($base)) {
  $base = 'http://localhost:8080';
}
// if user passed host without scheme, add http
if (parse_url($base, PHP_URL_SCHEME) === null) {
  $base = 'http://' . $base;
}
$base = rtrim($base, '/');

function request(string $method, string $url, $data = null, array $headers = []): array {
  $ch = curl_init();
  $full = rtrim($GLOBALS['base'], '/') . '/' . ltrim($url, '/');

  // validate that the full URL contains a host
  $parts = parse_url($full);
  if (empty($parts['host'])) {
    return ['code' => 0, 'body' => '', 'error' => 'No host in URL: ' . $full, 'url' => $full];
  }

  curl_setopt($ch, CURLOPT_URL, $full);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
  // allow reading response body even on 4xx/5xx
  curl_setopt($ch, CURLOPT_FAILONERROR, false);

  $defaultHeaders = ['Accept: application/json'];
  if ($data !== null) {
    $json = json_encode($data);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    $defaultHeaders[] = 'Content-Type: application/json';
  }
  curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
  $body = curl_exec($ch);
  $err = curl_error($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return ['code' => $httpCode, 'body' => $body, 'error' => $err, 'url' => $full];
}

function prettyPrintResponse(array $res) {
  echo "HTTP {$res['code']}\n";
  if ($res['error']) {
    echo "cURL error: {$res['error']}\n";
  }
  $body = $res['body'] ?? '';
  $decoded = json_decode($body, true);
  if (json_last_error() === JSON_ERROR_NONE) {
    echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
  } else {
    echo $body . "\n";
  }
  echo str_repeat('-', 60) . "\n";
}

// SAMPLE IDs and payloads (adatta se il DB non contiene questi id)
$idA = 1;
$idS = 1;

$tests = [
  ['GET', "/account/{$idA}/subscriptions", null],
  ['GET', "/account/{$idA}/subscriptions/{$idS}", null],
  ['GET', "/account/{$idA}", null],
  ['POST', '/register', ['username' => 'testuser', 'email' => 'test@example.com', 'password' => 'secret123']],
  ['POST', '/login', ['username' => 'testuser', 'password' => 'secret123']],
  ['GET', '/admin/accounts', null],
  ['POST', "/account/{$idA}/subscriptions", [
    'nome' => 'Prova',
    'descrizione' => 'Abbonamento di prova',
    'data_sottoscrizione' => date('Y-m-d'),
    'data_scadenza' => date('Y-m-d', strtotime('+1 year')),
    'costo' => '9.99'
  ]],
  ['PUT', "/account/{$idA}/subscriptions/{$idS}", [
    'nome' => 'Prova Aggiornata',
    'descrizione' => 'Aggiornamento',
    'data_sottoscrizione' => date('Y-m-d'),
    'data_scadenza' => date('Y-m-d', strtotime('+6 months')),
    'costo' => '19.99'
  ]],
  ['DELETE', "/account/{$idA}/subscriptions/{$idS}", null],
  ['DELETE', "/admin/remove/{$idA}", null],
];

echo "Base URL: {$base}\n";
echo str_repeat('=', 60) . "\n";

foreach ($tests as $t) {
  [$method, $path, $payload] = $t;
  echo strtoupper($method) . " {$path}\n";
  $res = request($method, $path, $payload);
  prettyPrintResponse($res);
}

echo "Done.\n";
