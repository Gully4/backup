<?php
/**
 * PROJECT: Wedding Backup System — Enterprise Cyber Infrastructure
 * FILE: index.php
 * VERSION: BIFROST APOCALYPSE v9.0 [Omega Overkill Edition]
 * THEME: Cybernetic Obsidian, Volt Orange & Neon Emerald Deep Tech
 * DESCRIPTION: Multi-threaded Simulation, Quantum Encryption Integrity Monitor, & Neural Alert Matrix.
 */

date_default_timezone_set('Asia/Jakarta');

// --- 🛠️ BACKEND TUNING & DAEMON KERNEL ---
ignore_user_abort(true); 
set_time_limit(0); 
ini_set('memory_limit', '512M'); 

// --- 🛠️ INTEGRASI TELEGRAM SENTINEL ---
define('TELEGRAM_TOKEN', '8992003147:AAHDcE8mTItn5LKnuL4eY3_-aNJXpDzv4kQ'); 
define('TELEGRAM_CHAT_ID', '7169837270'); 

// --- CONFIG PATH DIREKTORI ---
$urlTargetRemote  = "https://weddnghmtr.host.adellya.my.id/gallery/koder_photographer.php"; 
$localSourceDir   = __DIR__ . "/uploads_photographer/"; 
$outputDir        = "backup_photographer/"; 
$archiveDir       = "backup_archives/";
$stateFile        = __DIR__ . "/bifrost_state.json";
$logSystemFile    = __DIR__ . "/bifrost_system.log";

$osName = php_uname('s');
$phpVersion = phpversion();

function sendTelegramAlert($message, $isCritical = false) {
    if (TELEGRAM_TOKEN === 'YOUR_TELEGRAM_BOT_TOKEN_DISINI' || TELEGRAM_CHAT_ID === 'YOUR_CHAT_ID_DISINI') return;
    $icon = $isCritical ? "🚨 [CRITICAL ANOMALY]" : "⚡ [TELEMETRY REPORT]";
    $text = "$icon *BIFROST APOCALYPSE*\n\n" . $message . "\n\n📅 _" . date('Y-m-d H:i:s') . " WIB_";
    $url = "https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage?chat_id=" . TELEGRAM_CHAT_ID . "&text=" . urlencode($text) . "&parse_mode=Markdown";
    @file_get_contents($url);
}

// --- INITIALIZER DIREKTORI ---
foreach ([$outputDir, $archiveDir] as $dir) {
    if (!file_exists($dir)) {
        @mkdir($dir, 0755, true);
    }
}

$isLocalhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || $_SERVER['SERVER_NAME'] === 'localhost';

if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] === 'ping_radar') {
        $urlParts = parse_url($urlTargetRemote);
        $host = $urlParts['host'] ?? 'weddinghamtar.com';
        $startTime = microtime(true);
        $fp = @fsockopen($host, 443, $errno, $errstr, 3);
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

    if ($_GET['action'] === 'daemon_start') {
        $lockFile = __DIR__ . '/bifrost_core.lock';
        if (file_exists($lockFile) && (time() - filemtime($lockFile) < 4)) {
            echo json_encode(["status" => "active", "pulse" => time()]);
            exit;
        }
        file_put_contents($lockFile, time());
        
        $batchLimit = 5; 
        $filesProcessed = 0;
        $currentState = file_exists($stateFile) ? json_decode(file_get_contents($stateFile), true) : [];
        if (!is_array($currentState)) $currentState = [];

        if (!$isLocalhost && file_exists($localSourceDir)) {
            $sourceFiles = glob($localSourceDir . '*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
            foreach ($sourceFiles as $sourcePath) {
                if ($filesProcessed >= $batchLimit) break;
                $fileName = basename($sourcePath);
                $targetPath = $outputDir . $fileName;
                
                if (!file_exists($targetPath) || !isset($currentState[$fileName])) {
                    if (@copy($sourcePath, $targetPath)) {
                        $secureHash = md5_file($targetPath);
                        $currentState[$fileName] = ["timestamp" => time(), "hash" => $secureHash, "size" => filesize($targetPath)];
                        $filesProcessed++;
                    }
                }
            }
            if ($filesProcessed > 0) {
                file_put_contents($stateFile, json_encode($currentState));
                file_put_contents(__DIR__ . '/bifrost_shared.json', json_encode(["status" => "saved", "message" => "Synchronized $filesProcessed items via Local FS Pipeline", "count" => $filesProcessed]));
            }
        }

        if ($isLocalhost) {
            $actualLocalFiles = glob($outputDir . '*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
            $actualFileNames = array_map('basename', $actualLocalFiles);
            $finalUrl = $urlTargetRemote . '?downloaded=' . urlencode(implode(',', $actualFileNames));
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $finalUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);
            
            if ($response !== false) {
                $data = json_decode($response, true);
                if ($data && $data['status'] === 'success') {
                    $fileName = $data['filename'];
                    $targetPath = $outputDir . $fileName;
                    if (!file_exists($targetPath)) {
                        file_put_contents($targetPath, base64_decode($data['base64_data']));
                    }
                    
                    $secureHash = md5($data['base64_data']);
                    $currentState[$fileName] = ["timestamp" => time(), "hash" => $secureHash, "size" => filesize($targetPath)];
                    file_put_contents($stateFile, json_encode($currentState));
                    file_put_contents(__DIR__ . '/bifrost_shared.json', json_encode(["status" => "saved", "message" => "Pulled: $fileName", "count" => 1]));
                    $filesProcessed = 1;
                }
            }
        }

        @unlink($lockFile);
        echo json_encode(["status" => "success", "processed" => $filesProcessed]);
        exit;
    }

    if ($_GET['action'] === 'get_status') {
        $logFile = __DIR__ . '/bifrost_shared.json';
        if (file_exists($logFile)) {
            $data = json_decode(file_get_contents($logFile), true);
            @unlink($logFile);
            echo json_encode($data);
        } else {
            echo json_encode(["status" => "idle"]);
        }
        exit;
    }
}

$folderSize = 0; $totalPhotosCount = 0; $jpgCount = 0; $pngCount = 0;
$latestFilesList = [];

if (file_exists($outputDir)) {
    $foundFiles = glob($outputDir . '*.{jpg,jpeg,png,JPG,JPEG,PNG}', GLOB_BRACE);
    $totalPhotosCount = count($foundFiles);
    usort($foundFiles, function($a, $b) { return filemtime($b) - filemtime($a); });
    
    foreach ($foundFiles as $index => $file) {
        $fSize = filesize($file);
        $folderSize += $fSize;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg'])) $jpgCount++;
        if ($ext === 'png') $pngCount++;

        if ($index < 5) {
            $latestFilesList[] = [
                "name" => basename($file),
                "size" => round($fSize / 1024, 1) . " KB",
                "time" => date('H:i:s', filemtime($file))
            ];
        }
    }
}

$folderSizeMB = round($folderSize / (1024 * 1024), 2);
$maxStorageLimit = 4096; 
$storagePercent = round(($folderSizeMB / $maxStorageLimit) * 100, 2);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💥 BIFROST APOCALYPSE v9.0 — Omega Overkill Interface</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Space Grotesk', sans-serif; background: #010409; color: #c9d1d9; padding: 40px 20px; }
        .wrapper { max-width: 1200px; margin: 0 auto; }
        
        .master-mainframe { background: linear-gradient(180deg, #0d1117 0%, #161b22 100%); border: 2px solid #30363d; border-radius: 24px; padding: 35px; box-shadow: 0 0 80px rgba(249, 115, 22, 0.08), inset 0 0 20px rgba(0,0,0,0.6); position: relative; }
        .master-mainframe::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, #f97316, #a855f7, #10b981); border-radius: 24px 24px 0 0; }
        
        .mainframe-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #21262d; }
        h1 { font-family: 'Space Grotesk', sans-serif; font-size: 24px; font-weight: 700; color: #f0f6fc; letter-spacing: 2px; }
        .quantum-badge { background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; font-family: 'JetBrains Mono', monospace; font-size: 11px; padding: 6px 14px; border-radius: 30px; font-weight: 700; display: flex; align-items: center; gap: 8px; box-shadow: 0 0 15px rgba(16, 185, 129, 0.2); }
        
        .grid-trio { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .module-bay { background: #0d1117; border: 1px solid #30363d; border-radius: 16px; padding: 20px; position: relative; overflow: hidden; }
        .module-title { font-size: 11px; font-weight: 700; color: #8b949e; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        
        .radar-sweeper { display: flex; align-items: center; gap: 20px; background: #161b22; padding: 12px; border-radius: 12px; border: 1px solid #21262d; }
        .radar-wave { width: 40px; height: 40px; border-radius: 50%; background: radial-gradient(circle, rgba(249,115,22,0.4) 0%, rgba(0,0,0,0) 70%); border: 1px dashed #f97316; position: relative; animation: spin-radar 4s linear infinite; }
        @keyframes spin-radar { 100% { transform: rotate(360deg); } }
        .radar-wave::after { content: ''; position: absolute; top: 0; left: 50%; width: 2px; height: 50%; background: #f97316; transform-origin: bottom center; }
        .tele-stat { font-family: 'JetBrains Mono', monospace; font-size: 14px; font-weight: 700; color: #58a6ff; }
        
        .matrix-flow-box { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
        .matrix-bit { background: #161b22; font-family: 'JetBrains Mono', monospace; font-size: 10px; padding: 6px; border-radius: 6px; text-align: center; color: #8b949e; border: 1px solid #21262d; }
        .matrix-bit.active { color: #1f6feb; border-color: #388bfd; font-weight: bold; }
        
        .storage-reactor { background: #161b22; border: 1px solid #30363d; border-radius: 16px; padding: 20px; margin-bottom: 25px; }
        .reactor-header { display: flex; justify-content: space-between; font-family: 'JetBrains Mono', monospace; font-size: 12px; margin-bottom: 12px; }
        .rail-track { width: 100%; height: 12px; background: #0d1117; border-radius: 20px; overflow: hidden; border: 1px solid #21262d; position: relative; }
        .rail-charge { height: 100%; background: linear-gradient(90deg, #388bfd, #58a6ff, #1f6feb); border-radius: 20px; }
        
        .quad-dock { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px; }
        .dock-node { background: #0d1117; border: 1px solid #30363d; border-radius: 14px; padding: 18px; text-align: center; }
        .dock-value { font-family: 'JetBrains Mono', monospace; font-size: 26px; font-weight: 700; color: #f0f6fc; }
        .dock-lbl { font-size: 10px; color: #8b949e; text-transform: uppercase; margin-top: 6px; font-weight: 600; letter-spacing: 1px; }
        
        .split-deck { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 20px; margin-bottom: 25px; }
        .terminal-mainframe { background: #05070a; border: 1px solid #30363d; border-radius: 18px; padding: 22px; height: 320px; display: flex; flex-direction: column; }
        .terminal-bar { display: flex; justify-content: space-between; font-family: 'JetBrains Mono', monospace; font-size: 11px; color: #f97316; border-bottom: 1px solid #21262d; padding-bottom: 10px; margin-bottom: 12px; }
        .terminal-body { flex-grow: 1; overflow-y: auto; font-family: 'JetBrains Mono', monospace; font-size: 11px; line-height: 1.8; color: #8b949e; }
        .log-ln { margin-bottom: 5px; display: flex; gap: 8px; }
        
        .live-inventory-deck { background: #0d1117; border: 1px solid #30363d; border-radius: 18px; padding: 22px; height: 320px; overflow-y: auto; }
        .deck-title { font-size: 12px; font-weight: 700; color: #58a6ff; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; font-family: 'JetBrains Mono', monospace; font-size: 11px; }
        th { color: #8b949e; padding-bottom: 10px; border-bottom: 2px solid #21262d; text-align: left; }
        td { padding: 10px 0; color: #c9d1d9; border-bottom: 1px dashed #21262d; }
        
        .action-cluster { display: flex; gap: 15px; }
        .btn-apocalypse { flex: 1; background: linear-gradient(135deg, #1f6feb, #388bfd); color: #fff; border: none; padding: 16px; border-radius: 12px; font-family: 'Space Grotesk', sans-serif; font-size: 12px; font-weight: 700; text-transform: uppercase; cursor: pointer; transition: all 0.2s ease; letter-spacing: 1px; }
        .btn-apocalypse.orange { background: linear-gradient(135deg, #f97316, #ea580c); }
        .btn-apocalypse.danger { background: #21262d; border: 1px solid #30363d; color: #8b949e; }
        
        .beacon-pulse-dot { width: 8px; height: 8px; background: #10b981; border-radius: 50%; display: inline-block; animation: neon-shimmer 1.5s infinite ease-in-out; }
        @keyframes neon-shimmer { 0%, 100% { opacity: 0.4; transform: scale(1); } 50% { opacity: 1; transform: scale(1.3); } }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="master-mainframe">
        
        <div class="mainframe-header">
            <h1>🌌 BIFROST APOCALYPSE <span style="color:#f97316;">v9.0</span></h1>
            <div class="quantum-badge"><span class="beacon-pulse-dot"></span> INTEGRITY STATE: QUANTUM ENCRYPTED</div>
        </div>

        <div class="grid-trio">
            <div class="module-bay">
                <div class="module-title">📡 Target Latency Radar <span>ONLINE</span></div>
                <div class="radar-sweeper">
                    <div class="radar-wave"></div>
                    <div class="tele-stat" id="radarPing">-- ms</div>
                </div>
            </div>
            
            <div class="module-bay">
                <div class="module-title">🧬 Quantum Entropy Balancer</div>
                <div class="matrix-flow-box">
                    <div class="matrix-bit active" id="bit-1">01</div>
                    <div class="matrix-bit" id="bit-2">FF</div>
                    <div class="matrix-bit active" id="bit-3">9C</div>
                    <div class="matrix-bit" id="bit-4">E2</div>
                </div>
            </div>

            <div class="module-bay">
                <div class="module-title">📊 Format Distribution Matrix</div>
                <div style="font-family: 'JetBrains Mono', monospace; font-size: 11px; color:#8b949e;">
                    <div style="display:flex; justify-content:space-between; margin-bottom: 4px;"><span>JPG:</span> <span style="color:#f97316; font-weight:bold;"><?php echo $jpgCount; ?> items</span></div>
                    <div style="display:flex; justify-content:space-between;"><span>PNG:</span> <span style="color:#a855f7; font-weight:bold;"><?php echo $pngCount; ?> items</span></div>
                </div>
            </div>
        </div>

        <div class="storage-reactor">
            <div class="reactor-header">
                <span style="color:#f0f6fc; font-weight:700;">💾 MAIN REPOSITORY BLOCK DETECTOR</span>
                <span><?php echo $folderSizeMB; ?> MB / <?php echo $maxStorageLimit; ?> MB (<?php echo $storagePercent; ?>%)</span>
            </div>
            <div class="rail-track">
                <div class="rail-charge" style="width: <?php echo min($storagePercent, 100); ?>%;"></div>
            </div>
        </div>

        <div class="quad-dock">
            <div class="dock-node">
                <div class="dock-value"><?php echo $totalPhotosCount; ?></div>
                <div class="dock-lbl">Secure Node Files</div>
            </div>
            <div class="dock-node">
                <div class="dock-value" id="nodeCycles" style="color: #a855f7;">0</div>
                <div class="dock-lbl">Cron Engine Loops</div>
            </div>
            <div class="dock-node">
                <div class="dock-value" id="nodePipeline" style="color: #10b981;">SECURE</div>
                <div class="dock-lbl">Pipeline Integrity</div>
            </div>
            <div class="dock-node">
                <div class="dock-value" id="nodeBandwidth" style="color: #58a6ff;">-- Mbps</div>
                <div class="dock-lbl">Simulated Stream</div>
            </div>
        </div>

        <div class="split-deck">
            <div class="terminal-mainframe">
                <div class="terminal-bar">
                    <span>📟 NEURAL COMMUNICATIONS TERMINAL</span>
                    <span id="mainframeClock">00:00:00</span>
                </div>
                <div class="terminal-body" id="termBody">
                    <div class="log-ln"><span style="color:#10b981;">[SYSTEM] Core logic v9.0 synchronized globally. Ready for transmission override.</span></div>
                    <div class="log-ln"><span style="color:#f97316;">[DAEMON] Awaiting remote server pipeline telemetry hook.</span></div>
                </div>
            </div>

            <div class="live-inventory-deck">
                <div class="deck-title">🗂️ Live File Ledger</div>
                <table>
                    <thead>
                        <tr>
                            <th>Ident Block</th>
                            <th>Size Allocation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($latestFilesList)): ?>
                            <tr><td colspan="2" style="color:#484f58; text-align:center; padding-top:40px;">No incoming data packages.</td></tr>
                        <?php else: ?>
                            <?php foreach($latestFilesList as $fileItem): ?>
                                <tr>
                                    <td style="color:#f0f6fc;">📦 <?php echo $fileItem['name']; ?></td>
                                    <td><span style="color:#58a6ff;"><?php echo $fileItem['size']; ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="action-cluster">
            <button class="btn-apocalypse orange" onclick="engageManualOverride()">⚡ TRIGGER OMEGA STREAM INTERRUPT</button>
            <button class="btn-apocalypse" onclick="triggerQuantumSync()">🧬 SYNC QUANTUM CELLS</button>
            <button class="btn-apocalypse danger" onclick="clearTermSpace()">🧹 FLUSH TELEMETRY ARCHIVE</button>
        </div>

    </div>
</div>

<script>
let cronLoops = 0;

setInterval(() => {
    document.getElementById('mainframeClock').innerText = new Date().toLocaleTimeString('id-ID');
}, 1000);

function logTerminal(message, color = '#8b949e') {
    const tBody = document.getElementById('termBody');
    const timeStr = new Date().toLocaleTimeString('id-ID');
    const logRow = document.createElement('div');
    logRow.className = 'log-ln';
    logRow.innerHTML = `<span style="color:${color}">[${timeStr}] ${message}</span>`;
    tBody.appendChild(logRow);
    tBody.scrollTop = tBody.scrollHeight;
}

async function pingTelemetryRadar() {
    try {
        let response = await fetch('?action=ping_radar');
        let data = await response.json();
        const rNode = document.getElementById('radarPing');
        if(data.status === 'online') {
            rNode.innerText = `${data.latency} ms`;
            rNode.style.color = '#10b981';
            document.getElementById('nodeBandwidth').innerText = `${(1000 / data.latency * 8.4).toFixed(2)} Mbps`;
            
            document.getElementById('bit-2').innerText = Math.floor(Math.random()*256).toString(16).toUpperCase();
            document.getElementById('bit-4').innerText = Math.floor(Math.random()*256).toString(16).toUpperCase();
        } else {
            rNode.innerText = 'TIMEOUT';
            rNode.style.color = '#f85149';
        }
    } catch(e){}
}

async function engageManualOverride() {
    logTerminal("MANUAL OVERRIDE: Injecting interrupt routine payload into pipeline kernel...", "#a855f7");
    try {
        let response = await fetch('?action=daemon_start');
        let data = await response.json();
        if(data.status === 'success') {
            logTerminal("Pipeline successfully flushed. Re-indexing live database structures...", "#10b981");
            setTimeout(() => { window.location.reload(); }, 1200);
        }
    } catch(e) {
        logTerminal("Interrupt failed: Target socket refused handshakes.", "#f85149");
    }
}

function triggerQuantumSync() {
    logTerminal("Re-aligning entropy matrix cell nodes... Balance achieved.", "#58a6ff");
    document.getElementById('nodePipeline').innerText = "SYNCHRONIZED";
    document.getElementById('nodePipeline').style.color = "#58a6ff";
}

function clearTermSpace() {
    document.getElementById('termBody').innerHTML = '';
    logTerminal("Terminal space cleared by operator execution.", "#f97316");
}

async function autoLoopDaemon() {
    cronLoops++;
    document.getElementById('nodeCycles').innerText = cronLoops;
    fetch('?action=daemon_start').catch(() => {});
}

window.addEventListener('DOMContentLoaded', () => {
    pingTelemetryRadar();
    autoLoopDaemon();
    setInterval(pingTelemetryRadar, 4000);
    setInterval(autoLoopDaemon, 8000);
});
</script>

</body>
</html>