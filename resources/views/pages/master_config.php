<?php
$masterConfigBaseUrl = 'http://192.168.1.50';
$masterConfigPath = '/cgi-bin/luci/admin/status/overview';
$masterConfigUrl = rtrim($masterConfigBaseUrl, '/') . $masterConfigPath;
$masterConfigRootUrl = rtrim($masterConfigBaseUrl, '/') . '/';
$masterConfigProxyUrl = 'master_proxy.php?path=' . rawurlencode($masterConfigPath);
?>

<style>
    .master-config-panel {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
        --master-config-zoom: 1;
    }

    .master-config-panel.is-fullscreen {
        position: fixed;
        inset: 12px;
        z-index: 1085;
        display: flex;
        flex-direction: column;
        border-radius: 10px;
    }

    body.master-config-locked {
        overflow: hidden;
    }

    .master-config-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        border-bottom: 1px solid #e9ecef;
    }

    .master-config-address {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        color: #1e3c72;
        font-weight: 600;
    }

    .master-config-address span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .master-config-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 0 0 auto;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .master-config-zoom-controls {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        background: #f8f9fa;
    }

    .master-config-zoom-level {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 54px;
        height: 31px;
        padding: 0 8px;
        border-radius: 6px;
        background: #ffffff;
        color: #1e3c72;
        font-size: 0.85rem;
        font-weight: 700;
    }

    .master-config-frame-wrap {
        position: relative;
        height: calc(100vh - 220px);
        min-height: 560px;
        overflow: auto;
        background: #f8f9fa;
    }

    .master-config-panel.is-fullscreen .master-config-frame-wrap {
        flex: 1 1 auto;
        height: auto;
        min-height: 0;
    }

    .master-config-frame-scale {
        width: 100%;
        height: 100%;
        min-width: 100%;
        min-height: 100%;
        transform: scale(var(--master-config-zoom));
        transform-origin: top left;
    }

    .master-config-frame {
        width: 100%;
        height: 100%;
        border: 0;
        display: block;
        background: #ffffff;
    }

    .master-config-note {
        padding: 10px 16px;
        color: #6c757d;
        border-top: 1px solid #e9ecef;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .master-config-toolbar {
            align-items: stretch;
            flex-direction: column;
        }

        .master-config-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .master-config-actions .btn {
            flex: 1;
        }

        .master-config-zoom-controls {
            width: 100%;
        }

        .master-config-zoom-controls .btn {
            flex: 1 1 0;
        }

        .master-config-frame-wrap {
            height: calc(100vh - 280px);
            min-height: 480px;
        }
    }

    /* Analytics Dashboard Styles */
    .master-analytics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .analytics-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fe 100%);
        border-radius: 12px;
        padding: 18px 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        border: 1px solid rgba(0,0,0,0.04);
        position: relative;
        overflow: hidden;
        transition: transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.3s ease;
    }

    .analytics-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    }

    .analytics-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #5e72e4, #825ee4);
        opacity: 0.8;
    }

    .analytics-card.status-danger::before {
        background: linear-gradient(90deg, #f5365c, #f56036);
    }

    .analytics-card.status-success::before {
        background: linear-gradient(90deg, #2dce89, #2dceb5);
    }

    .analytics-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: #fff;
        margin-bottom: 12px;
        background: linear-gradient(135deg, #5e72e4 0%, #825ee4 100%);
        box-shadow: 0 4px 10px rgba(94,114,228,0.3);
    }

    .analytics-card.status-success .analytics-icon {
        background: linear-gradient(135deg, #2dce89 0%, #2dbbce 100%);
        box-shadow: 0 4px 10px rgba(45,206,137,0.3);
    }

    .analytics-card.status-danger .analytics-icon {
        background: linear-gradient(135deg, #f5365c 0%, #f56036 100%);
        box-shadow: 0 4px 10px rgba(245,54,92,0.3);
    }

    .analytics-title {
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #8898aa;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .analytics-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #32325d;
        margin-bottom: 0;
        line-height: 1.2;
    }

    .analytics-desc {
        font-size: 0.85rem;
        color: #adb5bd;
        margin-top: 6px;
    }

    .analytics-progress {
        height: 6px;
        background-color: #e9ecef;
        border-radius: 3px;
        margin-top: 10px;
        overflow: hidden;
    }

    .analytics-progress-bar {
        height: 100%;
        border-radius: 3px;
        transition: width 0.5s ease;
    }

    /* Skeleton loader animation */
    @keyframes pulse-bg {
        0% { background-color: #f0f2f5; }
        50% { background-color: #e2e5e9; }
        100% { background-color: #f0f2f5; }
    }
    .skel-text {
        height: 24px;
        width: 60%;
        border-radius: 4px;
        animation: pulse-bg 1.5s infinite;
    }
    .skel-desc {
        height: 12px;
        width: 40%;
        border-radius: 4px;
        margin-top: 8px;
        animation: pulse-bg 1.5s infinite;
    }
</style>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h4 class="mb-1"><i class="fas fa-sliders"></i> Master Web Configuration</h4>
            <p class="text-muted mb-0">Akses panel konfigurasi WiFi HaLow Master di jaringan lokal.</p>
        </div>
        <a class="btn btn-primary" href="<?php echo htmlspecialchars($masterConfigUrl); ?>" target="_blank" rel="noopener">
            <i class="fas fa-up-right-from-square"></i> Buka Tab Baru
        </a>
    </div>
</div>

<!-- Analytics Dashboard -->
<div class="master-analytics-grid" id="masterAnalytics">
    <!-- Status Card -->
    <div class="analytics-card" id="card-status">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="analytics-title">Status Node</div>
                <div class="analytics-value" id="val-status"><div class="skel-text"></div></div>
            </div>
            <div class="analytics-icon"><i class="fas fa-server"></i></div>
        </div>
        <div class="analytics-desc" id="desc-status"><div class="skel-desc"></div></div>
    </div>

    <!-- CPU Card -->
    <div class="analytics-card" id="card-cpu">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="analytics-title">CPU Load</div>
                <div class="analytics-value" id="val-cpu"><div class="skel-text"></div></div>
            </div>
            <div class="analytics-icon" style="background: linear-gradient(135deg, #11cdef 0%, #1171ef 100%);"><i class="fas fa-microchip"></i></div>
        </div>
        <div class="analytics-progress"><div class="analytics-progress-bar" id="prog-cpu" style="width: 0%; background-color: #11cdef;"></div></div>
        <div class="analytics-desc" id="desc-cpu"><div class="skel-desc"></div></div>
    </div>

    <!-- Memory Card -->
    <div class="analytics-card" id="card-ram">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="analytics-title">Memory (RAM)</div>
                <div class="analytics-value" id="val-ram"><div class="skel-text"></div></div>
            </div>
            <div class="analytics-icon" style="background: linear-gradient(135deg, #fb6340 0%, #fbb140 100%);"><i class="fas fa-memory"></i></div>
        </div>
        <div class="analytics-progress"><div class="analytics-progress-bar" id="prog-ram" style="width: 0%; background-color: #fb6340;"></div></div>
        <div class="analytics-desc" id="desc-ram"><div class="skel-desc"></div></div>
    </div>

    <!-- Uptime Card -->
    <div class="analytics-card" id="card-uptime">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <div class="analytics-title">System Uptime</div>
                <div class="analytics-value" id="val-uptime"><div class="skel-text"></div></div>
            </div>
            <div class="analytics-icon" style="background: linear-gradient(135deg, #8965e0 0%, #bc65e0 100%);"><i class="fas fa-clock"></i></div>
        </div>
        <div class="analytics-desc" id="desc-uptime"><div class="skel-desc"></div></div>
    </div>
</div>

<div class="master-config-panel">
    <div class="master-config-toolbar">
        <div class="master-config-address">
            <i class="fas fa-network-wired"></i>
            <span><?php echo htmlspecialchars($masterConfigUrl); ?></span>
        </div>
        <div class="master-config-actions">
            <div class="master-config-zoom-controls" aria-label="Kontrol zoom master config">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="zoomOutMasterConfig" title="Zoom out">
                    <i class="fas fa-magnifying-glass-minus"></i>
                </button>
                <span class="master-config-zoom-level" id="masterConfigZoomLevel">100%</span>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="zoomInMasterConfig" title="Zoom in">
                    <i class="fas fa-magnifying-glass-plus"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="resetZoomMasterConfig" title="Reset zoom">
                    <i class="fas fa-rotate-left"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="fullscreenMasterConfig" title="Full width">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="reloadMasterConfig">
                <i class="fas fa-rotate-right"></i> Reload
            </button>
            <a class="btn btn-outline-secondary btn-sm" href="<?php echo htmlspecialchars($masterConfigRootUrl); ?>" target="_blank" rel="noopener">
                <i class="fas fa-network-wired"></i> IP
            </a>
            <a class="btn btn-outline-secondary btn-sm" href="<?php echo htmlspecialchars($masterConfigUrl); ?>" target="_blank" rel="noopener">
                <i class="fas fa-up-right-from-square"></i> Tab
            </a>
        </div>
    </div>

    <div class="master-config-frame-wrap">
        <div class="master-config-frame-scale" id="masterConfigScale">
            <iframe
                id="masterConfigFrame"
                class="master-config-frame"
                src="<?php echo htmlspecialchars($masterConfigProxyUrl); ?>"
                title="Master Web Configuration"
                referrerpolicy="no-referrer"
            ></iframe>
        </div>
    </div>

    <div class="master-config-note">
        Panel ditampilkan melalui proxy lokal karena firmware LuCI memakai proteksi X-Frame-Options. Jika login atau halaman tertentu tetap ditolak, gunakan tombol Tab untuk membuka panel langsung.
    </div>
</div>

<script>
$(function() {
    var zoom = parseFloat(localStorage.getItem('masterConfigZoom') || '1');
    var minZoom = 0.5;
    var maxZoom = 2;
    var zoomStep = 0.1;
    var panel = document.querySelector('.master-config-panel');
    var scaleWrap = document.getElementById('masterConfigScale');
    var zoomLevel = document.getElementById('masterConfigZoomLevel');
    var fullscreenButton = document.getElementById('fullscreenMasterConfig');

    function clampZoom(value) {
        return Math.min(maxZoom, Math.max(minZoom, value));
    }

    function applyZoom(value) {
        zoom = Math.round(clampZoom(value || 1) * 10) / 10;

        if (panel) {
            panel.style.setProperty('--master-config-zoom', zoom);
        }

        if (scaleWrap) {
            var baseSize = zoom < 1 ? (100 / zoom) : 100;
            scaleWrap.style.width = baseSize + '%';
            scaleWrap.style.height = baseSize + '%';
        }

        if (zoomLevel) {
            zoomLevel.textContent = Math.round(zoom * 100) + '%';
        }

        localStorage.setItem('masterConfigZoom', String(zoom));
    }

    function setFullscreen(enabled) {
        if (!panel || !fullscreenButton) {
            return;
        }

        panel.classList.toggle('is-fullscreen', enabled);
        document.body.classList.toggle('master-config-locked', enabled);
        fullscreenButton.innerHTML = enabled
            ? '<i class="fas fa-compress"></i>'
            : '<i class="fas fa-expand"></i>';
        fullscreenButton.setAttribute('title', enabled ? 'Keluar full width' : 'Full width');
    }

    applyZoom(zoom);

    $('#zoomOutMasterConfig').on('click', function() {
        applyZoom(zoom - zoomStep);
    });

    $('#zoomInMasterConfig').on('click', function() {
        applyZoom(zoom + zoomStep);
    });

    $('#resetZoomMasterConfig').on('click', function() {
        applyZoom(1);
    });

    $('#fullscreenMasterConfig').on('click', function() {
        if (panel) {
            setFullscreen(!panel.classList.contains('is-fullscreen'));
        }
    });

    $(document).on('keydown', function(event) {
        if (event.key === 'Escape') {
            setFullscreen(false);
        }
    });

    $('#reloadMasterConfig').on('click', function() {
        var frame = document.getElementById('masterConfigFrame');
        if (frame) {
            try {
                frame.contentWindow.location.reload();
            } catch (error) {
                frame.src = frame.src || '<?php echo addslashes($masterConfigProxyUrl); ?>';
            }
        }
    });

    // ==========================================
    // Analytics Dashboard Logic
    // ==========================================
    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        var k = 1024, sizes = ['B', 'KB', 'MB', 'GB'], i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    function formatUptime(seconds) {
        var d = Math.floor(seconds / 86400);
        var h = Math.floor((seconds % 86400) / 3600);
        var m = Math.floor((seconds % 3600) / 60);
        if (d > 0) return d + 'd ' + h + 'h';
        if (h > 0) return h + 'h ' + m + 'm';
        return m + 'm';
    }

    function updateAnalytics() {
        $.ajax({
            url: 'master_api.php',
            type: 'GET',
            dataType: 'json',
            timeout: 5000,
            success: function(data) {
                if (!data.online || !data.system_info) {
                    handleOffline();
                    return;
                }

                var sys = data.system_info;
                var board = data.system_board || {};

                // 1. Status Node
                $('#val-status').text('Online');
                $('#desc-status').text(board.hostname ? board.hostname + ' / ' + (board.release ? board.release.version : '') : 'Router Tersambung');
                $('#card-status').removeClass('status-danger').addClass('status-success');

                // 2. CPU Load
                // Load array from ubus is [1min, 5min, 15min] multiplied by 65535
                var load1m = (sys.load[0] / 65535).toFixed(2);
                var load5m = (sys.load[1] / 65535).toFixed(2);
                var loadPercent = Math.min(100, Math.round((load1m / 4) * 100)); // Assume max 4 cores for UI scale
                $('#val-cpu').text(load1m);
                $('#desc-cpu').text('5m avg: ' + load5m);
                $('#prog-cpu').css('width', loadPercent + '%');
                if (loadPercent > 80) $('#prog-cpu').css('background-color', '#f5365c');
                else $('#prog-cpu').css('background-color', '#11cdef');

                // 3. Memory
                if (sys.memory) {
                    var memTotal = sys.memory.total;
                    var memFree = sys.memory.free + (sys.memory.cached || 0) + (sys.memory.buffered || 0);
                    var memUsed = memTotal - memFree;
                    var memPercent = Math.round((memUsed / memTotal) * 100);

                    $('#val-ram').text(memPercent + '%');
                    $('#desc-ram').text(formatBytes(memUsed) + ' / ' + formatBytes(memTotal));
                    $('#prog-ram').css('width', memPercent + '%');
                    if (memPercent > 85) $('#prog-ram').css('background-color', '#f5365c');
                    else $('#prog-ram').css('background-color', '#fb6340');
                }

                // 4. Uptime
                if (sys.uptime) {
                    $('#val-uptime').text(formatUptime(sys.uptime));
                    var startDate = new Date(new Date().getTime() - (sys.uptime * 1000));
                    $('#desc-uptime').text('Since ' + startDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}));
                }
            },
            error: function() {
                handleOffline();
            }
        });
    }

    function handleOffline() {
        $('#val-status').text('Offline');
        $('#desc-status').text('Koneksi ke node terputus');
        $('#card-status').removeClass('status-success').addClass('status-danger');

        $('#val-cpu').text('--');
        $('#prog-cpu').css('width', '0%');
        $('#desc-cpu').text('No data');

        $('#val-ram').text('--');
        $('#prog-ram').css('width', '0%');
        $('#desc-ram').text('No data');

        $('#val-uptime').text('--');
        $('#desc-uptime').text('System unavailable');
    }

    // Initial fetch and interval
    updateAnalytics();
    setInterval(updateAnalytics, 5000);
});
</script>
