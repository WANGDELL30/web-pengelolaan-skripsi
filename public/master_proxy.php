<?php
/**
 * Local reverse proxy for the WiFi HaLow master panel.
 *
 * LuCI returns X-Frame-Options: SAMEORIGIN, so a direct iframe from localhost is
 * blocked by the browser. This proxy keeps the iframe on the same origin as the
 * dashboard while forwarding requests only to the configured master IP.
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Helpers/master_device.php';

if (!isLoggedIn()) {
    http_response_code(403);
    echo 'Login aplikasi diperlukan.';
    exit;
}

if (!canManageProject()) {
    http_response_code(403);
    echo 'Akses panel konfigurasi master hanya untuk admin.';
    exit;
}

// The proxy only needs the app session for the login check above. Releasing the
// lock lets LuCI load its JS, CSS and RPC calls in parallel instead of queuing.
$appSessionName = session_name();
session_write_close();

$_masterCfg = [];
$_masterLocalFile = __DIR__ . '/../config/master.local.php';
if (is_file($_masterLocalFile)) {
    $_masterCfg = require $_masterLocalFile;
}

$masterConfig = masterDeviceGetConfig($pdo);
$masterHost = $masterConfig['connect_host'];
$masterBaseUrl = $masterConfig['connect_base_url'];
$luciUser = getenv('LUCI_USER') ?: ($_masterCfg['luci_user'] ?? 'root');
$luciPass = getenv('LUCI_PASS') ?: ($_masterCfg['luci_pass'] ?? 'psn2026');
unset($_masterCfg, $_masterLocalFile);

$proxyBasePath = $_SERVER['SCRIPT_NAME'] ?? ('/' . basename(__FILE__));
$sysauthTokenFile = masterDeviceTokenFile('sysauth', $masterHost);
$ubusTokenFile = masterDeviceTokenFile('ubus', $masterHost);


function masterProxyHeaders($headers = []) {
    return array_merge($headers, masterDeviceCloudflareAccessHeaders());
}

/**
 * Get or create a valid LuCI sysauth token.
 * Caches the token in a temp file so it persists across requests.
 */
function masterProxyGetSysauth($masterBaseUrl, $luciUser, $luciPass, $tokenFile) {
    // Check cached token
    if (file_exists($tokenFile)) {
        $cached = json_decode(file_get_contents($tokenFile), true);
        if ($cached && !empty($cached['token']) && ($cached['expires'] ?? 0) > time()) {
            return $cached['token'];
        }
    }

    // Login to LuCI via POST
    $loginUrl = $masterBaseUrl . '/cgi-bin/luci/';
    $postData = http_build_query([
        'luci_username' => $luciUser,
        'luci_password' => $luciPass,
    ]);

    $respHeaders = [];
    $ch = curl_init($loginUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => masterProxyHeaders([
            'Host: ' . parse_url($masterBaseUrl, PHP_URL_HOST),
            'Content-Type: application/x-www-form-urlencoded',
        ]),
        CURLOPT_HEADERFUNCTION => function ($curl, $headerLine) use (&$respHeaders) {
            $line = trim($headerLine);
            if ($line !== '' && strpos($line, ':') !== false) {
                $parts = explode(':', $line, 2);
                $respHeaders[] = [trim($parts[0]), trim($parts[1])];
            }
            return strlen($headerLine);
        },
    ]);
    curl_exec($ch);
    curl_close($ch);

    // Extract sysauth cookie from response
    $sysauth = '';
    foreach ($respHeaders as [$name, $value]) {
        if (strtolower($name) === 'set-cookie' && preg_match('/sysauth[^=]*=([^;]+)/i', $value, $m)) {
            $sysauth = $m[0]; // e.g. "sysauth_http=abc123"
            break;
        }
    }

    if ($sysauth !== '') {
        file_put_contents($tokenFile, json_encode([
            'token' => $sysauth,
            'expires' => time() + 3600, // cache for 1 hour
        ]));
    }

    return $sysauth;
}

/**
 * Invalidate cached LuCI token so next request re-authenticates.
 */
function masterProxyInvalidateToken($sysauthTokenFile, $ubusTokenFile) {
    @unlink($sysauthTokenFile);
    @unlink($ubusTokenFile);
}

/**
 * Get a ubus RPC session token via ubus login.
 */
function masterProxyGetUbusToken($masterBaseUrl, $luciUser, $luciPass, $tokenFile) {
    if (file_exists($tokenFile)) {
        $cached = json_decode(file_get_contents($tokenFile), true);
        if ($cached && !empty($cached['token']) && ($cached['expires'] ?? 0) > time()) {
            return $cached['token'];
        }
    }
    $ch = curl_init($masterBaseUrl . '/ubus');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => masterProxyHeaders(['Content-Type: application/json']),
        CURLOPT_POSTFIELDS => json_encode([
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'call',
            'params' => ['00000000000000000000000000000000', 'session', 'login',
                         ['username' => $luciUser, 'password' => $luciPass]]
        ]),
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($result ?: '', true);
    $token = $data['result'][1]['ubus_rpc_session'] ?? null;
    if ($token) {
        file_put_contents($tokenFile, json_encode(['token' => $token, 'expires' => time() + 250]));
    }
    return $token ?: '';
}

if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (strpos($name, 'HTTP_') !== 0) {
                continue;
            }
            $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$headerName] = $value;
        }
        return $headers;
    }
}

function masterProxyPath($value) {
    $path = (string) $value;

    if ($path === '') {
        return '/cgi-bin/luci/admin/status/overview';
    }

    if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
        return '/cgi-bin/luci/admin/status/overview';
    }

    if ($path[0] !== '/') {
        $path = '/' . $path;
    }

    return $path;
}

function masterProxyNormalizePath($path) {
    $parts = [];
    foreach (explode('/', $path) as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if ($part === '..') {
            array_pop($parts);
            continue;
        }
        $parts[] = $part;
    }

    return '/' . implode('/', $parts);
}

function masterProxyProxyUrl($path, $proxyBasePath) {
    $path = masterProxyPath($path);
    return $proxyBasePath . '?path=' . rawurlencode($path);
}

function masterProxyResolveUrl($url, $currentPath, $masterHost, $proxyBasePath) {
    $url = trim((string) $url);

    if ($url === '' || $url[0] === '#' || preg_match('#^(data|mailto|tel|javascript):#i', $url)) {
        return $url;
    }

    if ($url[0] === '?') {
        $currentOnly = explode('?', $currentPath, 2)[0];
        return masterProxyProxyUrl($currentOnly . $url, $proxyBasePath);
    }

    if (preg_match('#^https?://#i', $url)) {
        $parts = parse_url($url);
        if (!$parts || (($parts['host'] ?? '') !== $masterHost)) {
            return $url;
        }

        $path = $parts['path'] ?? '/';
        if (!empty($parts['query'])) {
            $path .= '?' . $parts['query'];
        }

        return masterProxyProxyUrl($path, $proxyBasePath);
    }

    if (strpos($url, '//') === 0) {
        return masterProxyResolveUrl('http:' . $url, $currentPath, $masterHost, $proxyBasePath);
    }

    if ($url[0] === '/') {
        return masterProxyProxyUrl($url, $proxyBasePath);
    }

    $currentOnly = explode('?', $currentPath, 2)[0];
    $baseDir = preg_replace('#/[^/]*$#', '/', $currentOnly);
    $resolved = masterProxyNormalizePath($baseDir . $url);

    return masterProxyProxyUrl($resolved, $proxyBasePath);
}

function masterProxyRewriteBody(
    $body,
    $contentType,
    $currentPath,
    $masterHost,
    $masterBaseUrl,
    $proxyBasePath,
    $ubusToken = ''
) {
    if (!preg_match('#(text/html|text/css|javascript|json|xml)#i', $contentType)) {
        return $body;
    }

    // Never rewrite ubus JSON-RPC responses
    if (strpos($currentPath, '/ubus') === 0) {
        return $body;
    }

    $isHtml = preg_match('#text/html#i', $contentType);
    $isCss = preg_match('#text/css#i', $contentType);

    $rewrite = function ($url) use ($currentPath, $masterHost, $proxyBasePath) {
        return masterProxyResolveUrl($url, $currentPath, $masterHost, $proxyBasePath);
    };

    if ($isHtml) {
        $body = preg_replace_callback(
            '/\b(href|src|action)=([\'"])(.*?)\2/i',
            function ($matches) use ($rewrite) {
                return $matches[1] . '=' . $matches[2] . htmlspecialchars($rewrite($matches[3]), ENT_QUOTES, 'UTF-8') . $matches[2];
            },
            $body
        );
    }

    if ($isHtml || $isCss) {
        $body = preg_replace_callback(
            '/\burl\(([\'"]?)(.*?)\1\)/i',
            function ($matches) use ($rewrite) {
                return 'url(' . $matches[1] . $rewrite($matches[2]) . $matches[1] . ')';
            },
            $body
        );
    }

    $body = str_replace(
        ['"/cgi-bin/luci/', "'/cgi-bin/luci/", '"/luci-static/', "'/luci-static/", '"/ubus/', "'/ubus/"],
        [
            '"' . masterProxyProxyUrl('/cgi-bin/luci/', $proxyBasePath),
            "'" . masterProxyProxyUrl('/cgi-bin/luci/', $proxyBasePath),
            '"' . masterProxyProxyUrl('/luci-static/', $proxyBasePath),
            "'" . masterProxyProxyUrl('/luci-static/', $proxyBasePath),
            '"' . masterProxyProxyUrl('/ubus/', $proxyBasePath),
            "'" . masterProxyProxyUrl('/ubus/', $proxyBasePath),
        ],
        $body
    );

    $jsonPathMap = [
        '\\/cgi-bin\\/luci' => masterProxyProxyUrl('/cgi-bin/luci', $proxyBasePath),
        '\\/luci-static\\/resources' => masterProxyProxyUrl('/luci-static/resources', $proxyBasePath),
        '\\/luci-static\\/openmanetargon' => masterProxyProxyUrl('/luci-static/openmanetargon', $proxyBasePath),
        '\\/luci-static\\/' => masterProxyProxyUrl('/luci-static/', $proxyBasePath),
        '\\/ubus\\/' => masterProxyProxyUrl('/ubus/', $proxyBasePath),
    ];

    foreach ($jsonPathMap as $source => $target) {
        $body = str_replace('"' . $source . '"', '"' . $target . '"', $body);
        $body = str_replace("'" . $source . "'", "'" . $target . "'", $body);
    }

    $body = preg_replace_callback(
        '/([\'"])(\/(?:cgi-bin\/luci|luci-static|ubus)\/?[^\'"]*)\1/',
        function ($matches) use ($rewrite) {
            return $matches[1] . $rewrite($matches[2]) . $matches[1];
        },
        $body
    );

    if ($isHtml && stripos($body, 'new LuCI({') !== false) {
        $proxyBaseJson = json_encode($proxyBasePath);
        $masterBaseJson = json_encode(rtrim($masterBaseUrl, '/'));
        $proxyResourceJson = json_encode(masterProxyProxyUrl('/luci-static/resources', $proxyBasePath));
        $ubusTokenJson = json_encode($ubusToken ?: '');
        $proxyUbusPath = json_encode(masterProxyProxyUrl('/ubus/', $proxyBasePath));
        $body = preg_replace(
            '/L\s*=\s*new\s+LuCI\(\s*\{/',
            'L = new LuCI({ "base_url": ' . $proxyResourceJson . ', "sessionid": ' . $ubusTokenJson . ', "ubuspath": ' . $proxyUbusPath . ',',
            $body,
            1
        );

        // Replace original sessionid value to prevent duplicate key overwrite
        if ($ubusToken) {
            $body = preg_replace(
                '/"sessionid"\s*:\s*"[a-f0-9]{32}"/',
                '"sessionid": ' . $ubusTokenJson,
                $body
            );
        }

        // Replace original ubuspath to route through proxy
        $body = preg_replace(
            '/"ubuspath"\s*:\s*"[^"]*"/',
            '"ubuspath": ' . $proxyUbusPath,
            $body
        );

        $proxyPrePatch = <<<HTML
<script>
try {
    window.sessionStorage.clear();
    window.localStorage.clear();
} catch (error) {}

(function() {
    if (window.__dashboardLuCIProxyRuntime) return;
    window.__dashboardLuCIProxyRuntime = true;

    var proxyBase = {$proxyBaseJson};
    var masterBase = {$masterBaseJson}.replace(/\\/+$/, '');
    window.__luciProxyUbusToken = {$ubusTokenJson};

    function proxyUrl(path) {
        path = String(path || '/cgi-bin/luci/admin/status/overview');
        if (path.charAt(0) !== '/') path = '/' + path;
        return proxyBase + '?path=' + encodeURIComponent(path);
    }

    /*
     * LuCI sometimes formats an asset URL after L.resource() returns it, for
     * example L.resource('icons/wifi%s.png').format(''). An encoded URL
     * contains %2F sequences which LuCI's formatter mistakes for directives.
     * Asset paths therefore keep their slashes literal; the browser safely
     * encodes any remaining special characters when issuing the request.
     */
    function proxyAssetUrl(path) {
        path = String(path || '/luci-static/resources');
        if (path.charAt(0) !== '/') path = '/' + path;
        return proxyBase + '?path=' + path;
    }

    function normalizeParts(values) {
        var parts = [];
        function collect(value) {
            if (Array.isArray(value)) {
                for (var i = 0; i < value.length; i++) collect(value[i]);
                return;
            }
            if (value === null || value === undefined || value === '') return;
            parts.push(String(value).replace(/^\\/+|\\/+$/g, ''));
        }
        for (var i = 0; i < values.length; i++) collect(values[i]);
        return parts.join('/');
    }

    function rewriteProxyTarget(url) {
        if (url === null || url === undefined) return url;

        var text = String(url);
        if (
            text === '' ||
            /^(data|blob|mailto|tel|javascript):/i.test(text) ||
            text.indexOf(proxyBase + '?path=') === 0
        ) {
            return url;
        }

        if (
            text.indexOf('/cgi-bin/luci') === 0 ||
            text.indexOf('/luci-static') === 0 ||
            text.indexOf('/ubus') === 0
        ) {
            return proxyUrl(text);
        }

        if (
            text.indexOf('cgi-bin/luci') === 0 ||
            text.indexOf('luci-static') === 0 ||
            text.indexOf('ubus') === 0
        ) {
            return proxyUrl('/' + text);
        }

        try {
            var parsed = new URL(text, window.location.href);
            if (parsed.href.indexOf(masterBase + '/') === 0) {
                return proxyUrl(parsed.pathname + parsed.search);
            }
        } catch (error) {}

        return url;
    }

    if (window.XMLHttpRequest && !window.XMLHttpRequest.__dashboardProxyPatched) {
        var rawOpen = window.XMLHttpRequest.prototype.open;
        window.XMLHttpRequest.prototype.open = function(method, url) {
            arguments[1] = rewriteProxyTarget(url);
            return rawOpen.apply(this, arguments);
        };
        window.XMLHttpRequest.__dashboardProxyPatched = true;
    }

    if (window.fetch && !window.fetch.__dashboardProxyPatched) {
        var rawFetch = window.fetch;
        var patchedFetch = function(input, init) {
            if (typeof input === 'string') {
                input = rewriteProxyTarget(input);
            } else if (input && input.url) {
                var rewritten = rewriteProxyTarget(input.url);
                if (rewritten !== input.url) {
                    input = new Request(rewritten, input);
                }
            }
            return rawFetch.call(this, input, init);
        };
        patchedFetch.__dashboardProxyPatched = true;
        window.fetch = patchedFetch;
    }

    function patchLuCI() {
        if (!window.L || window.L.__dashboardProxyPatched) return;
        window.L.__dashboardProxyPatched = true;
        window.L.url = function() {
            var path = normalizeParts(arguments);
            return proxyUrl('/cgi-bin/luci/' + path);
        };
        window.L.resource = function() {
            var path = normalizeParts(arguments);
            return proxyAssetUrl('/luci-static/resources/' + path);
        };
        window.L.media = function() {
            var path = normalizeParts(arguments);
            return proxyAssetUrl('/luci-static/openmanetargon/' + path);
        };
        window.L.env = window.L.env || {};
        window.L.env.base_url = proxyAssetUrl('/luci-static/resources');
        window.L.env.scriptname = proxyUrl('/cgi-bin/luci');
        window.L.env.resource = proxyAssetUrl('/luci-static/resources');
        window.L.env.media = proxyAssetUrl('/luci-static/openmanetargon');
        window.L.env.ubuspath = proxyUrl('/ubus/');

        // Inject the ubus RPC session token
        if (window.__luciProxyUbusToken) {
            window.L.env.sessionid = window.__luciProxyUbusToken;
            if (window.L.session) {
                window.L.session.getID = function() { return window.__luciProxyUbusToken; };
            }
        }

        if (window.L.Request && window.L.Request.expandURL && !window.L.Request.__dashboardProxyPatched) {
            var rawExpandURL = window.L.Request.expandURL;
            window.L.Request.expandURL = function(url) {
                return rewriteProxyTarget(rawExpandURL.call(this, url));
            };
            window.L.Request.__dashboardProxyPatched = true;
        }
    }
    window.__dashboardPatchLuCI = patchLuCI;
    patchLuCI();
    document.addEventListener('DOMContentLoaded', patchLuCI);
    window.addEventListener('load', patchLuCI);
})();
</script>
HTML;
        $proxyPatch = <<<HTML
<script>
if (window.__dashboardPatchLuCI) {
    window.__dashboardPatchLuCI();
}
</script>
HTML;
        $patchedBody = preg_replace(
            '/(<script[^>]*>\s*L\s*=\s*new\s+LuCI\([\s\S]*?\);\s*<\/script>)/i',
            $proxyPrePatch . '$1' . $proxyPatch,
            $body,
            1,
            $patchCount
        );

        if ($patchCount > 0) {
            $body = $patchedBody;
        } else {
            $body = str_ireplace('</body>', $proxyPrePatch . $proxyPatch . '</body>', $body);
        }
    }

    return $body;
}

function masterProxyRewriteCookie($cookie) {
    $cookie = preg_replace('/;\s*domain=[^;]*/i', '', $cookie);
    $cookie = preg_replace('/;\s*path=[^;]*/i', '; Path=/', $cookie);
    return $cookie;
}

function masterProxyForwardCookieHeader($cookieHeader, $appSessionName) {
    $cookies = [];

    foreach (explode(';', (string) $cookieHeader) as $cookie) {
        $cookie = trim($cookie);

        if ($cookie === '' || strpos($cookie, '=') === false) {
            continue;
        }

        [$name] = explode('=', $cookie, 2);
        $name = trim($name);

        if ($name === $appSessionName || strtolower($name) === 'phpsessid') {
            continue;
        }

        $cookies[] = $cookie;
    }

    return implode('; ', $cookies);
}

$targetPath = masterProxyPath($_GET['path'] ?? '/cgi-bin/luci/admin/status/overview');
$query = $_GET;
unset($query['path']);
if ($query) {
    $targetPath .= (strpos($targetPath, '?') === false ? '?' : '&') . http_build_query($query);
}

$targetUrl = $masterBaseUrl . $targetPath;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$requestBody = file_get_contents('php://input');

// Un-rewrite proxy URLs back to local router paths for ubus RPC calls
// LuCI sometimes constructs local filesystem paths using L.resource() (e.g. /www + L.resource(...))
// Since we patched L.resource() to return the proxy URL, these paths arrive as proxy URLs in ubus 'file' calls.
if (strpos($targetPath, '/ubus') === 0 && $requestBody !== '') {
    $proxyPathRegex = preg_quote($proxyBasePath, '#');
    $requestBody = preg_replace_callback(
        '#(/www)?' . $proxyPathRegex . '\?path=([^"\'\\\\,]+)#',
        function($matches) {
            $prefix = $matches[1] ? '/www' : '';
            return $prefix . urldecode($matches[2]);
        },
        $requestBody
    );
}

$requestHeaders = [];
$hasContentType = false;
foreach (getallheaders() as $name => $value) {
    $lower = strtolower($name);
    if (in_array($lower, ['host', 'connection', 'content-length', 'accept-encoding', 'origin', 'referer', 'cookie'], true)) {
        continue;
    }
    if ($lower === 'content-type') {
        $hasContentType = true;
    }
    $requestHeaders[] = $name . ': ' . $value;
}

$requestHeaders[] = 'Host: ' . $masterHost;
$requestHeaders[] = 'Origin: ' . $masterBaseUrl;
$requestHeaders[] = 'Referer: ' . $masterBaseUrl . '/cgi-bin/luci/';
$requestHeaders = masterProxyHeaders($requestHeaders);

// For POST requests, ensure Content-Length is set
if (!in_array($method, ['GET', 'HEAD'], true) && $requestBody !== '') {
    $requestHeaders[] = 'Content-Length: ' . strlen($requestBody);
    if (!$hasContentType) {
        $requestHeaders[] = 'Content-Type: application/json';
    }
}

$responseHeaders = [];
$statusCode = 200;

$ch = curl_init($targetUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => $requestHeaders,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HEADERFUNCTION => function ($curl, $headerLine) use (&$responseHeaders, &$statusCode) {
        $line = trim($headerLine);

        if ($line === '') {
            return strlen($headerLine);
        }

        if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $matches)) {
            $statusCode = (int) $matches[1];
            return strlen($headerLine);
        }

        $parts = explode(':', $line, 2);
        if (count($parts) === 2) {
            $responseHeaders[] = [trim($parts[0]), trim($parts[1])];
        }

        return strlen($headerLine);
    },
]);

if (!in_array($method, ['GET', 'HEAD'], true)) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
}

$forwardCookie = masterProxyForwardCookieHeader($_SERVER['HTTP_COOKIE'] ?? '', $appSessionName);

// Inject LuCI auto-login sysauth cookie
$sysauthToken = masterProxyGetSysauth(
    $masterBaseUrl,
    $luciUser,
    $luciPass,
    $sysauthTokenFile
);
if ($sysauthToken !== '') {
    // Remove any existing sysauth from forwarded cookies, use our auto-login one
    $forwardCookie = preg_replace('/\bsysauth[^=]*=[^;]*(;\s*)?/i', '', $forwardCookie);
    $forwardCookie = trim($forwardCookie, '; ');
    $forwardCookie = $forwardCookie !== '' ? $forwardCookie . '; ' . $sysauthToken : $sysauthToken;
}

if ($forwardCookie !== '') {
    curl_setopt($ch, CURLOPT_COOKIE, $forwardCookie);
}

$body = curl_exec($ch);
$curlError = curl_error($ch);
$curlStatus = curl_errno($ch);
curl_close($ch);

// If we got 403, token may be expired - retry with fresh login
if ($statusCode === 403 || ($statusCode >= 300 && $statusCode < 400 && stripos(implode(' ', array_map(function($h) { return $h[1]; }, $responseHeaders)), 'login') !== false)) {
    masterProxyInvalidateToken($sysauthTokenFile, $ubusTokenFile);
    $sysauthToken = masterProxyGetSysauth(
        $masterBaseUrl,
        $luciUser,
        $luciPass,
        $sysauthTokenFile
    );
    if ($sysauthToken !== '') {
        $responseHeaders = [];
        $statusCode = 200;

        $ch = curl_init($targetUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $requestHeaders,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_COOKIE => $sysauthToken,
            CURLOPT_HEADERFUNCTION => function ($curl, $headerLine) use (&$responseHeaders, &$statusCode) {
                $line = trim($headerLine);
                if ($line === '') return strlen($headerLine);
                if (preg_match('#^HTTP/\S+\s+(\d+)#', $line, $matches)) {
                    $statusCode = (int) $matches[1];
                    return strlen($headerLine);
                }
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $responseHeaders[] = [trim($parts[0]), trim($parts[1])];
                }
                return strlen($headerLine);
            },
        ]);
        if (!in_array($method, ['GET', 'HEAD'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        }
        $body = curl_exec($ch);
        $curlError = curl_error($ch);
        $curlStatus = curl_errno($ch);
        curl_close($ch);
    }
}

if ($body === false || $curlStatus !== 0) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Gagal menghubungi master panel: ' . $curlError;
    exit;
}

$contentType = 'text/html; charset=utf-8';
http_response_code($statusCode);

foreach ($responseHeaders as [$name, $value]) {
    $lower = strtolower($name);

    if (in_array($lower, [
        'connection',
        'transfer-encoding',
        'content-length',
        'content-encoding',
        'x-frame-options',
        'content-security-policy',
        'content-security-policy-report-only',
    ], true)) {
        continue;
    }

    if ($lower === 'content-type') {
        $contentType = $value;
        header($name . ': ' . $value, true);
        continue;
    }

    if ($lower === 'location') {
        header('Location: ' . masterProxyResolveUrl($value, $targetPath, $masterHost, $proxyBasePath), true);
        continue;
    }

    if ($lower === 'set-cookie') {
        header('Set-Cookie: ' . masterProxyRewriteCookie($value), false);
        continue;
    }

    header($name . ': ' . $value, false);
}

$ubusToken = masterProxyGetUbusToken(
    $masterBaseUrl,
    $luciUser,
    $luciPass,
    $ubusTokenFile
);
echo masterProxyRewriteBody(
    $body,
    $contentType,
    $targetPath,
    $masterHost,
    $masterBaseUrl,
    $proxyBasePath,
    $ubusToken
);
