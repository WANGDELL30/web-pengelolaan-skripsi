<?php
$masterConfigBaseUrl = 'http://192.168.1.50';
$masterConfigPath = '/cgi-bin/luci/';
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
    }

    .master-config-frame-wrap {
        height: calc(100vh - 220px);
        min-height: 560px;
        background: #f8f9fa;
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
        }

        .master-config-actions .btn {
            flex: 1;
        }

        .master-config-frame-wrap {
            height: calc(100vh - 280px);
            min-height: 480px;
        }
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

<div class="master-config-panel">
    <div class="master-config-toolbar">
        <div class="master-config-address">
            <i class="fas fa-network-wired"></i>
            <span><?php echo htmlspecialchars($masterConfigUrl); ?></span>
        </div>
        <div class="master-config-actions">
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
        <iframe
            id="masterConfigFrame"
            class="master-config-frame"
            src="<?php echo htmlspecialchars($masterConfigProxyUrl); ?>"
            title="Master Web Configuration"
            referrerpolicy="no-referrer"
        ></iframe>
    </div>

    <div class="master-config-note">
        Panel ditampilkan melalui proxy lokal karena firmware LuCI memakai proteksi X-Frame-Options. Jika login atau halaman tertentu tetap ditolak, gunakan tombol Tab untuk membuka panel langsung.
    </div>
</div>

<script>
$(function() {
    $('#reloadMasterConfig').on('click', function() {
        var frame = document.getElementById('masterConfigFrame');
        if (frame) {
            frame.src = '<?php echo addslashes($masterConfigProxyUrl); ?>';
        }
    });
});
</script>
