<?php
$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
$channels = $notify_channels ?? [];

$type_meta = [
	"telegram" => ["icon" => "fas fa-paper-plane", "color" => "#38bdf8"],
	"discord" => ["icon" => "fas fa-comments", "color" => "#818cf8"],
	"slack" => ["icon" => "fas fa-hashtag", "color" => "#eab308"],
	"webhook" => ["icon" => "fas fa-plug", "color" => "#22c55e"],
];

$type_counts = ["telegram" => 0, "discord" => 0, "slack" => 0, "webhook" => 0];
foreach ($channels as $ch) {
	$t = $ch["TYPE"] ?? "";
	if (isset($type_counts[$t])) {
		$type_counts[$t]++;
	}
}
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/ai-healing/">
				<i class="fas fa-arrow-left icon-blue"></i><?= $is_tr ? "Geri" : "Back" ?>
			</a>
			<a href="/list/anomalies/" class="button button-secondary">
				<i class="fas fa-triangle-exclamation icon-purple"></i> <?= $is_tr ? "Anomali İzleme" : "Anomaly Monitor" ?>
			</a>
		</div>
		<div class="toolbar-right">
			<span style="font-size: 12px; color: var(--color-text-muted, #94a3b8);">
				<i class="fas fa-bell"></i> <?= $is_tr ? "Sistem uyarıları e-postanın yanında bu kanallara da gönderilir" : "System alerts are mirrored to these channels in addition to email" ?>
			</span>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container" style="max-width: 1400px; padding: 15px 20px;">

	<!-- Summary Cards -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">

		<!-- Total Channels -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= $is_tr ? "Toplam Kanal" : "Total Channels" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= count($channels) ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(56,189,248,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-bell" style="font-size: 16px; color: #38bdf8;"></i>
				</div>
			</div>
		</div>

		<?php foreach ($type_counts as $t => $cnt): ?>
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= htmlspecialchars(ucfirst($t)) ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= (int)$cnt ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: <?= $type_meta[$t]["color"] ?>22; display: flex; align-items: center; justify-content: center;">
					<i class="<?= $type_meta[$t]["icon"] ?>" style="font-size: 16px; color: <?= $type_meta[$t]["color"] ?>;"></i>
				</div>
			</div>
		</div>
		<?php endforeach; ?>

	</div>

	<!-- Add Channel Form -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
		<h3 style="margin: 0 0 15px; font-size: 1rem; color: var(--color-text, #f8fafc);">
			<i class="fas fa-circle-plus icon-green"></i> <?= $is_tr ? "Yeni Bildirim Kanalı Ekle" : "Add New Notification Channel" ?>
		</h3>
		<form method="post" action="/list/notify/">
			<input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION["token"] ?? "") ?>">
			<input type="hidden" name="action" value="add_channel">
			<div style="display: grid; grid-template-columns: 180px 160px 1fr 180px auto; gap: 12px; align-items: end;">
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= $is_tr ? "Ad" : "Name" ?>
					</label>
					<input type="text" class="form-control" name="v_name" required maxlength="64"
						pattern="[a-zA-Z0-9_-]+"
						placeholder="ops-telegram"
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= $is_tr ? "Tip" : "Type" ?>
					</label>
					<select class="form-control" name="v_type" required
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
						<option value="telegram">Telegram</option>
						<option value="discord">Discord</option>
						<option value="slack">Slack</option>
						<option value="webhook">Webhook</option>
					</select>
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= $is_tr ? "Hedef (token@chat_id veya https:// URL)" : "Target (token@chat_id or https:// URL)" ?>
					</label>
					<input type="text" class="form-control" name="v_target" required maxlength="2048"
						placeholder="110201543:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw@123456789"
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= $is_tr ? "Olaylar" : "Events" ?>
					</label>
					<input type="text" class="form-control" name="v_events" maxlength="128"
						placeholder="all"
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
				</div>
				<div>
					<button type="submit" class="button button-primary" style="white-space: nowrap;">
						<i class="fas fa-floppy-disk"></i> <?= $is_tr ? "Kaydet" : "Save" ?>
					</button>
				</div>
			</div>
			<p style="font-size: 11px; color: var(--color-text-muted, #94a3b8); margin: 10px 0 0;">
				<?= $is_tr
					? "Telegram için hedef biçimi: bot_token@chat_id. Diğer tipler için https:// ile başlayan webhook adresi. Aynı ad kullanılırsa kanal güncellenir. Hedefler sunucuda izinli (chmod 600) olarak saklanır ve listede maskelenir."
					: "Telegram target format: bot_token@chat_id. Other types expect an https:// webhook URL. Re-using a name updates the channel. Targets are stored with chmod 600 and always displayed masked." ?>
			</p>
		</form>
	</div>

	<?php if (empty($channels)): ?>
	<!-- Empty State -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 40px; text-align: center;">
		<i class="fas fa-bell-slash" style="font-size: 48px; color: var(--color-text-muted, #94a3b8); margin-bottom: 15px;"></i>
		<h3 style="color: var(--color-text, #f8fafc); margin: 0 0 8px;">
			<?= $is_tr ? "Bildirim Kanalı Yok" : "No Notification Channels" ?>
		</h3>
		<p style="color: var(--color-text-muted, #94a3b8); font-size: 13px; margin: 0;">
			<?= $is_tr
				? "Henüz kanal eklenmedi. Yukarıdaki formu kullanarak Telegram, Discord, Slack veya generic webhook kanalı ekleyin; sistem uyarıları (oto-onarım, anomali tespiti) bu kanallara da gönderilir."
				: "No channels configured yet. Add a Telegram, Discord, Slack or generic webhook channel above and system alerts (self-healing, anomaly detection) will be mirrored to it." ?>
		</p>
	</div>
	<?php else: ?>

	<!-- Channel Table -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; overflow: hidden;">
		<div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color, #334155); display: flex; justify-content: space-between; align-items: center;">
			<h3 style="margin: 0; font-size: 1rem; color: var(--color-text, #f8fafc);">
				<i class="fas fa-tower-broadcast icon-blue"></i> <?= $is_tr ? "Yapılandırılmış Kanallar" : "Configured Channels" ?>
			</h3>
			<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
				<?= count($channels) ?> <?= $is_tr ? "kanal" : "channels" ?>
			</span>
		</div>

		<div style="overflow-x: auto;">
			<table style="width: 100%; border-collapse: collapse; font-size: 13px;">
				<thead>
					<tr style="background: rgba(0,0,0,0.2);">
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Ad" : "Name" ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Tip" : "Type" ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Hedef (maskeli)" : "Target (masked)" ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Olaylar" : "Events" ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Eklenme" : "Added" ?></th>
						<th style="padding: 10px 15px; text-align: right; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "İşlemler" : "Actions" ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($channels as $ch_name => $ch):
						$t = $ch["TYPE"] ?? "webhook";
						$meta = $type_meta[$t] ?? $type_meta["webhook"];
					?>
					<tr style="border-bottom: 1px solid var(--border-color, #334155);">
						<td style="padding: 10px 15px; color: var(--color-text, #f8fafc); font-weight: 600; white-space: nowrap;">
							<?= htmlspecialchars((string)$ch_name) ?>
						</td>
						<td style="padding: 10px 15px; white-space: nowrap;">
							<span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: <?= $meta["color"] ?>22; color: <?= $meta["color"] ?>; border: 1px solid <?= $meta["color"] ?>44;">
								<i class="<?= $meta["icon"] ?>" style="font-size: 9px;"></i>
								<?= htmlspecialchars(ucfirst($t)) ?>
							</span>
						</td>
						<td style="padding: 10px 15px; color: var(--color-text-muted, #cbd5e1); font-family: monospace; font-size: 12px; word-break: break-all;">
							<?= htmlspecialchars((string)($ch["TARGET"] ?? "")) ?>
						</td>
						<td style="padding: 10px 15px; color: var(--color-text-muted, #cbd5e1); white-space: nowrap;">
							<?= htmlspecialchars((string)($ch["EVENTS"] ?? "all")) ?>
						</td>
						<td style="padding: 10px 15px; color: var(--color-text-muted, #94a3b8); white-space: nowrap; font-family: monospace; font-size: 12px;">
							<?= htmlspecialchars((string)($ch["DATE"] ?? "")) ?>
						</td>
						<td style="padding: 10px 15px; text-align: right; white-space: nowrap;">
							<form method="post" action="/list/notify/" style="display: inline;">
								<input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION["token"] ?? "") ?>">
								<input type="hidden" name="action" value="test_channel">
								<input type="hidden" name="v_name" value="<?= htmlspecialchars((string)$ch_name) ?>">
								<button type="submit" class="button button-secondary button-small" style="font-size: 11px; padding: 4px 10px;">
									<i class="fas fa-paper-plane"></i> <?= $is_tr ? "Test" : "Test" ?>
								</button>
							</form>
							<form method="post" action="/list/notify/" style="display: inline;"
								onsubmit="return confirm('<?= $is_tr ? 'Kanal silinsin mi: ' : 'Delete channel: ' ?><?= htmlspecialchars((string)$ch_name) ?> ?');">
								<input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION["token"] ?? "") ?>">
								<input type="hidden" name="action" value="delete_channel">
								<input type="hidden" name="v_name" value="<?= htmlspecialchars((string)$ch_name) ?>">
								<button type="submit" class="button button-secondary button-small" style="font-size: 11px; padding: 4px 10px; color: #ef4444;">
									<i class="fas fa-trash"></i> <?= $is_tr ? "Sil" : "Delete" ?>
								</button>
							</form>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif; ?>

</div>
