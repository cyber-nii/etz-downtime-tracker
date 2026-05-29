<?php
require_once __DIR__ . '/../../config/config.php';

if (defined('APP_ENV') && APP_ENV === 'production') {
    http_response_code(403);
    exit('This tool is not available in production.');
}

$payload = null;
$rawJson = null;
$curlCmd = null;
$apiResponse = null;
$apiResponsePretty = null;
$error = null;
$fired = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Both username and password are required.';
    } else {
        $rawJson = json_encode([
            EXTERNAL_AUTH_REQ_USERNAME => $username,
            EXTERNAL_AUTH_REQ_PASSWORD => $password,
        ], JSON_UNESCAPED_UNICODE);

        $pubKey = openssl_pkey_get_public(EXTERNAL_AUTH_PUBLIC_KEY);
        if ($pubKey === false) {
            $error = 'Failed to load RSA public key.';
        } elseif (!openssl_public_encrypt($rawJson, $encrypted, $pubKey, OPENSSL_PKCS1_PADDING)) {
            $error = 'RSA encryption failed: ' . openssl_error_string();
        } else {
            $b64 = base64_encode($encrypted);
            $payload = json_encode(['payload' => $b64]);

            $escapedPayload = str_replace("'", "'\\''", $payload);
            $curlCmd = "curl -X POST \\\n"
                . "  '" . EXTERNAL_AUTH_API_URL . "' \\\n"
                . "  -H 'Content-Type: application/json' \\\n"
                . "  -H 'Accept: application/json' \\\n"
                . "  -d '" . $escapedPayload . "'";

            if (isset($_POST['fire_request'])) {
                $fired = true;
                $ch = curl_init(EXTERNAL_AUTH_API_URL);
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $payload,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'Accept: application/json, text/plain, */*',
                    ],
                    CURLOPT_TIMEOUT        => EXTERNAL_AUTH_API_TIMEOUT,
                    CURLOPT_SSL_VERIFYPEER => true,
                ]);
                $apiResponse = curl_exec($ch);
                $curlError   = curl_error($ch);
                curl_close($ch);

                if ($apiResponse === false) {
                    $error = 'cURL request failed: ' . $curlError;
                } else {
                    $decoded = json_decode($apiResponse, true);
                    $apiResponsePretty = $decoded
                        ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                        : $apiResponse;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payload Generator — Dev Tool</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        pre { white-space: pre-wrap; word-break: break-all; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen font-mono">
    <div class="max-w-3xl mx-auto px-4 py-10">

        <div class="mb-8">
            <div class="inline-flex items-center gap-2 text-xs text-amber-400 bg-amber-400/10 border border-amber-400/20 rounded px-3 py-1 mb-4">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                DEV TOOL — not available in production
            </div>
            <h1 class="text-2xl font-bold text-white">API Payload Generator</h1>
            <p class="text-gray-400 text-sm mt-1">Generates the RSA-encrypted payload for the XPortal Authenticator API.</p>
        </div>

        <form method="POST" class="bg-gray-900 border border-gray-800 rounded-xl p-6 space-y-5">
            <div>
                <label class="block text-xs text-gray-400 mb-1.5" for="username">Username / Email</label>
                <input
                    type="text" id="username" name="username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500"
                    placeholder="your.username"
                    autocomplete="off"
                >
            </div>

            <div>
                <label class="block text-xs text-gray-400 mb-1.5" for="password">Password</label>
                <input
                    type="password" id="password" name="password"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-blue-500"
                    placeholder="••••••••"
                    autocomplete="off"
                >
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-1">
                <button type="submit" name="generate"
                    class="flex-1 bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                    Generate Payload
                </button>
                <button type="submit" name="fire_request" value="1"
                    class="flex-1 bg-emerald-700 hover:bg-emerald-600 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                    Generate &amp; Fire Request
                </button>
            </div>
        </form>

        <?php if ($error): ?>
        <div class="mt-6 bg-red-900/40 border border-red-700/50 rounded-xl p-4 text-red-300 text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($payload): ?>
        <div class="mt-8 space-y-6">

            <!-- Raw JSON -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-widest">1. Pre-encryption JSON</span>
                    <button onclick="copyText('raw-json', this)" class="text-xs text-blue-400 hover:text-blue-300 transition-colors">Copy</button>
                </div>
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                    <pre id="raw-json" class="text-green-400 text-sm"><?= htmlspecialchars($rawJson) ?></pre>
                </div>
            </div>

            <!-- Final Payload -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-widest">2. Final Payload (RSA + Base64)</span>
                    <button onclick="copyText('final-payload', this)" class="text-xs text-blue-400 hover:text-blue-300 transition-colors">Copy</button>
                </div>
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                    <pre id="final-payload" class="text-yellow-300 text-sm"><?= htmlspecialchars($payload) ?></pre>
                </div>
            </div>

            <!-- cURL command -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-widest">3. cURL Command</span>
                    <button onclick="copyText('curl-cmd', this)" class="text-xs text-blue-400 hover:text-blue-300 transition-colors">Copy</button>
                </div>
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                    <pre id="curl-cmd" class="text-cyan-300 text-sm"><?= htmlspecialchars($curlCmd) ?></pre>
                </div>
            </div>

            <!-- API Response -->
            <?php if ($fired): ?>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-widest">4. API Response</span>
                    <button onclick="copyText('api-response', this)" class="text-xs text-blue-400 hover:text-blue-300 transition-colors">Copy</button>
                </div>
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
                    <pre id="api-response" class="text-cyan-300 text-sm"><?= htmlspecialchars($apiResponsePretty ?? $apiResponse ?? '') ?></pre>
                </div>
            </div>
            <?php endif; ?>

        </div>
        <?php endif; ?>

    </div>

    <script>
        function copyText(id, btn) {
            const text = document.getElementById(id).textContent;
            navigator.clipboard.writeText(text).then(() => {
                const orig = btn.textContent;
                btn.textContent = 'Copied!';
                btn.classList.replace('text-blue-400', 'text-emerald-400');
                setTimeout(() => {
                    btn.textContent = orig;
                    btn.classList.replace('text-emerald-400', 'text-blue-400');
                }, 1500);
            });
        }
    </script>
</body>
</html>
