<?php
// Central log search page. Log lines are attacker-controlled, so every
// dynamic value below is passed through htmlspecialchars() unconditionally.

$log_search_q = (string)($log_search_q ?? "");
$log_search_type = (string)($log_search_type ?? "all");
$log_search_domain = (string)($log_search_domain ?? "all");
$log_search_limit = (int)($log_search_limit ?? 100);
$log_search_domains = is_array($log_search_domains ?? null) ? $log_search_domains : [];
$log_search_results = is_array($log_search_results ?? null) ? $log_search_results : [];
$log_search_total = (int)($log_search_total ?? 0);
$log_search_truncated = !empty($log_search_truncated);
$log_search_done = !empty($log_search_done);
$log_search_error = (string)($log_search_error ?? "");
$log_search_all_users_scope = !empty($log_search_all_users_scope);

$type_badge = [
	"access" => ["icon" => "fas fa-file-lines", "color" => "#38bdf8"],
	"error" => ["icon" => "fas fa-circle-exclamation", "color" => "#ef4444"],
];
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/web/">
				<i class="fas fa-arrow-left icon-blue"></i><?= __tr("Back", "Geri") ?>
			</a>
		</div>
		<div class="toolbar-right">
			<span style="font-size: 12px; color: var(--color-text-muted, #94a3b8);">
				<i class="fas fa-magnifying-glass"></i>
				<?= __tr(
					"Fixed-string search across domain access and error logs",
					"Domain access ve error loglarında sabit dizgi araması",
				) ?>
			</span>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container" style="max-width: 1400px; padding: 15px 20px;">

	<!-- Search Form (GET: no state is changed, no CSRF token needed) -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
		<h3 style="margin: 0 0 15px; font-size: 1rem; color: var(--color-text, #f8fafc);">
			<i class="fas fa-magnifying-glass icon-blue"></i> <?= __tr("Log Search", "Log Arama") ?>
		</h3>
		<form method="get" action="/list/log-search/">
			<div style="display: grid; grid-template-columns: 2fr 1fr 160px 120px auto; gap: 12px; align-items: end;">
				<div>
					<label for="log-search-q" style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= __tr("Search pattern", "Arama terimi") ?>
					</label>
					<input type="text" class="form-control" id="log-search-q" name="q" maxlength="256" required
						value="<?= htmlspecialchars($log_search_q, ENT_QUOTES) ?>"
						placeholder="<?= htmlspecialchars('192.0.2.10 / 403 / wp-login', ENT_QUOTES) ?>"
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
				</div>
				<div>
					<label for="log-search-domain" style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= __tr("Domain", "Domain") ?>
					</label>
					<select class="form-control" id="log-search-domain" name="domain"
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
						<option value="all"<?= $log_search_domain === "all" ? " selected" : "" ?>><?= htmlspecialchars(__tr("All domains", "Tüm domainler"), ENT_QUOTES) ?></option>
						<?php if ($log_search_all_users_scope && count($log_search_domains) > 1): ?>
							<?php foreach ($log_search_domains as $ls_group_user => $ls_group_domains): ?>
								<optgroup label="<?= htmlspecialchars("user: " . (string)$ls_group_user, ENT_QUOTES) ?>">
									<?php foreach ($ls_group_domains as $ls_option_domain): ?>
										<option value="<?= htmlspecialchars((string)$ls_option_domain, ENT_QUOTES) ?>"<?= $log_search_domain === (string)$ls_option_domain ? " selected" : "" ?>>
											<?= htmlspecialchars((string)$ls_option_domain, ENT_QUOTES) ?>
										</option>
									<?php endforeach; ?>
								</optgroup>
							<?php endforeach; ?>
						<?php else: ?>
							<?php foreach ($log_search_domains as $ls_group_domains): ?>
								<?php foreach ($ls_group_domains as $ls_option_domain): ?>
									<option value="<?= htmlspecialchars((string)$ls_option_domain, ENT_QUOTES) ?>"<?= $log_search_domain === (string)$ls_option_domain ? " selected" : "" ?>>
										<?= htmlspecialchars((string)$ls_option_domain, ENT_QUOTES) ?>
									</option>
								<?php endforeach; ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>
				<div>
					<label for="log-search-type" style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= __tr("Log type", "Log tipi") ?>
					</label>
					<select class="form-control" id="log-search-type" name="type"
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
						<option value="all"<?= $log_search_type === "all" ? " selected" : "" ?>><?= __tr("All", "Tümü") ?></option>
						<option value="access"<?= $log_search_type === "access" ? " selected" : "" ?>><?= __tr("Access", "Access") ?></option>
						<option value="error"<?= $log_search_type === "error" ? " selected" : "" ?>><?= __tr("Error", "Error") ?></option>
					</select>
				</div>
				<div>
					<label for="log-search-limit" style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;">
						<?= __tr("Limit", "Limit") ?>
					</label>
					<input type="number" class="form-control" id="log-search-limit" name="limit" min="1" max="1000"
						value="<?= htmlspecialchars((string)$log_search_limit, ENT_QUOTES) ?>"
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
				</div>
				<div>
					<button type="submit" class="button button-primary" style="white-space: nowrap;">
						<i class="fas fa-magnifying-glass"></i> <?= __tr("Search", "Ara") ?>
					</button>
				</div>
			</div>
			<p style="font-size: 11px; color: var(--color-text-muted, #94a3b8); margin: 10px 0 0;">
				<?= __tr(
					"The pattern is matched as a fixed string (not a regex) in the active access/error logs of the selected domains. Rotated .gz archives are not searched. Result lines are cut at 500 characters.",
					"Arama terimi sabit dizgi olarak (regex değil) seçili domainlerin aktif access/error loglarında aranır. .gz arşivleri taranmaz. Sonuç satırları 500 karakterde kesilir.",
				) ?>
			</p>
		</form>
	</div>

	<?php if ($log_search_error !== ""): ?>
	<!-- Error Message -->
	<div style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.4); border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; color: #fca5a5; font-size: 13px;">
		<i class="fas fa-circle-exclamation"></i>
		<?= htmlspecialchars($log_search_error, ENT_QUOTES) ?>
	</div>
	<?php endif; ?>

	<?php if ($log_search_done && $log_search_error === "" && $log_search_total === 0): ?>
	<!-- Empty State -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 40px; text-align: center;">
		<i class="fas fa-magnifying-glass-minus" style="font-size: 48px; color: var(--color-text-muted, #94a3b8); margin-bottom: 15px;"></i>
		<h3 style="color: var(--color-text, #f8fafc); margin: 0 0 8px;">
			<?= __tr("No results found", "Sonuç bulunamadı") ?>
		</h3>
		<p style="color: var(--color-text-muted, #94a3b8); font-size: 13px; margin: 0;">
			<?= __tr(
				"No log line matches this pattern in the selected scope.",
				"Seçili kapsamda bu terime uyan log satırı yok.",
			) ?>
		</p>
	</div>
	<?php elseif ($log_search_done && $log_search_error === ""): ?>

	<?php if ($log_search_truncated): ?>
	<!-- Truncation Warning -->
	<div style="background: rgba(234,179,8,0.12); border: 1px solid rgba(234,179,8,0.4); border-radius: 8px; padding: 12px 18px; margin-bottom: 15px; color: #fde047; font-size: 13px;">
		<i class="fas fa-triangle-exclamation"></i>
		<?= __tr(
			"Results were limited: more matches exist than displayed. Raise the limit or narrow the search.",
			"Sonuçlar sınırlandırıldı: görüntülenenden daha fazla eşleşme var. Limiti artırın veya aramayı daraltın.",
		) ?>
	</div>
	<?php endif; ?>

	<!-- Results Table -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; overflow: hidden;">
		<div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color, #334155); display: flex; justify-content: space-between; align-items: center;">
			<h3 style="margin: 0; font-size: 1rem; color: var(--color-text, #f8fafc);">
				<i class="fas fa-list icon-blue"></i> <?= __tr("Results", "Sonuçlar") ?>
			</h3>
			<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
				<?= htmlspecialchars((string)$log_search_total, ENT_QUOTES) ?>
				<?= __tr("match(es)", "eşleşme") ?>
			</span>
		</div>
		<div style="overflow-x: auto;">
			<table style="width: 100%; border-collapse: collapse; font-size: 13px;">
				<thead>
					<tr style="background: rgba(0,0,0,0.2);">
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= __tr("Domain", "Domain") ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= __tr("Type", "Tip") ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= __tr("Log line", "Log satırı") ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($log_search_results as $ls_row):
						$ls_row = is_array($ls_row) ? $ls_row : [];
						$ls_domain = (string)($ls_row["domain"] ?? "");
						$ls_kind = (string)($ls_row["type"] ?? "");
						$ls_file = (string)($ls_row["file"] ?? "");
						$ls_line = (string)($ls_row["line"] ?? "");
						$ls_ts = (string)($ls_row["timestamp_raw"] ?? "");
						$ls_meta = $type_badge[$ls_kind] ?? $type_badge["access"];
					?>
					<tr style="border-bottom: 1px solid var(--border-color, #334155); vertical-align: top;">
						<td style="padding: 10px 15px; color: var(--color-text, #f8fafc); font-weight: 600; white-space: nowrap;">
							<?= htmlspecialchars($ls_domain, ENT_QUOTES) ?>
						</td>
						<td style="padding: 10px 15px; white-space: nowrap;">
							<span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; background: <?= $ls_meta["color"] ?>22; color: <?= $ls_meta["color"] ?>; border: 1px solid <?= $ls_meta["color"] ?>44;">
								<i class="<?= $ls_meta["icon"] ?>" style="font-size: 9px;"></i>
								<?= htmlspecialchars($ls_kind, ENT_QUOTES) ?>
							</span>
						</td>
						<td style="padding: 10px 15px;">
							<div style="font-size: 11px; color: var(--color-text-muted, #94a3b8); font-family: monospace; margin-bottom: 4px; word-break: break-all;">
								<?= htmlspecialchars($ls_file, ENT_QUOTES) ?>
								<?php if ($ls_ts !== ""): ?>
									&middot; <?= htmlspecialchars($ls_ts, ENT_QUOTES) ?>
								<?php endif; ?>
							</div>
							<div style="color: var(--color-text-muted, #cbd5e1); font-family: monospace; font-size: 12px; word-break: break-all; white-space: pre-wrap;">
								<?= htmlspecialchars($ls_line, ENT_QUOTES) ?>
							</div>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif; ?>

</div>
