<?php
/**
 * PROJECT: Wedding Backup System — Enterprise Cyber Infrastructure
 * FILE: index.php [LOKAL DECK]
 * VERSION: BIFROST APOCALYPSE v9.6 [Mobile Optimized Framework]
 */

date_default_timezone_set('Asia/Jakarta');

ignore_user_abort(true); 
set_time_limit(0); 
ini_set('memory_limit', '512M'); 

// === CONFIG UTAMA ===
$urlTargetRemote  = "http://weddnghmtr.host.adellya.my.id/gallery/koder_photographer.php"; 
$outputDir        = __DIR__ . "/backup_photographer/"; 

// Bikin folder backup di lokal kalau belum ada
if (!file_exists($outputDir)) {
    @mkdir($outputDir, 0755, true);
}

// === ENGINE AJAX CONTROLLER ===
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    // 1. Detektor Sinyal Radar
    if ($_GET['action'] === 'ping_radar') {
        $urlParts = parse_url($urlTargetRemote);
        $host = $urlParts['host'] ?? 'weddnghmtr.host.adellya.my.id';
        $startTime = microtime(true);
        $fp = @fsockopen($host, 443, $errno, $errstr, 2);
        $stopTime = microtime(true);

        if ($fp) {
            fclose($fp);
            $latency = round(($stopTime - $startTime) * 1000);
            echo json_encode(["status" => "online", "latency" => $latency]);
        } else {
            echo json_encode(["status" => "offline", "latency" => 999]);
        }
        exit;
    }

    // 2. Mesin Sinkronisasi Gambar Riil via cURL
    if ($_GET['action'] === 'daemon_start') {
        $actualLocalFiles = glob($outputDir . '*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
        $actualFileNames = array_map('basename', $actualLocalFiles);

        $finalUrl = $urlTargetRemote . '?downloaded=' . urlencode(implode(',', $actualFileNames));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $finalUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            echo json_encode(["status" => "error", "message" => "cURL Error: " . $error_msg]);
            exit;
        }
        curl_close($ch);

        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['status']) && $data['status'] === 'success') {
                $fileName = $data['filename'];
                $targetPath = $outputDir . $fileName;

                $decodedData = base64_decode($data['base64_data']);
                if ($decodedData !== false) {
                    file_put_contents($targetPath, $decodedData);
                    echo json_encode([
                        "status" => "success", 
                        "message" => "Synchronized package: " . $fileName
                    ]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Corrupted base64 payload data."]);
                }
                exit;
            } else {
                echo json_encode([
                    "status" => "idle", 
                    "message" => isset($data['message']) ? $data['message'] : "No new data packets detected."
                ]);
                exit;
            }
        }
        echo json_encode(["status" => "error", "message" => "Failed to fetch response payload from target remote server."]);
        exit;
    }
}

// === LOGIK UNTUK PENGISIAN DATA DASHBOARD ===
$totalPhotosCount = 0;
$folderSize = 0;

if (file_exists($outputDir)) {
    $foundFiles = glob($outputDir . '*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
    $totalPhotosCount = count($foundFiles);
    foreach ($foundFiles as $file) {
        $folderSize += filesize($file);
    }
}
$folderSizeMB = round($folderSize / (1024 * 1024), 2);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌌 BIFROST APOCALYPSE v9.6 — Mobile UI Fixed</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Space Grotesk', sans-serif; background: #010409; color: #c9d1d9; padding: 20px 12px; }
        .master-mainframe { background: linear-gradient(180deg, #0d1117 0%, #161b22 100%); border: 2px solid #30363d; border-radius: 16px; padding: 20px; max-width: 1100px; margin: 0 auto; position: relative; box-shadow: 0 0 50px rgba(249,115,22,0.05); overflow: hidden; }
        .master-mainframe::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, #f97316, #10b981, #58a6ff); border-radius: 16px 16px 0 0; }

        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #21262d; padding-bottom: 15px; margin-bottom: 20px; gap: 10px; flex-wrap: wrap; }
        h1 { font-size: 20px; font-weight: 700; color: #f0f6fc; letter-spacing: 0.5px; }
        .status-badge { font-family:'JetBrains Mono'; font-size: 10px; color:#10b981; background:rgba(16,185,129,0.1); border:1px solid #10b981; padding:4px 10px; border-radius:20px; white-space: nowrap; }

        .grid-trio { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .module-bay { background: #0d1117; border: 1px solid #30363d; border-radius: 12px; padding: 15px; width: 100%; }
        .module-title { font-size: 10px; font-weight: 700; color: #8b949e; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.5px; }

        .radar-sweeper { display: flex; align-items: center; gap: 12px; background: #161b22; padding: 10px; border-radius: 8px; border: 1px solid #21262d; }
        .radar-wave { width: 28px; height: 28px; border-radius: 50%; border: 1px dashed #f97316; flex-shrink: 0; animation: spin 4s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        .terminal-box { background: #05070a; border: 1px solid #30363d; border-radius: 10px; padding: 12px; height: 180px; overflow-y: auto; font-family: 'JetBrains Mono', monospace; font-size: 11px; color: #8b949e; line-height: 1.5; word-break: break-all; }

        .btn-cluster { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn { flex: 1; min-width: 200px; padding: 14px; border-radius: 8px; border: none; font-weight: 700; cursor: pointer; color: white; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; transition: opacity 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .btn:hover { opacity: 0.9; }
        .btn-orange { background: linear-gradient(135deg, #f97316, #ea580c); }
        .btn-blue { background: linear-gradient(135deg, #1f6feb, #388bfd); }

        /* === MEDIA QUERY RESPONSIVE UNTUK LAYAR HP === */
        @media (max-width: 600px) {
            body { padding: 10px 6px; }
            .master-mainframe { padding: 15px; border-radius: 12px; }
            .header { flex-direction: column; align-items: flex-start; gap: 8px; }
            h1 { font-size: 18px; }
            .grid-trio { grid-template-columns: 1fr; gap: 12px; }
            .btn-cluster { flex-direction: column; gap: 10px; }
            .btn { width: 100%; flex: none; }
        }
    </style>
</head>
<body>

<div class="master-mainframe">
    <div class="header">
        <h1>🌌 BIFROST APOCALYPSE v9.6</h1>
        <span class="status-badge">● CORE ENGINE ACTIVE</span>
    </div>

    <div class="grid-trio">
        <!-- NODE RADAR -->
        <div class="module-bay">
            <div class="module-title">📡 Remote Node Latency</div>
            <div class="radar-sweeper">
                <div class="radar-wave"></div>
                <div id="radarPing" style="font-family:'JetBrains Mono'; font-weight:bold; color:#f97316; font-size: 13px;">Intercepting...</div>
            </div>
        </div>

        <!-- REPOSITORY STATISTICS -->
        <div class="module-bay">
            <div class="module-title">💾 Local Repository Metrics</div>
            <div style="font-family:'JetBrains Mono', monospace; font-size:12px; line-height: 1.6;">
                Files Synced : <span style="color:#f0f6fc; font-weight:bold;"><?php echo $totalPhotosCount; ?> Elements</span><br>
                Disk Storage : <span style="color:#10b981; font-weight:bold;"><?php echo $folderSizeMB; ?> MB</span>
            </div>
        </div>

        <!-- DIAGNOSTICS -->
        <div class="module-bay">
            <div class="module-title">⚙️ Kernel Telemetry Diagnostics</div>
            <div style="font-family:'JetBrains Mono', monospace; font-size:11px; color:#8b949e; line-height: 1.6;">
                PHP ARCH : <span style="color:#58a6ff;"><?php echo PHP_VERSION; ?></span><br>
                OS STACK : <span style="color:#a855f7;"><?php echo PHP_OS; ?></span>
            </div>
        </div>
    </div>

    <!-- MAIN OPERATIONAL LOG TERMINAL -->
    <div class="module-bay" style="margin-bottom: 20px;">
        <div class="module-title">📟 Operational Core Telemetry Log</div>
        <div class="terminal-box" id="termBody">
            <div>[SYSTEM] Framework v9.6 mobile fluid adaptation loaded.</div>
            <div>[SYSTEM] Local pipelines standby. Awaiting telemetry stream hooks...</div>
        </div>
    </div>

    <div class="btn-cluster">
        <button class="btn btn-orange" onclick="triggerManualSync()">⚡ Force Pipeline Run</button>
        <button class="btn btn-blue" onclick="checkRadarNow()">🔄 Recalibrate Signal Radar</button>
    </div>
</div>

<script>
let isSyncRunning = false;

function logToTerminal(message, color = '#8b949e') {
    const term = document.getElementById('termBody');
    const time = new Date().toLocaleTimeString('id-ID');
    term.innerHTML += `<div style="color:${color}">[${time}] ${message}</div>`;
    term.scrollTop = term.scrollHeight;
}

async function checkRadarNow() {
    try {
        let res = await fetch('?action=ping_radar');
        let data = await res.json();
        const pingNode = document.getElementById('radarPing');
        if (data.status === 'online') {
            pingNode.innerText = `${data.latency} ms (CONNECTED)`;
            pingNode.style.color = '#10b981';
        } else {
            pingNode.innerText = 'OFFLINE / TIMEOUT';
            pingNode.style.color = '#f85149';
        }
    } catch (e) {
        logToTerminal("Radar Error: Failed to catch host data handshake.", "#f85149");
    }
}

async function triggerManualSync() {
    if (isSyncRunning) return;
    isSyncRunning = true;

    logToTerminal("Executing network sync sweep pipeline...", "#f97316");
    try {
        let res = await fetch('?action=daemon_start');
        let data = await res.json();

        if (data.status === 'success') {
            logToTerminal(`[SUCCESS] ${data.message}`, '#10b981');
            setTimeout(() => { window.location.reload(); }, 1000);
        } else if (data.status === 'idle') {
            logToTerminal(`[IDLE] ${data.message}`, '#58a6ff');
        } else {
            logToTerminal(`[ERROR] ${data.message}`, '#f85149');
        }
    } catch(e) {
        logToTerminal("Pipeline stream error: Connection timed out or server execution limits hit.", "#f85149");
    } finally {
        isSyncRunning = false;
    }
}

window.addEventListener('DOMContentLoaded', () => {
    checkRadarNow();
    setInterval(checkRadarNow, 5000);
    setInterval(triggerManualSync, 10000);
});
</script>
</body>
</html>
