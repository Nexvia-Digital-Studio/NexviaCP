<?php
// Bilingual helper (defined in web/inc/main.php; guarded so the template
// also renders standalone without a fatal redefinition error)
if (!function_exists("__tr")) {
	function __tr(string $en, string $tr): string {
		$lang = $_SESSION["language"] ?? $_SESSION["LANGUAGE"] ?? "en";
		return ($lang === "tr") ? $tr : $en;
	}
}

$hb = $heartbeat_status ?? [];
$hb_state = $hb["state"] ?? [];
$certs = $certs_data["certs"] ?? [];
$cert_summary = $certs_data["summary"] ?? [];

$severity_meta = [
	"ok" => ["color" => "#22c55e", "icon" => "fas fa-circle-check", "label" => __tr("OK", "OK")],
	"warn" => ["color" => "#eab308", "icon" => "fas fa-triangle-exclamation", "label" => __tr("WARN", "UYARI")],
	"critical" => ["color" => "#f97316", "icon" => "fas fa-fire", "label" => __tr("CRITICAL", "KRİTİK")],
	"expired" => ["color" => "#ef4444", "icon" => "fas fa-circle-xmark", "label" => __tr("EXPIRED", "SÜRESİ DOLDU")],
	"no-cert" => ["color" => "#94a3b8", "icon" => "fas fa-circle-question", "label" => __tr("NO CERT", "SERTİFİKA YOK")],
];

$hb_configured = !empty($hb["configured"]);
$last_status = (string)($hb_state["last_status"] ?? "");
$fail_count = (int)($hb_state["fail_count"] ?? 0);
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/server/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(__tr("Back", "Geri")) ?>
			</a>
			<a href="/list/notify/" class="button button-secondary">
				<i class="fas fa-bell icon-green"></i> <?= tohtml(__tr("Notification Channels", "Bildirim Kanalları")) ?>
			</a>
		</div>
		<div class="toolbar-right">
			<span style="font-size: 12px; color: var(--color-text-muted, #94a3b8);">
				<i class="fas fa-heart-pulse"></i> <?= tohtml(__tr("External watchdog & certificate expiry tracking", "Dış izleyici ve sertifika bitiş takibi")) ?>
			</span>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container" style="max-width: 1400px; padding: 15px 20px;">

	<!-- Heartbeat status card -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
		<h3 style="margin: 0 0 15px; font-size: 1rem; color: var(--color-text, #f8fafc);">
			<i class="fas fa-heart-pulse icon-red"></i> <?= tohtml(__tr("Heartbeat Watchdog Status", "Heartbeat İzleyici Durumu")) ?>
		</h3>
		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
			<div>
				<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
					<?= tohtml(__tr("Configured", "Yapılandırılmış")) ?>
				</span>
				<div style="margin-top: 5px;">
					<?php if ($hb_configured): ?>
						<span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; background: rgba(34,197,94,0.15); color: #22c55e; border: 1px solid rgba(34,197,94,0.3);">
							<i class="fas fa-circle-check" style="font-size: 9px;"></i> <?= tohtml(__tr("YES", "EVET")) ?>
						</span>
					<?php else: ?>
						<span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; background: rgba(148,163,184,0.15); color: #94a3b8; border: 1px solid rgba(148,163,184,0.3);">
							<i class="fas fa-circle-xmark" style="font-size: 9px;"></i> <?= tohtml(__tr("NO", "HAYIR")) ?>
						</span>
					<?php endif; ?>
				</div>
			</div>
			<div>
				<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
					<?= tohtml(__tr("Ping URL", "Ping URL")) ?>
				</span>
				<div style="margin-top: 5px; font-family: monospace; font-size: 12px; color: var(--color-text, #f8fafc); word-break: break-all;">
					<?= $hb_configured ? htmlspecialchars((string)($hb["url"] ?? "")) : tohtml(__tr("not configured", "yapılandırılmadı")) ?>
				</div>
			</div>
			<div>
				<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
					<?= tohtml(__tr("Interval", "Aralık")) ?>
				</span>
				<div style="margin-top: 5px; font-size: 13px; color: var(--color-text, #f8fafc);">
					<?= $hb_configured ? tohtml(__tr("every", "her")) . " " . (int)($hb["interval"] ?? 5) . " " . tohtml(__tr("min", "dk")) : "-" ?>
				</div>
			</div>
			<div>
				<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
					<?= tohtml(__tr("Cron Entry", "Cron Kaydı")) ?>
				</span>
				<div style="margin-top: 5px; font-size: 13px;">
					<?php if (!empty($hb["cron_present"])): ?>
						<span style="color: #22c55e;"><i class="fas fa-circle-check"></i> <?= tohtml(__tr("installed", "kurulu")) ?></span>
					<?php else: ?>
						<span style="color: #94a3b8;"><i class="fas fa-circle-xmark"></i> <?= tohtml(__tr("not installed", "kurulu değil")) ?></span>
					<?php endif; ?>
				</div>
			</div>
			<div>
				<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
					<?= tohtml(__tr("Last Ping", "Son Ping")) ?>
				</span>
				<div style="margin-top: 5px; font-family: monospace; font-size: 12px; color: var(--color-text, #f8fafc);">
					<?= htmlspecialchars((string)($hb_state["last_ping"] ?? "")) ?: "-" ?>
					<?php if ($last_status !== ""): ?>
						<?php if ($last_status === "ok"): ?>
							<span style="color: #22c55e;"><i class="fas fa-circle-check"></i> <?= tohtml(__tr("ok", "başarılı")) ?></span>
						<?php else: ?>
							<span style="color: #ef4444;"><i class="fas fa-circle-xmark"></i> <?= tohtml(__tr("fail", "başarısız")) ?></span>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
			<div>
				<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
					<?= tohtml(__tr("Consecutive Failures", "Ardışık Hata")) ?>
				</span>
				<div style="margin-top: 5px; font-size: 13px; font-weight: bold; color: <?= $fail_count >= 12 ? "#ef4444" : ($fail_count > 0 ? "#eab308" : "#22c55e") ?>;">
					<?= $fail_count ?> / 12
				</div>
			</div>
		</div>
	</div>

	<!-- Heartbeat configuration form -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 20px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
		<h3 style="margin: 0 0 15px; font-size: 1rem; color: var(--color-text, #f8fafc);">
			<i class="fas fa-satellite-dish icon-blue"></i> <?= tohtml(__tr("Heartbeat Configuration", "Heartbeat Yapılandırması")) ?>
		</h3>
		<form method="post" action="/list/health/">
			<input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION["token"] ?? "") ?>">
			<input type="hidden" name="action" value="save_heartbeat">
			<div style="display: grid; grid-template-columns: 1fr 160px auto auto; gap: 12px; align-items: end;">
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= tohtml(__tr("Health Check URL (https:// only)", "Health Check URL (sadece https://)")) ?>
					</label>
					<input type="url" class="form-control" name="v_url" required maxlength="2048"
						pattern="https://[a-zA-Z0-9._~:/-]+"
						placeholder="https://hc-ping.com/your-uuid"
						value="<?= $hb_configured ? htmlspecialchars((string)($hb["url"] ?? "")) : "" ?>"
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= tohtml(__tr("Interval (min)", "Aralık (dk)")) ?>
					</label>
					<input type="number" class="form-control" name="v_minutes" min="1" max="1440" step="1"
						value="<?= (int)($hb["interval"] ?? 5) ?: 5 ?>"
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
				</div>
				<div>
					<button type="submit" class="button button-primary" style="white-space: nowrap;">
						<i class="fas fa-floppy-disk"></i> <?= tohtml(__tr("Save", "Kaydet")) ?>
					</button>
				</div>
			</div>
			<p style="font-size: 11px; color: var(--color-text-muted, #94a3b8); margin: 10px 0 0;">
				<?= tohtml(__tr(
					"healthchecks.io style ping URL. Only the strict https://host[:port]/path form is accepted (no query string). The interval must be 1-1440 minutes. The cron entry is installed automatically at /etc/cron.d/nexvia-heartbeat.",
					"healthchecks.io tarzı ping adresi. Sadece katı https://host[:port]/path biçimi kabul edilir (sorgu dizesi yok). Aralık 1-1440 dakika olmalıdır. Cron kaydı /etc/cron.d/nexvia-heartbeat dosyasına otomatik kurulur."
				)) ?>
			</p>
		</form>
		<?php if ($hb_configured): ?>
		<form method="post" action="/list/health/" style="margin-top: 12px;"
			onsubmit="return confirm('<?= tohtml(__tr("Remove the heartbeat watchdog (config, cron entry and state)?", "Heartbeat izleyici kaldırılsın mı (konfigürasyon, cron ve durum)?")) ?>');">
			<input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION["token"] ?? "") ?>">
			<input type="hidden" name="action" value="delete_heartbeat">
			<button type="submit" class="button button-secondary" style="color: #ef4444;">
				<i class="fas fa-trash"></i> <?= tohtml(__tr("Delete Heartbeat", "Heartbeat Sil")) ?>
			</button>
		</form>
		<?php endif; ?>
	</div>

	<!-- Certificate summary cards -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 15px; margin-bottom: 20px;">
		<?php
		$summary_cards = [
			["key" => "total", "label" => __tr("Total Certificates", "Toplam Sertifika"), "color" => "#38bdf8", "icon" => "fas fa-file-shield"],
			["key" => "ok", "label" => __tr("OK (>30 days)", "OK (>30 gün)"), "color" => "#22c55e", "icon" => "fas fa-circle-check"],
			["key" => "warn", "label" => __tr("Warning (≤30d)", "Uyarı (≤30g)"), "color" => "#eab308", "icon" => "fas fa-triangle-exclamation"],
			["key" => "critical", "label" => __tr("Critical (≤7d)", "Kritik (≤7g)"), "color" => "#f97316", "icon" => "fas fa-fire"],
			["key" => "expired", "label" => __tr("Expired", "Süresi Doldu"), "color" => "#ef4444", "icon" => "fas fa-circle-xmark"],
			["key" => "no_cert", "label" => __tr("No Cert", "Sertifika Yok"), "color" => "#94a3b8", "icon" => "fas fa-circle-question"],
		];
		foreach ($summary_cards as $card):
			$cnt = (int)($cert_summary[$card["key"]] ?? 0);
		?>
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= tohtml($card["label"]) ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: var(--color-text, #f8fafc); <?= in_array($card["key"], ["critical", "expired"], true) && $cnt > 0 ? "color: " . $card["color"] . ";" : "" ?>">
						<?= $cnt ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: <?= $card["color"] ?>22; display: flex; align-items: center; justify-content: center;">
					<i class="<?= $card["icon"] ?>" style="font-size: 16px; color: <?= $card["color"] ?>;"></i>
				</div>
			</div>
		</div>
		<?php endforeach; ?>
	</div>

	<!-- Certificate table -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; overflow: hidden;">
		<div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color, #334155); display: flex; justify-content: space-between; align-items: center;">
			<h3 style="margin: 0; font-size: 1rem; color: var(--color-text, #f8fafc);">
				<i class="fas fa-file-shield icon-blue"></i> <?= tohtml(__tr("SSL Certificates", "SSL Sertifikaları")) ?>
			</h3>
			<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
				<?= count($certs) ?> <?= tohtml(__tr("entries (web domains + panel)", "kayıt (web domainleri + panel)")) ?>
			</span>
		</div>

		<?php if (empty($certs)): ?>
		<div style="padding: 40px; text-align: center;">
			<i class="fas fa-file-shield" style="font-size: 48px; color: var(--color-text-muted, #94a3b8); margin-bottom: 15px;"></i>
			<h3 style="color: var(--color-text, #f8fafc); margin: 0 0 8px;">
				<?= tohtml(__tr("No Certificates Found", "Sertifika Bulunamadı")) ?>
			</h3>
			<p style="color: var(--color-text-muted, #94a3b8); font-size: 13px; margin: 0;">
				<?= tohtml(__tr("No web domains or panel certificate are present on this server yet.", "Bu sunucuda henüz web domaini veya panel sertifikası yok.")) ?>
			</p>
		</div>
		<?php else: ?>
		<div style="overflow-x: auto;">
			<table style="width: 100%; border-collapse: collapse; font-size: 13px;">
				<thead>
					<tr style="background: rgba(0,0,0,0.2);">
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= tohtml(__tr("Domain", "Domain")) ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= tohtml(__tr("Type", "Tip")) ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= tohtml(__tr("Expires", "Bitiş")) ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= tohtml(__tr("Days Left", "Kalan Gün")) ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= tohtml(__tr("Status", "Durum")) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($certs as $cert):
						$sev_key = (string)($cert["severity"] ?? "no-cert");
						$meta = $severity_meta[$sev_key] ?? $severity_meta["no-cert"];
						$days = $cert["days_left"];
					?>
					<tr style="border-bottom: 1px solid var(--border-color, #334155);">
						<td style="padding: 10px 15px; color: var(--color-text, #f8fafc); font-weight: 600; white-space: nowrap;">
							<?= htmlspecialchars((string)($cert["domain"] ?? "")) ?>
							<span style="color: var(--color-text-muted, #94a3b8); font-weight: 400; font-size: 11px;">(<?= htmlspecialchars((string)($cert["user"] ?? "")) ?>)</span>
						</td>
						<td style="padding: 10px 15px; white-space: nowrap;">
							<span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: rgba(56,189,248,0.15); color: #38bdf8; border: 1px solid rgba(56,189,248,0.3);">
								<i class="<?= ($cert["type"] ?? "") === "panel" ? "fas fa-server" : "fas fa-globe" ?>" style="font-size: 9px;"></i>
								<?= htmlspecialchars((string)($cert["type"] ?? "web")) ?>
							</span>
						</td>
						<td style="padding: 10px 15px; color: var(--color-text-muted, #cbd5e1); font-family: monospace; font-size: 12px; white-space: nowrap;">
							<?= htmlspecialchars((string)($cert["not_after"] ?? "")) ?: "-" ?>
						</td>
						<td style="padding: 10px 15px; font-family: monospace; font-size: 12px; white-space: nowrap; color: <?= $meta["color"] ?>; font-weight: 600;">
							<?= $days !== null ? (int)$days : "-" ?>
						</td>
						<td style="padding: 10px 15px; white-space: nowrap;">
							<span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: <?= $meta["color"] ?>22; color: <?= $meta["color"] ?>; border: 1px solid <?= $meta["color"] ?>44;">
								<i class="<?= $meta["icon"] ?>" style="font-size: 9px;"></i>
								<?= tohtml($meta["label"]) ?>
							</span>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
	</div>

</div>
