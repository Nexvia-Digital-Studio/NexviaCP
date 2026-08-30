<?php
// Notification center page — renders the same data as the bell dropdown
// (list/notifications/?ajax=1) as a full page with delete actions.
$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
$table_data = array_values($data ?? []);
$total = count($table_data);
$unread = 0;
foreach ($table_data as $note) {
	if (empty($note["ACKNOWLEDGED"])) {
		$unread++;
	}
}
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/user/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml($is_tr ? "Geri" : "Back") ?>
			</a>
		</div>
		<div class="toolbar-right">
			<span class="badge badge-secondary" style="padding:6px 12px; font-size:12px;">
				<i class="fas fa-bell"></i>
				<?= tohtml($is_tr ? "$total bildirim · $unread okunmamış" : "$total notifications · $unread unread") ?>
			</span>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="l-center units">
	<div class="units-table" style="margin-top: 10px;">
		<div class="units-table-header">
			<div class="units-table-cell"><?= tohtml($is_tr ? "Tarih" : "Date") ?></div>
			<div class="units-table-cell"><?= tohtml($is_tr ? "Bildirim" : "Notification") ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml($is_tr ? "Öncelik" : "Priority") ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml($is_tr ? "Durum" : "Status") ?></div>
			<div class="units-table-cell u-text-center"><?= tohtml($is_tr ? "İşlem" : "Action") ?></div>
		</div>

		<?php if (empty($table_data)): ?>
			<div class="units-table-row">
				<div class="units-table-cell u-text-center u-text-muted" style="grid-column: 1 / -1; padding: 24px 0;">
					<?= tohtml($is_tr ? "Henüz bildirim yok." : "No notifications yet.") ?>
				</div>
			</div>
		<?php else: ?>
			<?php foreach ($table_data as $note): ?>
				<?php
					$priority = (int) ($note["PRIORITY"] ?? 0);
					$ack = !empty($note["ACKNOWLEDGED"]);
					$badge = $priority >= 2 ? "badge-danger" : ($priority == 1 ? "badge-warning" : "badge-secondary");
					?>
				<div class="units-table-row"<?= $ack ? ' style="opacity:.6;"' : '' ?>>
					<div class="units-table-cell u-text-muted" title="<?= tohtml($note["TIMESTAMP_TITLE"] ?? "") ?>">
						<?= tohtml($note["TIMESTAMP_TEXT"] ?? "") ?>
					</div>
					<div class="units-table-cell">
						<?= !empty($note["TOPIC"]) ? "<strong>" . tohtml($note["TOPIC"]) . "</strong><br>" : "" ?>
						<?= tohtml($note["NOTE"] ?? "") ?>
					</div>
					<div class="units-table-cell u-text-center">
						<span class="badge <?= $badge ?>" style="font-size:11px; padding:2px 8px;"><?= $priority ?></span>
					</div>
					<div class="units-table-cell u-text-center">
						<?php if ($ack): ?>
							<span class="badge badge-success" style="font-size:11px; padding:2px 8px;">
								<i class="fas fa-check"></i> <?= tohtml($is_tr ? "Okundu" : "Read") ?>
							</span>
						<?php else: ?>
							<span class="badge badge-info" style="font-size:11px; padding:2px 8px;"><?= tohtml($is_tr ? "Yeni" : "New") ?></span>
						<?php endif; ?>
					</div>
					<div class="units-table-cell u-text-center">
						<form method="post" action="/delete/notification/" style="margin:0; display:inline;"
							onsubmit="return confirm('<?= tohtml($is_tr ? "Bu bildirim silinsin mi?" : "Delete this notification?") ?>');">
							<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
							<input type="hidden" name="delete" value="1">
							<input type="hidden" name="notification_id" value="<?= tohtml((string) ($note["ID"] ?? "")) ?>">
							<button type="submit" class="button button-danger button-small" title="<?= tohtml($is_tr ? "Sil" : "Delete") ?>" style="padding:3px 8px; font-size:11px;">
								<i class="fas fa-trash-can"></i>
							</button>
						</form>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>

<footer class="app-footer">
	<div class="footer-inner">
		&copy; <span class="copy-year"></span> · NexviaCP
	</div>
</footer>
