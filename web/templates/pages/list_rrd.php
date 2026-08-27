<?php
$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');

$rrd_meta = [
	"la" => [
		"cat" => "system",
		"icon" => "fa-microchip",
		"badge" => $is_tr ? "CPU İşlemci Yükü" : "CPU Load Average",
		"desc" => $is_tr ? "İşlemcinin 1dk, 5dk ve 15dk aralıklarındaki iş parçacığı kuyruk yoğunluğunu gösterir." : "Shows CPU run queue length over 1, 5, and 15 minute intervals.",
		"tip" => $is_tr ? ("12 çekirdekli sisteminizde yük " . ($live_metrics["cores"] ?? 1) . ".0 altındayken sunucu sıfır darboğaz ile son derece akıcı çalışır.") : ("Values below total core count (" . ($live_metrics["cores"] ?? 1) . ") indicate zero CPU bottlenecks.")
	],
	"mem" => [
		"cat" => "system",
		"icon" => "fa-memory",
		"badge" => $is_tr ? "RAM & Takas Alanı (Swap)" : "RAM & Swap Memory",
		"desc" => $is_tr ? "Fiziksel RAM ve Swap takas alanı tüketim geçmişini gösterir. Linux'un kullanılabilir RAM'i dosya önbelleği (Cache) için tutması normaldir." : "Tracks physical RAM and Swap usage history. Linux caches active files in free RAM for peak disk speed.",
		"tip" => $is_tr ? "Swap kullanımı %0 civarındayken tüm işlemler disk beklemeden ultra-hızlı RAM üzerinde döner." : "Near 0% Swap usage confirms in-memory processing without disk latency."
	],
	"net" => [
		"cat" => "system",
		"icon" => "fa-network-wired",
		"badge" => $is_tr ? "Ağ & Bant Genişliği (RX/TX)" : "Network Bandwidth (RX/TX)",
		"desc" => $is_tr ? "Sunucuya gelen (Gelen/RX) ve sunucudan çıkan (Giden/TX) anlık ağ veri trafiğini gösterir." : "Monitors incoming (RX) and outgoing (TX) network bandwidth throughput.",
		"tip" => $is_tr ? "Ani yükselişler ziyaretçi akışını veya potansiyel DDoS saldırılarını teşhis etmenizi sağlar." : "Spikes help identify visitor rushes or inbound flood attempts."
	],
	"web" => [
		"cat" => "web",
		"icon" => "fa-earth-americas",
		"badge" => $is_tr ? "Nginx Web Sunucu Trafiği" : "NGINX Web Traffic",
		"desc" => $is_tr ? "Nginx web sunucusuna gelen saniyelik HTTP isteklerini ve eşzamanlı aktif istemci bağlantılarını gösterir." : "Measures active HTTP requests per second and concurrent client connections handled by NGINX.",
		"tip" => $is_tr ? "FastCGI microcache aktif edildiğinde isteklerin çoğu PHP'ye yük bindirmeden Nginx'ten servis edilir." : "FastCGI microcache serves cached hits directly with microsecond latency."
	],
	"db" => [
		"cat" => "db",
		"icon" => "fa-database",
		"badge" => $is_tr ? "Veritabanı Motoru (SQL)" : "Database Engine (SQL)",
		"desc" => $is_tr ? "MariaDB/MySQL veya PostgreSQL veritabanına yapılan saniyelik sorgu (QPS) ve bağlantı havuzu trafiğini gösterir." : "Tracks queries per second (QPS) and active database connection pool load on MariaDB/MySQL/PostgreSQL.",
		"tip" => $is_tr ? "Redis nesne önbelleği açıldığında mükerrer SQL okuma yükü belirgin şekilde düşer." : "Enabling Redis object cache significantly reduces repeated SQL reads."
	],
	"mail" => [
		"cat" => "services",
		"icon" => "fa-envelope",
		"badge" => $is_tr ? "Exim Mail Sunucusu" : "Exim Mail Server",
		"desc" => $is_tr ? "Gelen ve giden e-posta iletimlerini, kuyrukta bekleyen teslimat mesajlarını gösterir." : "Monitors incoming/outgoing SMTP email messages and delivery queues processed by Exim.",
		"tip" => $is_tr ? "Ani gönderim artışları spam veya bülten gönderimlerini tespit etmeye yarar." : "Sudden spikes indicate newsletter campaigns or potential outbound spam."
	],
	"ssh" => [
		"cat" => "services",
		"icon" => "fa-terminal",
		"badge" => $is_tr ? "SSH Güvenli Oturumlar" : "SSH Secure Sessions",
		"desc" => $is_tr ? "Sunucuya SSH üzerinden kurulan güvenli terminal bağlantılarını ve oturum sayısını gösterir." : "Monitors active secure terminal SSH sessions and remote administration access.",
		"tip" => $is_tr ? "Tanımadığınız oturum artışları varsa Güvenlik/Fail2ban ban listesini kontrol edin." : "Unfamiliar session spikes can be cross-referenced with Fail2ban logs."
	],
	"ftp" => [
		"cat" => "services",
		"icon" => "fa-folder-tree",
		"badge" => $is_tr ? "FTP Dosya Transferi" : "FTP File Transfers",
		"desc" => $is_tr ? "FTP dosya yükleme ve indirme oturumlarını gösterir." : "Tracks FTP file uploads, downloads, and concurrent file transfer connections.",
		"tip" => $is_tr ? "Kullanmıyorsanız servisler sayfasından vsftpd'yi kapatıp bellek tasarrufu sağlayabilirsiniz." : "If unused, vsftpd can be stopped from services to save memory."
	]
];
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/server/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml( _("Back")) ?>
			</a>
			<a href="/list/server/?cpu" class="button button-secondary">
				<i class="fas fa-chart-pie icon-green"></i><?= tohtml( _("Advanced Details")) ?>
			</a>
		</div>
		<div class="toolbar-right">
			<a class="toolbar-link<?php if ((empty($period)) || ($period == 'daily')) echo " selected" ?>" href="?<?= tohtml(http_build_query(["period" => 'daily'])) ?>"><?= tohtml( _("Daily")) ?></a>
			<a class="toolbar-link<?php if ((!empty($period)) && ($period == 'weekly')) echo " selected" ?>" href="?<?= tohtml(http_build_query(["period" => 'weekly'])) ?>"><?= tohtml( _("Weekly")) ?></a>
			<a class="toolbar-link<?php if ((!empty($period)) && ($period == 'monthly')) echo " selected" ?>" href="?<?= tohtml(http_build_query(["period" => 'monthly'])) ?>"><?= tohtml( _("Monthly")) ?></a>
			<a class="toolbar-link<?php if ((!empty($period)) && ($period == 'yearly')) echo " selected" ?>" href="?<?= tohtml(http_build_query(["period" => 'yearly'])) ?>"><?= tohtml( _("Yearly")) ?></a>
			<a class="toolbar-link<?php if ((!empty($period)) && ($period == 'biennially')) echo " selected" ?>" href="?<?= tohtml(http_build_query(["period" => 'biennially'])) ?>"><?= tohtml( _("Biennially")) ?></a>
			<a class="toolbar-link<?php if ((!empty($period)) && ($period == 'triennially')) echo " selected" ?>" href="?<?= tohtml(http_build_query(["period" => 'triennially'])) ?>"><?= tohtml( _("Triennially")) ?></a>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container" style="max-width: 1400px; padding: 15px 20px;">

	<!-- Live Telemetry Hero Grid -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 15px; margin-bottom: 25px;">
		
		<!-- Card 1: CPU Load -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= $is_tr ? "CPU & Yük Ortalaması" : "CPU & Load Average" ?>
					</span>
					<h3 style="margin: 4px 0 0 0; font-size: 1.4rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= round($live_metrics["load"][0] ?? 0.1, 2) ?>
						<small style="font-size: 12px; font-weight: normal; color: var(--color-text-muted, #94a3b8);">/ <?= $live_metrics["cores"] ?? 1 ?> <?= $is_tr ? "Çekirdek" : "Cores" ?></small>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(168,85,247,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-microchip icon-purple" style="font-size: 16px;"></i>
				</div>
			</div>
			<div style="font-size: 11px; color: var(--color-text-muted, #94a3b8); margin-top: 4px; display: flex; justify-content: space-between;">
				<span>1dk: <b style="color: var(--color-text, #f8fafc);"><?= round($live_metrics["load"][0] ?? 0.1, 2) ?></b></span>
				<span>5dk: <b style="color: var(--color-text, #f8fafc);"><?= round($live_metrics["load"][1] ?? 0.1, 2) ?></b></span>
				<span>15dk: <b style="color: var(--color-text, #f8fafc);"><?= round($live_metrics["load"][2] ?? 0.1, 2) ?></b></span>
			</div>
			<div style="margin-top: 8px; font-size: 11px; color: #22c55e; display: flex; align-items: center; gap: 5px;">
				<i class="fas fa-circle-check"></i> <?= $is_tr ? "İşlemci son derece rahat" : "CPU running smoothly" ?>
			</div>
		</div>

		<!-- Card 2: Memory -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= $is_tr ? "Fiziksel Bellek (RAM)" : "Memory (RAM) Usage" ?>
					</span>
					<h3 style="margin: 4px 0 0 0; font-size: 1.4rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= round($live_metrics["mem_used_mb"] / 1024, 1) ?> GB
						<small style="font-size: 12px; font-weight: normal; color: var(--color-text-muted, #94a3b8);">/ <?= round($live_metrics["mem_total_mb"] / 1024, 1) ?> GB (<?= $live_metrics["mem_percent"] ?>%)</small>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(34,197,94,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-memory icon-green" style="font-size: 16px;"></i>
				</div>
			</div>
			<!-- Progress Bar -->
			<div style="width: 100%; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; margin: 6px 0;">
				<div style="width: <?= min(100, $live_metrics["mem_percent"]) ?>%; height: 100%; background: <?= $live_metrics["mem_percent"] > 85 ? "#ef4444" : "#22c55e" ?>; border-radius: 3px;"></div>
			</div>
			<div style="font-size: 11px; color: var(--color-text-muted, #94a3b8); display: flex; justify-content: space-between;">
				<span><?= $is_tr ? "Boş/Kullanılabilir" : "Available" ?>: <b><?= round($live_metrics["mem_avail_mb"] / 1024, 1) ?> GB</b></span>
				<span style="color: #22c55e;"><?= $is_tr ? "Sağlıklı" : "Healthy" ?></span>
			</div>
		</div>

		<!-- Card 3: Disk Space -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= $is_tr ? "Disk Alanı (Kök /)" : "Disk Storage (Root /)" ?>
					</span>
					<h3 style="margin: 4px 0 0 0; font-size: 1.4rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= $live_metrics["disk_used_gb"] ?> GB
						<small style="font-size: 12px; font-weight: normal; color: var(--color-text-muted, #94a3b8);">/ <?= $live_metrics["disk_total_gb"] ?> GB (<?= $live_metrics["disk_percent"] ?>%)</small>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(56,189,248,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-hard-drive icon-blue" style="font-size: 16px;"></i>
				</div>
			</div>
			<!-- Progress Bar -->
			<div style="width: 100%; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; margin: 6px 0;">
				<div style="width: <?= min(100, $live_metrics["disk_percent"]) ?>%; height: 100%; background: #38bdf8; border-radius: 3px;"></div>
			</div>
			<div style="font-size: 11px; color: var(--color-text-muted, #94a3b8); display: flex; justify-content: space-between;">
				<span><?= $is_tr ? "Boş Alan" : "Free Space" ?>: <b><?= round($live_metrics["disk_total_gb"] - $live_metrics["disk_used_gb"], 1) ?> GB</b></span>
				<span style="color: #38bdf8;"><?= $is_tr ? "Bol Yer Var" : "Plenty of Space" ?></span>
			</div>
		</div>

		<!-- Card 4: Uptime & Health -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= $is_tr ? "Çalışma Süresi (Uptime)" : "Server Uptime" ?>
					</span>
					<h3 style="margin: 4px 0 0 0; font-size: 1.4rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= $live_metrics["uptime_str"] ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(234,179,8,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-heart-pulse icon-orange" style="font-size: 16px;"></i>
				</div>
			</div>
			<div style="font-size: 11px; color: var(--color-text-muted, #94a3b8); margin-top: 4px;">
				<?= $is_tr ? "RRD Dönemi" : "RRD Period" ?>: <b style="text-transform: capitalize; color: var(--color-text, #f8fafc);"><?= htmlspecialchars($period) ?></b>
			</div>
			<div style="margin-top: 8px; font-size: 11px; color: #22c55e; display: flex; align-items: center; gap: 5px;">
				<i class="fas fa-bolt"></i> <?= $is_tr ? "Tüm Servisler Aktif & Dengeli" : "All services operational" ?>
			</div>
		</div>

	</div>

	<!-- Category Filter Tabs -->
	<div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; border-bottom: 1px solid var(--border-color, #334155); padding-bottom: 12px;">
		<button type="button" class="button button-primary button-small rrd-cat-btn active" onclick="filterRrdCategory('all', this);" style="font-size: 12px;">
			<i class="fas fa-layer-group"></i> <?= $is_tr ? "Tüm Grafikler" : "All Graphs" ?> (<?= count($data) ?>)
		</button>
		<button type="button" class="button button-secondary button-small rrd-cat-btn" onclick="filterRrdCategory('system', this);" style="font-size: 12px;">
			<i class="fas fa-server"></i> <?= $is_tr ? "Donanım & Sistem (CPU/RAM/Ağ)" : "Hardware & System" ?>
		</button>
		<button type="button" class="button button-secondary button-small rrd-cat-btn" onclick="filterRrdCategory('web', this);" style="font-size: 12px;">
			<i class="fas fa-earth-americas"></i> <?= $is_tr ? "Web & Nginx Trafiği" : "Web & NGINX Traffic" ?>
		</button>
		<button type="button" class="button button-secondary button-small rrd-cat-btn" onclick="filterRrdCategory('db', this);" style="font-size: 12px;">
			<i class="fas fa-database"></i> <?= $is_tr ? "Veritabanı (MySQL/PostgreSQL)" : "Databases" ?>
		</button>
		<button type="button" class="button button-secondary button-small rrd-cat-btn" onclick="filterRrdCategory('services', this);" style="font-size: 12px;">
			<i class="fas fa-sliders"></i> <?= $is_tr ? "Mail & Servisler" : "Mail & Other Services" ?>
		</button>
	</div>

	<!-- Graph Cards Grid Layout -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(620px, 1fr)); gap: 20px;">
		<?php foreach ($data as $key => $value) { 
			$type = $data[$key]["TYPE"] ?? "";
			$rrd_key = $data[$key]["RRD"] ?? "";
			$meta = $rrd_meta[$type] ?? ($rrd_meta[$rrd_key] ?? [
				"cat" => "services",
				"icon" => "fa-chart-line",
				"badge" => $data[$key]["TITLE"] ?? "Sistem Grafiği",
				"desc" => $is_tr ? "Bu servis için zaman içindeki kaynak tüketim ve istek metriklerini gösterir." : "Monitors historic resource usage and throughput metrics.",
				"tip" => $is_tr ? "Detaylı inceleme için üstteki zaman dilimlerini (Haftalık/Aylık) kullanabilirsiniz." : "Use period selector above to inspect weekly and monthly patterns."
			]);
		?>
			<div class="rrd-graph-card" data-category="<?= htmlspecialchars($meta["cat"]) ?>" style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex; flex-direction: column;">
				
				<!-- Card Header -->
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid var(--border-color, #334155); padding-bottom: 10px;">
					<div style="display: flex; align-items: center; gap: 10px;">
						<div style="width: 32px; height: 32px; border-radius: 6px; background: rgba(255,255,255,0.06); display: flex; align-items: center; justify-content: center;">
							<i class="fas <?= $meta["icon"] ?> icon-blue" style="font-size: 15px;"></i>
						</div>
						<div>
							<h3 style="margin: 0; font-size: 1.05rem; font-weight: bold; color: var(--color-text, #f8fafc);">
								<?= tohtml($data[$key]["TITLE"]) ?>
							</h3>
							<small style="font-size: 11px; color: var(--color-text-muted, #94a3b8); font-weight: 500;">
								<?= tohtml($meta["badge"]) ?>
							</small>
						</div>
					</div>
					<span class="badge badge-info" style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; padding: 3px 8px;">
						<?= htmlspecialchars($period) ?>
					</span>
				</div>

				<!-- Smart Insight / Meaning Box -->
				<div style="background: rgba(0,0,0,0.15); border-left: 3px solid var(--icon-color-blue, #38bdf8); border-radius: 0 4px 4px 0; padding: 8px 12px; margin-bottom: 15px; font-size: 11.5px; line-height: 1.45; color: var(--color-text-muted, #cbd5e1);">
					<div><?= tohtml($meta["desc"]) ?></div>
					<div style="margin-top: 4px; color: var(--icon-color-green, #4ade80); font-weight: 500;">
						<i class="fas fa-lightbulb" style="font-size: 10px; margin-right: 3px;"></i> <?= tohtml($meta["tip"]) ?>
					</div>
				</div>

				<!-- Chart Canvas -->
				<div style="flex: 1; min-height: 240px; position: relative;">
					<canvas
						class="u-max-height300 js-rrd-chart"
						data-service="<?= tohtml($data[$key]["TYPE"] !== "net" ? $data[$key]["RRD"] : "net_" . $data[$key]["RRD"]) ?>"
						data-period="<?= tohtml($period) ?>"
						style="width: 100%; height: 100%;"
					></canvas>
				</div>

			</div>
		<?php } ?>
	</div>

</div>

<script>
function filterRrdCategory(category, btn) {
	document.querySelectorAll('.rrd-cat-btn').forEach(b => {
		b.className = 'button button-secondary button-small rrd-cat-btn';
	});
	btn.className = 'button button-primary button-small rrd-cat-btn active';

	const cards = document.querySelectorAll('.rrd-graph-card');
	cards.forEach(card => {
		const cat = card.getAttribute('data-category');
		if (category === 'all' || cat === category) {
			card.style.display = 'flex';
		} else {
			card.style.display = 'none';
		}
	});
}
</script>
