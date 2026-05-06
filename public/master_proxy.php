<?php
/**
 * Local reverse proxy for the WiFi HaLow master panel.
 *
 * LuCI returns X-Frame-Options: SAMEORIGIN, so a direct iframe from localhost is
 * blocked by the browser. This proxy keeps the iframe on the same origin as the
 * dashboard while forwarding requests only to the configured master IP.
 */

session_start();
require_once __DIR__ . '/../app/Helpers/functions.php';

if (!isLoggedIn()) {
    http_response_code(403);
    echo 'Login aplikasi diperlukan.';
    exit;
}

$masterHost = '192.168.1.50';
$masterBaseUrl = 'http://' . $masterHost;
$proxyBasePath = $_SERVER['SCRIPT_NAME'] ?? ('/' . basename(__FILE__));

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
        return '/cgi-bin/luci/';
    }

    if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
        return '/cgi-bin/luci/';
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

function masterProxyRewriteBody($body, $contentType, $currentPath, $masterHost, $proxyBasePath) {
    if (!preg_match('#(text/html|text/css|javascript|json|xml)#i', $contentType)) {
        return $body;
    }

    $rewrite = function ($url) use ($currentPath, $masterHost, $proxyBasePath) {
        return masterProxyResolveUrl($url, $currentPath, $masterHost, $proxyBasePath);
    };

    $body = preg_replace_callback(
        '/\b(href|src|action)=([\'"])(.*?)\2/i',
        function ($matches) use ($rewrite) {
            return $matches[1] . '=' . $matches[2] . htmlspecialchars($rewrite($matches[3]), ENT_QUOTES, 'UTF-8') . $matches[2];
        },
        $body
    );

    $body = preg_replace_callback(
        '/url\(([\'"]?)(.*?)\1\)/i',
        function ($matches) use ($rewrite) {
            return 'url(' . $matches[1] . $rewrite($matches[2]) . $matches[1] . ')';
        },
        $body
    );

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

    if (stripos($body, 'new LuCI({') !== false) {
        $proxyBaseJson = json_encode($proxyBasePath);
        $proxyResourceJson = json_encode(masterProxyProxyUrl('/luci-static/resources', $proxyBasePath));
        $body = preg_replace(
            '/L\s*=\s*new\s+LuCI\(\s*\{/',
            'L = new LuCI({ "base_url": ' . $proxyResourceJson . ',',
            $body,
            1
        );

        $proxyPrePatch = <<<HTML
<script>
try {
    window.sessionStorage.removeItem('luci-session-store');
} catch (error) {}
</script>
HTML;
        $proxyPatch = <<<HTML
<script>
(function() {
    var proxyBase = {$proxyBaseJson};
    function proxyUrl(path) {
        path = String(path || '/cgi-bin/luci/');
        if (path.charAt(0) !== '/') path = '/' + path;
        return proxyBase + '?path=' + encodeURIComponent(path);
    }
    function patchLuCI() {
        if (!window.L || window.L.__dashboardProxyPatched) return;
        window.L.__dashboardProxyPatched = true;
        window.L.url = function() {
            var parts = Array.prototype.slice.call(arguments)
                .flat()
                .filter(function(part) { return part !== null && part !== undefined && part !== ''; })
                .map(function(part) { return String(part).replace(/^\\/+|\\/+$/g, ''); });
            return proxyUrl('/cgi-bin/luci/' + parts.join('/'));
        };
        window.L.resource = function() {
            var parts = Array.prototype.slice.call(arguments)
                .flat()
                .filter(function(part) { return part !== null && part !== undefined && part !== ''; })
                .map(function(part) { return String(part).replace(/^\\/+|\\/+$/g, ''); });
            return proxyUrl('/luci-static/resources/' + parts.join('/'));
        };
        window.L.media = function() {
            var parts = Array.prototype.slice.call(arguments)
                .flat()
                .filter(function(part) { return part !== null && part !== undefined && part !== ''; })
                .map(function(part) { return String(part).replace(/^\\/+|\\/+$/g, ''); });
            return proxyUrl('/luci-static/openmanetargon/' + parts.join('/'));
        };
        window.L.env = window.L.env || {};
        window.L.env.scriptname = proxyUrl('/cgi-bin/luci');
        window.L.env.resource = proxyUrl('/luci-static/resources');
        window.L.env.ubuspath = proxyUrl('/ubus/');
    }
    patchLuCI();
    document.addEventListener('DOMContentLoaded', patchLuCI);
    window.addEventListener('load', patchLuCI);
})();
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
            $body = str_ireplace('</body>', $proxyPatch . '</body>', $body);
        }
    }

    return $body;
}

function masterProxyRewriteCookie($cookie) {
    $cookie = preg_replace('/;\s*domain=[^;]*/i', '', $cookie);
    $cookie = preg_replace('/;\s*path=[^;]*/i', '; Path=/', $cookie);
    return $cookie;
}

$targetPath = masterProxyPath($_GET['path'] ?? '/cgi-bin/luci/');
$query = $_GET;
unset($query['path']);
if ($query) {
    $targetPath .= (strpos($targetPath, '?') === false ? '?' : '&') . http_build_query($query);
}

$targetUrl = $masterBaseUrl . $targetPath;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$requestBody = file_get_contents('php://input');

$requestHeaders = [];
foreach (getallheaders() as $name => $value) {
    $lower = strtolower($name);
    if (in_array($lower, ['host', 'connection', 'content-length', 'accept-encoding', 'origin', 'referer'], true)) {
        continue;
    }
    $requestHeaders[] = $name . ': ' . $value;
}

$requestHeaders[] = 'Host: ' . $masterHost;
$requestHeaders[] = 'Origin: ' . $masterBaseUrl;
$requestHeaders[] = 'Referer: ' . $masterBaseUrl . '/cgi-bin/luci/';

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

if (!empty($_SERVER['HTTP_COOKIE'])) {
    curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE']);
}

$body = curl_exec($ch);
$curlError = curl_error($ch);
$curlStatus = curl_errno($ch);
curl_close($ch);

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

echo masterProxyRewriteBody($body, $contentType, $targetPath, $masterHost, $proxyBasePath);
