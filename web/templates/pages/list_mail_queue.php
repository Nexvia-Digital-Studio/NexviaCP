<?php
$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
$summary = $queue_data["summary"] ?? [];
$messages = $queue_data["messages"] ?? [];
$queue_error = isset($return_var) && $return_var != 0;

// Format seconds into a human readable age string
if (!function_exists("nexvia_mail_queue_age")) {
	function nexvia_mail_queue_age($seconds) {
		$seconds = (int)$seconds;
		if ($seconds <= 0) {
			return "0s";
		}
		$out = "";
		$units = [
			"d" => 86400,
			"h" => 3600,
			"m" => 60,
		];
		foreach ($units as $suffix => $secs) {
			if ($seconds >= $secs) {
				$out .= intdiv($seconds, $secs) . $suffix . " ";
				$seconds %= $secs;
			}
		}
		if ($out === "") {
			$out = $seconds . "s";
		}
		return trim($out);
	}
}

// Format bytes into a human readable size
if (!function_exists("nexvia_mail_queue_size")) {
	function nexvia_mail_queue_size($bytes) {
		$bytes = (float)$bytes;
		if ($bytes >= 1048576) {
			return number_format($bytes / 1048576, 1) . " MB";
		}
		if ($bytes >= 1024) {
			return number_format($bytes / 1024, 1) . " KB";
		}
		return number_format($bytes, 0) . " B";
	}
}
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/mail/">
				<i class="fas fa-arrow-left icon-blue"></i><?= $is_tr ? "Geri" : "Back" ?>
			</a>
			<form method="post" action="/list/mail_queue/" style="display:inline;"
				onsubmit="return confirm('<?= $is_tr ? "Kuyruk tüm mesajlar için teslimat denemesi başlatacak. Devam edilsin mi?" : "Start a delivery run for all queued messages. Continue?" ?>');">
				<input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION["token"] ?? "") ?>">
				<input type="hidden" name="action" value="ctrl">
				<input type="hidden" name="op" value="flush">
				<input type="hidden" name="id" value="">
				<button type="submit" class="button button-primary">
					<i class="fas fa-paper-plane"></i> <?= $is_tr ? "Kuyruğu İşle" : "Flush Queue" ?>
				</button>
			</form>
			<a href="/list/mail_queue/" class="button button-secondary">
				<i class="fas fa-rotate icon-green"></i> <?= $is_tr ? "Yenile" : "Reload" ?>
			</a>
		</div>
		<div class="toolbar-right">
			<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
				<i class="fas fa-clock"></i>
				<?= $is_tr ? "Veriler anlıktır; güncellemek için Yenile kullanın." : "Data is a live snapshot; use Reload to refresh." ?>
			</span>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container" style="max-width: 1400px; padding: 15px 20px;">

	<?php if ($queue_error): ?>
	<div style="background: rgba(239,68,68,0.1); border: 1px solid #ef4444; border-radius: 8px; padding: 16px; margin-bottom: 20px; color: #ef4444;">
		<i class="fas fa-circle-exclamation"></i>
		<?= $is_tr
			? "Mail kuyruğu okunamadı. Exim mail sisteminin kurulu ve etkin olduğundan emin olun."
			: "Unable to read the mail queue. Make sure the Exim mail system is installed and enabled." ?>
	</div>
	<?php endif; ?>

	<!-- Summary Hero Cards -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px;">

		<!-- Total Messages -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8); letter-spacing: 0.5px;">
						<?= $is_tr ? "Toplam Mesaj" : "Total Messages" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= (int)($summary["total"] ?? 0) ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(56,189,248,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-envelope" style="font-size: 16px; color: #38bdf8;"></i>
				</div>
			</div>
		</div>

		<!-- Frozen -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid <?= ($summary["frozen"] ?? 0) > 0 ? '#38bdf8' : 'var(--border-color, #334155)' ?>; border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8);">
						<?= $is_tr ? "Donmuş (Frozen)" : "Frozen" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: <?= ($summary["frozen"] ?? 0) > 0 ? '#38bdf8' : 'var(--color-text, #f8fafc)' ?>;">
						<?= (int)($summary["frozen"] ?? 0) ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(56,189,248,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-snowflake" style="font-size: 16px; color: #38bdf8;"></i>
				</div>
			</div>
		</div>

		<!-- Oldest Message -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid <?= ($summary["oldest_age_seconds"] ?? 0) > 86400 ? '#f97316' : 'var(--border-color, #334155)' ?>; border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8);">
						<?= $is_tr ? "En Eski Mesaj" : "Oldest Message" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: <?= ($summary["oldest_age_seconds"] ?? 0) > 86400 ? '#f97316' : 'var(--color-text, #f8fafc)' ?>;">
						<?= htmlspecialchars(nexvia_mail_queue_age($summary["oldest_age_seconds"] ?? 0)) ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(249,115,22,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-clock-rotate-left" style="font-size: 16px; color: #f97316;"></i>
				</div>
			</div>
		</div>

		<!-- Listed / Truncation -->
		<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
			<div style="display: flex; justify-content: space-between; align-items: flex-start;">
				<div>
					<span style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--color-text-muted, #94a3b8);">
						<?= $is_tr ? "Listelenen" : "Listed" ?>
					</span>
					<h3 style="margin: 4px 0 0; font-size: 1.8rem; font-weight: bold; color: var(--color-text, #f8fafc);">
						<?= (int)($summary["count_listed"] ?? 0) ?>
						<?php if (!empty($summary["truncated"])): ?>
							<span style="font-size: 12px; color: #eab308;">(<?= $is_tr ? "ilk 1000" : "first 1000" ?>)</span>
						<?php endif; ?>
					</h3>
				</div>
				<div style="width: 36px; height: 36px; border-radius: 6px; background: rgba(168,85,247,0.15); display: flex; align-items: center; justify-content: center;">
					<i class="fas fa-list" style="font-size: 16px; color: #a855f7;"></i>
				</div>
			</div>
		</div>

	</div>

	<?php if (empty($messages)): ?>
	<!-- Empty State -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 40px; text-align: center; margin-bottom: 20px;">
		<i class="fas fa-circle-check" style="font-size: 48px; color: #22c55e; margin-bottom: 15px;"></i>
		<h3 style="color: var(--color-text, #f8fafc); margin: 0 0 8px;">
			<?= $is_tr ? "Mail Kuyruğu Boş" : "Mail Queue Is Empty" ?>
		</h3>
		<p style="color: var(--color-text-muted, #94a3b8); font-size: 13px; margin: 0;">
			<?= $is_tr
				? "Bekleyen veya donmuş mesaj yok. Her şey yolunda görünüyor."
				: "No pending or frozen messages. Everything looks healthy." ?>
		</p>
	</div>
	<?php else: ?>

	<!-- Messages Table -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; overflow: hidden; margin-bottom: 20px;">
		<div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color, #334155); display: flex; justify-content: space-between; align-items: center;">
			<h3 style="margin: 0; font-size: 1rem; color: var(--color-text, #f8fafc);">
				<i class="fas fa-layer-group icon-blue"></i> <?= $is_tr ? "Kuyruk Mesajları" : "Queue Messages" ?>
			</h3>
			<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
				<?= count($messages) ?> <?= $is_tr ? "kayıt" : "entries" ?>
				<?= !empty($summary["truncated"]) ? ($is_tr ? " (liste 1000 ile sınırlı)" : " (list capped at 1000)") : "" ?>
			</span>
		</div>

		<div style="overflow-x: auto;">
			<table style="width: 100%; border-collapse: collapse; font-size: 13px;">
				<thead>
					<tr style="background: rgba(0,0,0,0.2);">
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Mesaj ID" : "Message ID" ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Yaş" : "Age" ?></th>
						<th style="padding: 10px 15px; text-align: right; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Boyut" : "Size" ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Gönderen" : "Sender" ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Alıcı(lar)" : "Recipient(s)" ?></th>
						<th style="padding: 10px 15px; text-align: center; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "Durum" : "State" ?></th>
						<th style="padding: 10px 15px; text-align: center; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= $is_tr ? "İşlemler" : "Actions" ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($messages as $m):
						$id = (string)($m["id"] ?? "");
						$is_frozen = !empty($m["frozen"]);
						$recipients = implode(", ", array_map("strval", (array)($m["recipients"] ?? [])));
						$confirm_remove = $is_tr ? "Bu mesaj teslim edilmeden silinsin mi?" : "Remove this message without delivery?";
					?>
					<tr style="border-bottom: 1px solid var(--border-color, #334155); <?= $is_frozen ? "background: rgba(56,189,248,0.06);" : "" ?>">
						<td style="padding: 10px 15px; color: var(--color-text, #f8fafc); font-family: monospace; font-size: 12px; white-space: nowrap;">
							<?= htmlspecialchars($id) ?>
						</td>
						<td style="padding: 10px 15px; color: var(--color-text-muted, #cbd5e1); white-space: nowrap;">
							<?= htmlspecialchars(nexvia_mail_queue_age($m["age_seconds"] ?? 0)) ?>
						</td>
						<td style="padding: 10px 15px; text-align: right; color: var(--color-text-muted, #cbd5e1); font-family: monospace; font-size: 12px; white-space: nowrap;">
							<?= htmlspecialchars(nexvia_mail_queue_size($m["size_bytes"] ?? 0)) ?>
						</td>
						<td style="padding: 10px 15px; color: var(--color-text, #f8fafc); white-space: nowrap;">
							<?= htmlspecialchars((string)($m["sender"] ?? "")) ?>
						</td>
						<td style="padding: 10px 15px; color: var(--color-text-muted, #cbd5e1); max-width: 380px; overflow-wrap: anywhere;">
							<?= htmlspecialchars($recipients) ?>
						</td>
						<td style="padding: 10px 15px; text-align: center; white-space: nowrap;">
							<?php if ($is_frozen): ?>
							<span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: rgba(56,189,248,0.12); color: #38bdf8; border: 1px solid rgba(56,189,248,0.3);">
								<i class="fas fa-snowflake" style="font-size: 9px;"></i> <?= $is_tr ? "DONMUŞ" : "FROZEN" ?>
							</span>
							<?php else: ?>
							<span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: rgba(34,197,94,0.12); color: #22c55e; border: 1px solid rgba(34,197,94,0.3);">
								<i class="fas fa-hourglass-half" style="font-size: 9px;"></i> <?= $is_tr ? "BEKLİYOR" : "PENDING" ?>
							</span>
							<?php endif; ?>
						</td>
						<td style="padding: 10px 15px; text-align: center; white-space: nowrap;">
							<form method="post" action="/list/mail_queue/" style="display: inline-flex; gap: 6px;">
								<input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION["token"] ?? "") ?>">
								<input type="hidden" name="action" value="ctrl">
								<input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
								<button type="submit" name="op" value="retry" class="button button-secondary button-small" style="font-size: 10px; padding: 4px 8px;" title="<?= $is_tr ? "Şimdi teslim etmeyi dene" : "Try to deliver now" ?>">
									<i class="fas fa-rotate-right"></i> <?= $is_tr ? "Dene" : "Retry" ?>
								</button>
								<?php if ($is_frozen): ?>
								<button type="submit" name="op" value="unfreeze" class="button button-secondary button-small" style="font-size: 10px; padding: 4px 8px;" title="<?= $is_tr ? "Donmayı kaldır" : "Thaw message" ?>">
									<i class="fas fa-fire"></i> <?= $is_tr ? "Çöz" : "Unfreeze" ?>
								</button>
								<?php else: ?>
								<button type="submit" name="op" value="freeze" class="button button-secondary button-small" style="font-size: 10px; padding: 4px 8px;" title="<?= $is_tr ? "Mesajı dondur" : "Freeze message" ?>">
									<i class="fas fa-snowflake"></i> <?= $is_tr ? "Dondur" : "Freeze" ?>
								</button>
								<?php endif; ?>
								<button type="submit" name="op" value="remove" class="button button-secondary button-small" style="font-size: 10px; padding: 4px 8px; color: #ef4444;" title="<?= $is_tr ? "Teslim etmeden sil" : "Remove without delivery" ?>"
									onclick="return confirm('<?= htmlspecialchars($confirm_remove) ?>');">
									<i class="fas fa-trash-can"></i> <?= $is_tr ? "Sil" : "Remove" ?>
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

	<!-- Danger Zone: remove all -->
	<?php if (!empty($messages)): ?>
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid rgba(239,68,68,0.4); border-radius: 8px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
		<div>
			<h3 style="margin: 0; font-size: 0.95rem; color: #ef4444;">
				<i class="fas fa-triangle-exclamation"></i> <?= $is_tr ? "Tehlikeli Bölge" : "Danger Zone" ?>
			</h3>
			<p style="margin: 4px 0 0; font-size: 12px; color: var(--color-text-muted, #94a3b8);">
				<?= $is_tr
					? "Kuyruktaki tüm mesajları teslim edilmeden kalıcı olarak siler."
					: "Permanently remove every queued message without delivery." ?>
			</p>
		</div>
		<form method="post" action="/list/mail_queue/"
			onsubmit="return confirm('<?= $is_tr ? "Kuyruktaki TÜM mesajlar silinecek. Emin misiniz?" : "This deletes ALL queued messages. Are you sure?" ?>');">
			<input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION["token"] ?? "") ?>">
			<input type="hidden" name="action" value="ctrl">
			<input type="hidden" name="op" value="remove">
			<input type="hidden" name="id" value="all">
			<button type="submit" class="button button-secondary" style="color: #ef4444;">
				<i class="fas fa-trash-can"></i> <?= $is_tr ? "Tümünü Sil" : "Remove All" ?>
			</button>
		</form>
	</div>
	<?php endif; ?>

</div>
