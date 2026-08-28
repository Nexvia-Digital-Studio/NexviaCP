<?php
// API audit page: rate limit status + login/auth and API usage logs.
// All dynamic output is escaped with htmlspecialchars(..., ENT_QUOTES).

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
$stats = $api_stats ?? [];

$v_limit_value = (string) ($stats["limit"] ?? "120");
$v_window_value = (string) ($stats["window_seconds"] ?? "60");
$v_active_keys = (int) ($stats["active_keys"] ?? 0);
$v_total_requests = (int) ($stats["total_requests"] ?? 0);
$v_limit_source = (string) ($stats["limit_source"] ?? "default");
$v_lines_value = (string) ($audit_lines ?? 100);
$v_filter_value = (string) ($_GET["filter"] ?? "");
$v_status_value = (string) ($audit_status ?? "all");
$v_activity_filter_value = (string) ($_GET["activity_filter"] ?? "");
$v_category_value = (string) ($activity_category ?? "all");

$status_meta = [
	"failed" => ["label" => "failed", "color" => "#ef4444", "icon" => "fas fa-circle-xmark"],
	"success" => ["label" => "success", "color" => "#22c55e", "icon" => "fas fa-circle-check"],
	"info" => ["label" => "info", "color" => "#38bdf8", "icon" => "fas fa-circle-info"],
];

$level_meta = [
	"error" => ["color" => "#ef4444"],
	"warning" => ["color" => "#eab308"],
	"info" => ["color" => "#38bdf8"],
];
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/api/">
				<i class="fas fa-arrow-left icon-blue"></i><?= htmlspecialchars(__tr("Back", "Geri"), ENT_QUOTES) ?>
			</a>
			<a href="/list/log/?<?= htmlspecialchars(http_build_query(["user" => "system", "token" => $_SESSION["token"] ?? ""]), ENT_QUOTES) ?>" class="button button-secondary">
				<i class="fas fa-list-check icon-green"></i> <?= htmlspecialchars(__tr("Full System Log", "Tam Sistem Günlüğü"), ENT_QUOTES) ?>
			</a>
		</div>
		<div class="toolbar-right">
			<a href="javascript:location.reload();" class="button button-secondary">
				<i class="fas fa-arrow-rotate-right icon-green"></i><?= htmlspecialchars(__tr("Refresh", "Yenile"), ENT_QUOTES) ?>
			</a>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container" style="max-width: 1400px; padding: 15px 20px;">

	<!-- Rate Limit Status Cards -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">

		<!-- Active Limit -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= htmlspecialchars(__tr("Active Limit", "Aktif Limit"), ENT_QUOTES) ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= htmlspecialchars($v_limit_value, ENT_QUOTES) ?>
						<span style="font-size: 11px; font-weight: 600; color: <?= $v_limit_source === "file" ? "#22c55e" : "#eab308" ?>;">
							(<?= $v_limit_source === "file"
								? htmlspecialchars(__tr("configured", "yapılandırılmış"), ENT_QUOTES)
								: htmlspecialchars(__tr("default", "varsayılan"), ENT_QUOTES) ?>)
						</span>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(56,189,248,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-gauge-high" style="font-size: 16px; color: #38bdf8;"></i>
				</div>
			</div>
		</div>

		<!-- Window -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= htmlspecialchars(__tr("Window", "Pencere"), ENT_QUOTES) ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= htmlspecialchars($v_window_value, ENT_QUOTES) ?><?= htmlspecialchars(__tr("s", "sn"), ENT_QUOTES) ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(129,140,248,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-hourglass-half" style="font-size: 16px; color: #818cf8;"></i>
				</div>
			</div>
		</div>

		<!-- Active Keys -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= htmlspecialchars(__tr("Active Keys", "Aktif Anahtar"), ENT_QUOTES) ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= $v_active_keys ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(34,197,94,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-key" style="font-size: 16px; color: #22c55e;"></i>
				</div>
			</div>
		</div>

		<!-- Requests in Window -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= htmlspecialchars(__tr("Requests in Window", "Pencere İçi İstek"), ENT_QUOTES) ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= $v_total_requests ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(234,179,8,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-bolt" style="font-size: 16px; color: #eab308;"></i>
				</div>
			</div>
		</div>

	</div>

	<!-- Set Limit Form -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
		<h3 style="margin: 0 0 15px; font-size: 1rem; color: var(--color-text, #f8fafc);">
			<i class="fas fa-sliders icon-blue"></i> <?= htmlspecialchars(__tr("API Rate Limit", "API Hız Limiti"), ENT_QUOTES) ?>
		</h3>
		<form method="post" action="/list/api-audit/">
			<input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION["token"] ?? "", ENT_QUOTES) ?>">
			<input type="hidden" name="action" value="set_limit">
			<div style="display: flex; gap: 12px; align-items: end; flex-wrap: wrap;">
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= htmlspecialchars(__tr("Requests per 60s window (1-100000)", "60 saniyelik pencere başına istek (1-100000)"), ENT_QUOTES) ?>
					</label>
					<input type="number" class="form-control" name="v_limit" required
						min="1" max="100000" step="1"
						value="<?= htmlspecialchars($v_limit_value, ENT_QUOTES) ?>"
						style="width: 260px; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
				</div>
				<div>
					<button type="submit" class="button button-primary" style="white-space: nowrap;">
						<i class="fas fa-floppy-disk"></i> <?= htmlspecialchars(__tr("Save", "Kaydet"), ENT_QUOTES) ?>
					</button>
				</div>
			</div>
			<p style="font-size: 11px; color: var(--color-text-muted, #94a3b8); margin: 10px 0 0;">
				<?= htmlspecialchars(__tr(
					"Applies per API key to authenticated requests only. Rejected requests receive HTTP 429 with a Retry-After header. The limiter fails open if the counter storage is unavailable.",
					"Anahtar başına, yalnızca kimlik doğrulaması yapılmış isteklere uygulanır. Reddedilen istekler Retry-After başlığıyla HTTP 429 alır. Sayaç deposu kullanılamazsa limiter geçişe izin verir (fail-open)."
				), ENT_QUOTES) ?>
			</p>
		</form>
	</div>

	<!-- Auth & API Log -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; overflow: hidden; margin-bottom: 20px;">
		<div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color, #334155);">
			<h3 style="margin: 0 0 12px; font-size: 1rem; color: var(--color-text, #f8fafc);">
				<i class="fas fa-user-shield icon-green"></i>
				<?= htmlspecialchars(__tr("Login / Auth & API Log", "Login / Kimlik Doğrulama ve API Günlüğü"), ENT_QUOTES) ?>
				<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8); font-weight: 400;">(auth.log)</span>
			</h3>
			<form method="get" action="/list/api-audit/" style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
				<input type="hidden" name="activity_filter" value="<?= htmlspecialchars($v_activity_filter_value, ENT_QUOTES) ?>">
				<input type="hidden" name="category" value="<?= htmlspecialchars($v_category_value, ENT_QUOTES) ?>">
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= htmlspecialchars(__tr("Filter", "Filtre"), ENT_QUOTES) ?>
					</label>
					<input type="text" name="filter" value="<?= htmlspecialchars($v_filter_value, ENT_QUOTES) ?>"
						placeholder="<?= htmlspecialchars(__tr("ip, user, command...", "ip, kullanıcı, komut..."), ENT_QUOTES) ?>"
						style="padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px; width: 220px;">
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= htmlspecialchars(__tr("Status", "Durum"), ENT_QUOTES) ?>
					</label>
					<select name="status"
						style="padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
						<option value="all" <?= $v_status_value === "all" ? "selected" : "" ?>><?= htmlspecialchars(__tr("All", "Tümü"), ENT_QUOTES) ?></option>
						<option value="success" <?= $v_status_value === "success" ? "selected" : "" ?>><?= htmlspecialchars(__tr("Success", "Başarılı"), ENT_QUOTES) ?></option>
						<option value="failed" <?= $v_status_value === "failed" ? "selected" : "" ?>><?= htmlspecialchars(__tr("Failed", "Başarısız"), ENT_QUOTES) ?></option>
					</select>
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= htmlspecialchars(__tr("Lines", "Satır"), ENT_QUOTES) ?>
					</label>
					<select name="lines"
						style="padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
						<?php foreach ([50, 100, 200, 500] as $n): ?>
						<option value="<?= $n ?>" <?= $v_lines_value === (string) $n ? "selected" : "" ?>><?= $n ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div>
					<button type="submit" class="button button-secondary">
						<i class="fas fa-magnifying-glass"></i> <?= htmlspecialchars(__tr("Apply", "Uygula"), ENT_QUOTES) ?>
					</button>
				</div>
			</form>
		</div>

		<?php if (empty($auth_log_rows)): ?>
		<div style="padding: 30px; text-align: center;">
			<p style="color: var(--color-text-muted, #94a3b8); font-size: 13px; margin: 0;">
				<?= htmlspecialchars(__tr("No matching log entries.", "Eşleşen kayıt yok."), ENT_QUOTES) ?>
			</p>
		</div>
		<?php else: ?>
		<div style="overflow-x: auto; max-height: 480px; overflow-y: auto;">
			<table style="width: 100%; border-collapse: collapse; font-size: 13px;">
				<thead>
					<tr style="background: rgba(0,0,0,0.2);">
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= htmlspecialchars(__tr("Date", "Tarih"), ENT_QUOTES) ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= htmlspecialchars(__tr("Time", "Saat"), ENT_QUOTES) ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= htmlspecialchars(__tr("Status", "Durum"), ENT_QUOTES) ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= htmlspecialchars(__tr("Event", "Olay"), ENT_QUOTES) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($auth_log_rows as $row):
						$meta = $status_meta[$row["STATUS"]] ?? $status_meta["info"]; ?>
					<tr style="border-bottom: 1px solid var(--border-color, #334155);">
						<td style="padding: 8px 15px; color: var(--color-text-muted, #cbd5e1); white-space: nowrap; font-family: monospace; font-size: 12px;"><?= htmlspecialchars($row["DATE"], ENT_QUOTES) ?></td>
						<td style="padding: 8px 15px; color: var(--color-text-muted, #cbd5e1); white-space: nowrap; font-family: monospace; font-size: 12px;"><?= htmlspecialchars($row["TIME"], ENT_QUOTES) ?></td>
						<td style="padding: 8px 15px; white-space: nowrap;">
							<span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: <?= $meta["color"] ?>22; color: <?= $meta["color"] ?>; border: 1px solid <?= $meta["color"] ?>44;">
								<i class="<?= $meta["icon"] ?>" style="font-size: 9px;"></i>
								<?= htmlspecialchars($meta["label"], ENT_QUOTES) ?>
							</span>
						</td>
						<td style="padding: 8px 15px; color: var(--color-text, #f8fafc); font-family: monospace; font-size: 12px; word-break: break-all;"><?= htmlspecialchars($row["MESSAGE"], ENT_QUOTES) ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
	</div>

	<!-- System Activity -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; overflow: hidden;">
		<div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color, #334155);">
			<h3 style="margin: 0 0 12px; font-size: 1rem; color: var(--color-text, #f8fafc);">
				<i class="fas fa-clipboard-list icon-purple"></i>
				<?= htmlspecialchars(__tr("System Activity (API usage)", "Sistem Etkinliği (API kullanımı)"), ENT_QUOTES) ?>
				<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8); font-weight: 400;">(activity.log)</span>
			</h3>
			<form method="get" action="/list/api-audit/" style="display: flex; gap: 10px; align-items: end; flex-wrap: wrap;">
				<input type="hidden" name="filter" value="<?= htmlspecialchars($v_filter_value, ENT_QUOTES) ?>">
				<input type="hidden" name="status" value="<?= htmlspecialchars($v_status_value, ENT_QUOTES) ?>">
				<input type="hidden" name="lines" value="<?= htmlspecialchars($v_lines_value, ENT_QUOTES) ?>">
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= htmlspecialchars(__tr("Filter", "Filtre"), ENT_QUOTES) ?>
					</label>
					<input type="text" name="activity_filter" value="<?= htmlspecialchars($v_activity_filter_value, ENT_QUOTES) ?>"
						placeholder="<?= htmlspecialchars(__tr("message...", "mesaj..."), ENT_QUOTES) ?>"
						style="padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px; width: 220px;">
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= htmlspecialchars(__tr("Category", "Kategori"), ENT_QUOTES) ?>
					</label>
					<select name="category"
						style="padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
						<option value="all" <?= $v_category_value === "all" ? "selected" : "" ?>><?= htmlspecialchars(__tr("All", "Tümü"), ENT_QUOTES) ?></option>
						<option value="api" <?= $v_category_value === "api" ? "selected" : "" ?>>API</option>
						<option value="system" <?= $v_category_value === "system" ? "selected" : "" ?>><?= htmlspecialchars(__tr("System", "Sistem"), ENT_QUOTES) ?></option>
						<option value="auth" <?= $v_category_value === "auth" ? "selected" : "" ?>>Auth</option>
						<option value="security" <?= $v_category_value === "security" ? "selected" : "" ?>><?= htmlspecialchars(__tr("Security", "Güvenlik"), ENT_QUOTES) ?></option>
						<option value="backup" <?= $v_category_value === "backup" ? "selected" : "" ?>>Backup</option>
						<option value="updates" <?= $v_category_value === "updates" ? "selected" : "" ?>><?= htmlspecialchars(__tr("Updates", "Güncellemeler"), ENT_QUOTES) ?></option>
					</select>
				</div>
				<div>
					<button type="submit" class="button button-secondary">
						<i class="fas fa-magnifying-glass"></i> <?= htmlspecialchars(__tr("Apply", "Uygula"), ENT_QUOTES) ?>
					</button>
				</div>
			</form>
		</div>

		<?php if (empty($activity_rows)): ?>
		<div style="padding: 30px; text-align: center;">
			<p style="color: var(--color-text-muted, #94a3b8); font-size: 13px; margin: 0;">
				<?= htmlspecialchars(__tr("No matching activity entries.", "Eşleşen etkinlik kaydı yok."), ENT_QUOTES) ?>
			</p>
		</div>
		<?php else: ?>
		<div style="overflow-x: auto; max-height: 480px; overflow-y: auto;">
			<table style="width: 100%; border-collapse: collapse; font-size: 13px;">
				<thead>
					<tr style="background: rgba(0,0,0,0.2);">
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= htmlspecialchars(__tr("Date", "Tarih"), ENT_QUOTES) ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= htmlspecialchars(__tr("Time", "Saat"), ENT_QUOTES) ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= htmlspecialchars(__tr("Level", "Seviye"), ENT_QUOTES) ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= htmlspecialchars(__tr("Category", "Kategori"), ENT_QUOTES) ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= htmlspecialchars(__tr("Message", "Mesaj"), ENT_QUOTES) ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($activity_rows as $row):
						$level_key = strtolower($row["LEVEL"]);
						$level_color = ($level_meta[$level_key]["color"] ?? "#94a3b8"); ?>
					<tr style="border-bottom: 1px solid var(--border-color, #334155);">
						<td style="padding: 8px 15px; color: var(--color-text-muted, #cbd5e1); white-space: nowrap; font-family: monospace; font-size: 12px;"><?= htmlspecialchars($row["DATE"], ENT_QUOTES) ?></td>
						<td style="padding: 8px 15px; color: var(--color-text-muted, #cbd5e1); white-space: nowrap; font-family: monospace; font-size: 12px;"><?= htmlspecialchars($row["TIME"], ENT_QUOTES) ?></td>
						<td style="padding: 8px 15px; white-space: nowrap;">
							<span style="display: inline-flex; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: <?= $level_color ?>22; color: <?= $level_color ?>; border: 1px solid <?= $level_color ?>44;">
								<?= htmlspecialchars($row["LEVEL"] !== "" ? $row["LEVEL"] : "-", ENT_QUOTES) ?>
							</span>
						</td>
						<td style="padding: 8px 15px; color: var(--color-text, #f8fafc); white-space: nowrap;"><?= htmlspecialchars($row["CATEGORY"] !== "" ? $row["CATEGORY"] : "-", ENT_QUOTES) ?></td>
						<td style="padding: 8px 15px; color: var(--color-text-muted, #cbd5e1); font-family: monospace; font-size: 12px; word-break: break-all;"><?= htmlspecialchars($row["MESSAGE"], ENT_QUOTES) ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
	</div>

</div>
