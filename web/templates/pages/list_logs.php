<?php
// Live Web Log Streaming Console Template
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a href="/list/web/" class="button button-secondary button-back js-button-back">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(__tr("Back to Web Domains", "Web Domainlerine Dön")) ?>
			</a>
			<a href="/list/log/" class="button button-secondary">
				<i class="fas fa-clock-rotate-left icon-purple"></i><?= tohtml(__tr("User Audit Logs", "Kullanıcı İşlem Günlükleri")) ?>
			</a>
			<a href="/list/api/" class="button button-secondary">
				<i class="fas fa-bolt icon-yellow"></i><?= tohtml(__tr("API Services", "API & Servisler")) ?>
			</a>
		</div>
		<div class="toolbar-right">
			<div class="toolbar-buttons">
				<!-- Live Stream Toggle -->
				<button type="button" id="btn-toggle-stream" class="button button-primary" onclick="toggleStream()">
					<i class="fas fa-circle" id="stream-pulse-icon" style="color: #22c55e; font-size: 9px; margin-right: 4px; animation: pulse-live 1.5s infinite;"></i>
					<span id="stream-btn-text"><?= tohtml(__tr("Live Streaming: Active", "Canlı Akış: Aktif")) ?></span>
				</button>
				<!-- Manual Refresh -->
				<button type="button" class="button button-secondary" onclick="fetchLogs(true)" title="<?= tohtml(__tr("Refresh Logs", "Yenile")) ?>">
					<i class="fas fa-arrow-rotate-right icon-green"></i><?= tohtml(__tr("Refresh", "Yenile")) ?>
				</button>
				<!-- Auto-scroll toggle -->
				<button type="button" id="btn-toggle-autoscroll" class="button button-secondary active" onclick="toggleAutoScroll()" title="<?= tohtml(__tr("Auto-scroll to latest log", "Otomatik alta kaydır")) ?>">
					<i class="fas fa-arrow-down-long icon-blue"></i><span id="autoscroll-text"><?= tohtml(__tr("Auto-Scroll: ON", "Oto-Kaydırma: Açık")) ?></span>
				</button>
				<!-- Download Log -->
				<?php if (!empty($active_domain)): ?>
					<a href="/list/logs/?download=1&domain=<?= urlencode($active_domain) ?>&type=<?= urlencode($log_type) ?>&token=<?= tohtml($_SESSION['token']) ?>" class="button button-secondary" title="<?= tohtml(__tr("Download Raw Log", "Ham Günlüğü İndir")) ?>">
						<i class="fas fa-download icon-orange"></i><?= tohtml(__tr("Download", "İndir")) ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container u-mt20">

	<div class="u-flex u-flex-wrap u-justify-between u-items-center u-mb20" style="gap: 15px;">
		<div>
			<h1 class="u-mb5" style="font-size: 1.4rem; font-weight: 700; display: flex; align-items: center; gap: 10px;">
				<i class="fas fa-terminal icon-green"></i>
				<?= tohtml(__tr("Live Web & Application Log Streaming Console", "Canlı Web & Uygulama Günlük Akış Konsolu")) ?>
			</h1>
			<p class="u-text-muted" style="font-size: 13px; margin: 0;">
				<?= tohtml(__tr("Real-time telemetry and streaming diagnostics for Nginx web server, PHP-FPM workers, and isolated Node.js / .NET backend units.", "Nginx web sunucusu, PHP-FPM ve izole Node.js / .NET arka plan servisleri için anlık telemetri ve canlı günlük akışı.")) ?>
			</p>
		</div>
		<div class="u-flex u-items-center" style="gap: 10px;">
			<span class="badge badge-secondary" style="padding: 6px 12px; font-family: monospace; font-size: 12px;">
				<i class="fas fa-server icon-blue u-mr5"></i><span id="stat-count"><?= count($initial_logs) ?></span> <?= tohtml(__tr("events loaded", "kayıt yüklendi")) ?>
			</span>
			<span class="badge badge-success" id="stream-status-badge" style="padding: 6px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;">
				<span style="width: 8px; height: 8px; border-radius: 50%; background: #22c55e; display: inline-block;"></span>
				<span id="stream-status-text"><?= tohtml(__tr("Polling every 2s", "Her 2 sn'de taranıyor")) ?></span>
			</span>
		</div>
	</div>

	<!-- Controls & Filters Bar -->
	<div class="card u-mb20" style="padding: 16px 20px; border-radius: 8px; background: var(--bg-card, #1e293b); border: 1px solid var(--border-color, #334155);">
		<div class="u-flex u-flex-wrap u-items-center u-justify-between" style="gap: 15px;">
			
			<!-- Domain and Log Type Selectors -->
			<div class="u-flex u-flex-wrap u-items-center" style="gap: 10px;">
				<!-- Domain Selector -->
				<div>
					<label class="form-label u-mb5 u-text-bold" style="font-size: 11px; text-transform: uppercase; color: #94a3b8;"><?= tohtml(__tr("Web Domain", "Web Alan Adı")) ?></label>
					<select id="domain-select" class="form-select" onchange="changeDomain(this.value)" style="min-width: 220px; font-weight: 600;">
						<?php if (empty($domain_names)): ?>
							<option value=""><?= tohtml(__tr("No web domains found", "Domain bulunamadı")) ?></option>
						<?php else: ?>
							<?php foreach ($domain_names as $dname): ?>
								<option value="<?= tohtml($dname) ?>" <?= $dname === $active_domain ? 'selected' : '' ?>>
									<?= tohtml($dname) ?>
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>

				<!-- Log Type Selector -->
				<div>
					<label class="form-label u-mb5 u-text-bold" style="font-size: 11px; text-transform: uppercase; color: #94a3b8;"><?= tohtml(__tr("Log Source", "Günlük Kaynağı")) ?></label>
					<div class="button-group" style="display: flex; gap: 4px;">
						<button type="button" class="button button-small <?= $log_type === 'access' ? 'button-primary' : 'button-secondary' ?>" onclick="changeLogType('access')">
							<i class="fas fa-globe icon-blue u-mr5"></i>Nginx Access
						</button>
						<button type="button" class="button button-small <?= $log_type === 'error' ? 'button-primary' : 'button-secondary' ?>" onclick="changeLogType('error')">
							<i class="fas fa-triangle-exclamation icon-orange u-mr5"></i>Nginx Error
						</button>
						<button type="button" class="button button-small <?= $log_type === 'app' ? 'button-primary' : 'button-secondary' ?>" onclick="changeLogType('app')">
							<i class="fas fa-bolt icon-yellow u-mr5"></i>App (Node/.NET)
						</button>
						<button type="button" class="button button-small <?= $log_type === 'php-error' ? 'button-primary' : 'button-secondary' ?>" onclick="changeLogType('php-error')">
							<i class="fab fa-php icon-purple u-mr5"></i>PHP-FPM
						</button>
					</div>
				</div>

				<!-- Lines Limit -->
				<div>
					<label class="form-label u-mb5 u-text-bold" style="font-size: 11px; text-transform: uppercase; color: #94a3b8;"><?= tohtml(__tr("Line Limit", "Satır Sınırı")) ?></label>
					<select id="lines-select" class="form-select" onchange="changeLines(this.value)" style="width: 100px;">
						<option value="50" <?= $lines === 50 ? 'selected' : '' ?>>50</option>
						<option value="100" <?= $lines === 100 ? 'selected' : '' ?>>100</option>
						<option value="250" <?= $lines === 250 ? 'selected' : '' ?>>250</option>
						<option value="500" <?= $lines === 500 ? 'selected' : '' ?>>500</option>
						<option value="1000" <?= $lines === 1000 ? 'selected' : '' ?>>1000</option>
					</select>
				</div>
			</div>

			<!-- Search & Level Filter Buttons -->
			<div class="u-flex u-flex-wrap u-items-center" style="gap: 10px;">
				<!-- Search Filter Input -->
				<div>
					<label class="form-label u-mb5 u-text-bold" style="font-size: 11px; text-transform: uppercase; color: #94a3b8;"><?= tohtml(__tr("Search Filter", "Arama Filtresi")) ?></label>
					<div style="position: relative;">
						<input type="text" id="log-search-input" class="form-control" placeholder="<?= tohtml(__tr("Filter text, IP, path, status...", "Metin, IP, path, durum ara...")) ?>" oninput="applyFilters()" style="padding-left: 32px; min-width: 240px;">
						<i class="fas fa-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 12px;"></i>
					</div>
				</div>

				<!-- Level Filter Tabs -->
				<div>
					<label class="form-label u-mb5 u-text-bold" style="font-size: 11px; text-transform: uppercase; color: #94a3b8;"><?= tohtml(__tr("Status Level", "Durum Kodu")) ?></label>
					<div class="button-group" style="display: flex; gap: 4px;">
						<button type="button" class="button button-small filter-btn active" data-filter="all" onclick="setFilterLevel('all', this)"><?= tohtml(__tr("All", "Tümü")) ?></button>
						<button type="button" class="button button-small filter-btn" data-filter="2xx" onclick="setFilterLevel('2xx', this)" style="color: #22c55e;">2xx OK</button>
						<button type="button" class="button button-small filter-btn" data-filter="3xx" onclick="setFilterLevel('3xx', this)" style="color: #38bdf8;">3xx</button>
						<button type="button" class="button button-small filter-btn" data-filter="4xx" onclick="setFilterLevel('4xx', this)" style="color: #f59e0b;">4xx</button>
						<button type="button" class="button button-small filter-btn" data-filter="5xx" onclick="setFilterLevel('5xx', this)" style="color: #ef4444;">5xx Error</button>
						<button type="button" class="button button-small filter-btn" data-filter="error" onclick="setFilterLevel('error', this)" style="color: #f43f5e;">Errors Only</button>
					</div>
				</div>
			</div>

		</div>
	</div>

	<!-- High-Tech Dark Terminal Screen -->
	<div class="terminal-container" style="background: #0b0f19; border: 1px solid #1e293b; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5); overflow: hidden; display: flex; flex-direction: column;">
		
		<!-- Terminal Header Bar -->
		<div style="background: #0f172a; padding: 10px 16px; border-bottom: 1px solid #1e293b; display: flex; align-items: center; justify-content: space-between;">
			<div style="display: flex; align-items: center; gap: 8px;">
				<span style="width: 12px; height: 12px; border-radius: 50%; background: #ef4444; display: inline-block;"></span>
				<span style="width: 12px; height: 12px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
				<span style="width: 12px; height: 12px; border-radius: 50%; background: #22c55e; display: inline-block;"></span>
				<span style="margin-left: 10px; font-family: monospace; font-size: 12px; color: #94a3b8; font-weight: 600;">
					<i class="fas fa-terminal icon-green u-mr5"></i>nexvia-stream://<span id="term-domain-title"><?= tohtml($active_domain ?: 'none') ?></span>/<span id="term-type-title"><?= tohtml($log_type) ?></span>
				</span>
			</div>
			<div style="display: flex; align-items: center; gap: 10px;">
				<button type="button" class="button button-secondary button-small" onclick="clearTerminal()" style="padding: 3px 10px; font-size: 11px; height: auto;">
					<i class="fas fa-eraser u-mr5"></i><?= tohtml(__tr("Clear Screen", "Ekranı Temizle")) ?>
				</button>
				<button type="button" class="button button-secondary button-small" onclick="toggleFullScreen()" style="padding: 3px 10px; font-size: 11px; height: auto;">
					<i class="fas fa-expand u-mr5"></i><?= tohtml(__tr("Fullscreen", "Tam Ekran")) ?>
				</button>
			</div>
		</div>

		<!-- Terminal Log Body -->
		<div id="terminal-body" style="height: 520px; overflow-y: auto; padding: 14px 16px; font-family: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', Menlo, Monaco, Consolas, monospace; font-size: 12.5px; line-height: 1.6; color: #e2e8f0; scroll-behavior: smooth;">
			<div id="log-entries-list">
				<!-- Injected by JavaScript -->
			</div>
			<div id="log-empty-state" style="display: none; padding: 60px 20px; text-align: center; color: #64748b;">
				<i class="fas fa-inbox fa-3x u-mb15" style="opacity: 0.4;"></i>
				<p style="font-size: 14px; font-weight: 500;"><?= tohtml(__tr("No log entries match your current search and filter criteria.", "Filtre kriterlerine uygun günlük kaydı bulunamadı.")) ?></p>
			</div>
		</div>

		<!-- Terminal Footer Status Bar -->
		<div style="background: #0f172a; padding: 8px 16px; border-top: 1px solid #1e293b; display: flex; align-items: center; justify-content: space-between; font-size: 11.5px; color: #64748b; font-family: monospace;">
			<div>
				<span><?= tohtml(__tr("Showing", "Gösterilen:")) ?> <strong id="stat-visible-count" style="color: #38bdf8;">0</strong> / <span id="stat-total-count">0</span></span>
				<span style="margin: 0 8px;">•</span>
				<span><?= tohtml(__tr("Buffer", "Tampon:")) ?> <strong id="stat-lines-limit"><?= $lines ?></strong> lines</span>
			</div>
			<div style="display: flex; align-items: center; gap: 12px;">
				<span id="stream-last-ping" style="color: #94a3b8;"><?= tohtml(__tr("Last updated: Just now", "Son güncelleme: Az önce")) ?></span>
				<span style="color: #22c55e;">● READY</span>
			</div>
		</div>

	</div>

</div>

<style>
@keyframes pulse-live {
	0% { transform: scale(0.95); opacity: 0.7; }
	50% { transform: scale(1.3); opacity: 1; }
	100% { transform: scale(0.95); opacity: 0.7; }
}
.log-row {
	display: flex;
	align-items: flex-start;
	padding: 4px 6px;
	border-radius: 4px;
	transition: background 0.15s ease;
	border-bottom: 1px solid rgba(255,255,255,0.03);
}
.log-row:hover {
	background: rgba(255, 255, 255, 0.06);
}
.log-gutter {
	user-select: none;
	color: #475569;
	min-width: 45px;
	text-align: right;
	padding-right: 12px;
	font-size: 11px;
}
.log-ts {
	color: #64748b;
	margin-right: 10px;
	font-size: 11px;
	white-space: nowrap;
}
.log-badge {
	font-size: 10px;
	font-weight: 700;
	padding: 1px 6px;
	border-radius: 3px;
	margin-right: 8px;
	text-transform: uppercase;
	display: inline-block;
}
.badge-get { background: rgba(56, 189, 248, 0.2); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.4); }
.badge-post { background: rgba(34, 197, 94, 0.2); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.4); }
.badge-put { background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.4); }
.badge-delete { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.4); }
.badge-patch { background: rgba(168, 85, 247, 0.2); color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.4); }

.badge-2xx { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
.badge-3xx { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
.badge-4xx { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
.badge-5xx { background: rgba(239, 68, 68, 0.2); color: #f87171; font-weight: bold; }

.badge-level-info { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
.badge-level-warn { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
.badge-level-error { background: rgba(239, 68, 68, 0.25); color: #f87171; font-weight: bold; }
.badge-level-debug { background: rgba(148, 163, 184, 0.2); color: #cbd5e1; }

.log-ip { color: #94a3b8; margin-right: 8px; font-weight: 500; font-size: 11px; }
.log-path { color: #f1f5f9; font-weight: 500; word-break: break-all; }
.log-msg { color: #e2e8f0; word-break: break-word; flex: 1; }
.log-copy-btn {
	opacity: 0;
	margin-left: 8px;
	background: transparent;
	border: none;
	color: #94a3b8;
	cursor: pointer;
	padding: 2px 5px;
	border-radius: 3px;
	font-size: 11px;
}
.log-row:hover .log-copy-btn {
	opacity: 1;
}
.log-copy-btn:hover {
	color: #38bdf8;
	background: rgba(255, 255, 255, 0.1);
}
.filter-btn.active {
	background: var(--button-primary-bg, #3b82f6) !important;
	color: #fff !important;
}
</style>

<script>
// State variables
let currentDomain = "<?= addslashes($active_domain) ?>";
let currentType = "<?= addslashes($log_type) ?>";
let currentLines = <?= $lines ?>;
let activeFilterLevel = 'all';
let isStreamingActive = true;
let isAutoScrollActive = true;
let pollTimer = null;
let rawLogsData = <?= json_encode($initial_logs) ?> || [];
let rawLogTexts = [];

// Initialize
document.addEventListener('DOMContentLoaded', () => {
	renderLogs();
	startPolling();
});

// Switch Domain
function changeDomain(domain) {
	currentDomain = domain;
	document.getElementById('term-domain-title').textContent = domain;
	fetchLogs(true);
}

// Switch Log Type
function changeLogType(type) {
	currentType = type;
	document.getElementById('term-type-title').textContent = type;
	
	// Update button active states
	document.querySelectorAll('.button-group button').forEach(btn => {
		if (btn.getAttribute('onclick') && btn.getAttribute('onclick').includes(type)) {
			btn.classList.add('button-primary');
			btn.classList.remove('button-secondary');
		} else if (btn.getAttribute('onclick') && btn.getAttribute('onclick').includes('changeLogType')) {
			btn.classList.remove('button-primary');
			btn.classList.add('button-secondary');
		}
	});
	
	fetchLogs(true);
}

// Switch Line limit
function changeLines(lines) {
	currentLines = parseInt(lines, 10);
	document.getElementById('stat-lines-limit').textContent = lines;
	fetchLogs(true);
}

// Toggle Live Stream
function toggleStream() {
	isStreamingActive = !isStreamingActive;
	const btnText = document.getElementById('stream-btn-text');
	const pulse = document.getElementById('stream-pulse-icon');
	const statusBadge = document.getElementById('stream-status-badge');
	const statusText = document.getElementById('stream-status-text');

	if (isStreamingActive) {
		btnText.textContent = "<?= tohtml(__tr("Live Streaming: Active", "Canlı Akış: Aktif")) ?>";
		pulse.style.animation = "pulse-live 1.5s infinite";
		pulse.style.color = "#22c55e";
		statusBadge.className = "badge badge-success";
		statusText.textContent = "<?= tohtml(__tr("Polling every 2s", "Her 2 sn'de taranıyor")) ?>";
		startPolling();
	} else {
		btnText.textContent = "<?= tohtml(__tr("Live Streaming: Paused", "Canlı Akış: Duraklatıldı")) ?>";
		pulse.style.animation = "none";
		pulse.style.color = "#f59e0b";
		statusBadge.className = "badge badge-warning";
		statusText.textContent = "<?= tohtml(__tr("Stream Paused", "Akış Duraklatıldı")) ?>";
		if (pollTimer) clearInterval(pollTimer);
	}
}

// Toggle AutoScroll
function toggleAutoScroll() {
	isAutoScrollActive = !isAutoScrollActive;
	const btn = document.getElementById('btn-toggle-autoscroll');
	const text = document.getElementById('autoscroll-text');
	if (isAutoScrollActive) {
		btn.classList.add('active');
		text.textContent = "<?= tohtml(__tr("Auto-Scroll: ON", "Oto-Kaydırma: Açık")) ?>";
		scrollToBottom();
	} else {
		btn.classList.remove('active');
		text.textContent = "<?= tohtml(__tr("Auto-Scroll: OFF", "Oto-Kaydırma: Kapalı")) ?>";
	}
}

// Scroll terminal to bottom
function scrollToBottom() {
	const tb = document.getElementById('terminal-body');
	if (tb) {
		tb.scrollTop = tb.scrollHeight;
	}
}

// Clear Terminal
function clearTerminal() {
	rawLogsData = [];
	renderLogs();
}

// Fullscreen Toggle
function toggleFullScreen() {
	const term = document.querySelector('.terminal-container');
	if (!document.fullscreenElement) {
		term.requestFullscreen().catch(err => alert(err.message));
	} else {
		document.exitFullscreen();
	}
}

// Level Filter Tab
function setFilterLevel(level, element) {
	activeFilterLevel = level;
	document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
	if (element) element.classList.add('active');
	applyFilters();
}

// Polling Engine
function startPolling() {
	if (pollTimer) clearInterval(pollTimer);
	pollTimer = setInterval(() => {
		if (isStreamingActive && !document.hidden && currentDomain) {
			fetchLogs(false);
		}
	}, 2000);
}

// Fetch logs via AJAX
function fetchLogs(forceScroll) {
	if (!currentDomain) return;

	const url = `/list/logs/?ajax=1&domain=${encodeURIComponent(currentDomain)}&type=${encodeURIComponent(currentType)}&lines=${currentLines}`;
	
	fetch(url)
		.then(res => res.json())
		.then(data => {
			if (data && data.status === 'success') {
				rawLogsData = data.lines || [];
				renderLogs();
				document.getElementById('stream-last-ping').textContent = "<?= tohtml(__tr("Last updated:", "Son güncelleme:")) ?> " + new Date().toLocaleTimeString();
				if (forceScroll || isAutoScrollActive) {
					scrollToBottom();
				}
			}
		})
		.catch(err => {
			console.warn("Log fetch error:", err);
		});
}

// Render logs into terminal
function renderLogs() {
	const container = document.getElementById('log-entries-list');
	const emptyState = document.getElementById('log-empty-state');
	const searchTerm = (document.getElementById('log-search-input').value || '').toLowerCase().trim();

	document.getElementById('stat-total-count').textContent = rawLogsData.length;
	document.getElementById('stat-count').textContent = rawLogsData.length;

	let visibleCount = 0;
	let html = '';
	rawLogTexts = [];

	rawLogsData.forEach((item, index) => {
		// Filter by Level / Status
		let statusStr = String(item.status || '');
		let levelStr = String(item.level || '').toUpperCase();
		let passesLevel = true;

		if (activeFilterLevel === '2xx' && !statusStr.startsWith('2')) passesLevel = false;
		if (activeFilterLevel === '3xx' && !statusStr.startsWith('3')) passesLevel = false;
		if (activeFilterLevel === '4xx' && !statusStr.startsWith('4')) passesLevel = false;
		if (activeFilterLevel === '5xx' && !statusStr.startsWith('5')) passesLevel = false;
		if (activeFilterLevel === 'error' && levelStr !== 'ERROR' && !statusStr.startsWith('5')) passesLevel = false;

		// Filter by search text
		const fullText = JSON.stringify(item).toLowerCase();
		let passesSearch = !searchTerm || fullText.includes(searchTerm);

		if (!passesLevel || !passesSearch) return;

		visibleCount++;
		const lineNum = item.id || (index + 1);
		const ts = item.timestamp || '';
		
		// Method badge
		let methodBadge = '';
		if (item.method) {
			const m = item.method.toUpperCase();
			const badgeClass = 'badge-' + m.toLowerCase();
			methodBadge = `<span class="log-badge ${escapeHtml(badgeClass)}">${escapeHtml(m)}</span>`;
		}

		// Status badge
		let statusBadge = '';
		if (item.status) {
			let sClass = 'badge-2xx';
			if (item.status.startsWith('3')) sClass = 'badge-3xx';
			if (item.status.startsWith('4')) sClass = 'badge-4xx';
			if (item.status.startsWith('5')) sClass = 'badge-5xx';
			statusBadge = `<span class="log-badge ${sClass}">${escapeHtml(item.status)}</span>`;
		}

		// Level badge for error/app logs
		let levelBadge = '';
		if (item.level && !item.status) {
			const lClass = 'badge-level-' + item.level.toLowerCase();
			levelBadge = `<span class="log-badge ${escapeHtml(lClass)}">${escapeHtml(item.level)}</span>`;
		}

		const ipHtml = item.ip ? `<span class="log-ip">${escapeHtml(item.ip)}</span>` : '';
		const pathHtml = item.path ? `<span class="log-path">${escapeHtml(item.path)}</span>` : '';
		const msgHtml = item.message ? `<span class="log-msg">${escapeHtml(item.message)}</span>` : (item.raw ? `<span class="log-msg">${escapeHtml(item.raw)}</span>` : '');

		// Raw (unescaped) text for the copy button, stored by row index so no
		// user-controlled data is ever embedded inside an inline JS string literal.
		rawLogTexts[index] = item.raw || `${ts} [${item.level || item.status}] ${item.message || item.path || ''}`;

		html += `
		<div class="log-row">
			<div class="log-gutter">${escapeHtml(lineNum)}</div>
			<div class="log-ts">${escapeHtml(ts)}</div>
			${methodBadge}
			${statusBadge}
			${levelBadge}
			${ipHtml}
			${pathHtml}
			${msgHtml}
				<button type="button" class="log-copy-btn" onclick="copyToClipboard(${index})" title="<?= tohtml(__tr("Copy line", "Satırı kopyala")) ?>">
				<i class="fas fa-copy"></i>
			</button>
		</div>`;
	});

	container.innerHTML = html;
	document.getElementById('stat-visible-count').textContent = visibleCount;

	if (visibleCount === 0) {
		emptyState.style.display = 'block';
	} else {
		emptyState.style.display = 'none';
	}

	if (isAutoScrollActive) {
		scrollToBottom();
	}
}

function applyFilters() {
	renderLogs();
}

function escapeHtml(str) {
	if (!str) return '';
	return String(str)
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;')
		.replace(/"/g, '&quot;')
		.replace(/'/g, '&#039;');
}

function copyToClipboard(index) {
	const text = rawLogTexts[index];
	if (text === undefined || text === null) return;
	navigator.clipboard.writeText(text).then(() => {
		// subtle feedback
	});
}
</script>
