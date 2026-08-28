<?php
// Remote server registry page. All dynamic values (server fields, command
// output) are attacker-influenceable at some level, so everything below is
// passed through htmlspecialchars() unconditionally. Remote command output
// is treated as untrusted data and only ever rendered as plain text.

// Bilingual helper (defined in web/inc/main.php; guarded so the template
// also renders standalone without a fatal redefinition error)
if (!function_exists("__tr")) {
	function __tr(string $en, string $tr): string {
		$lang = $_SESSION["language"] ?? $_SESSION["LANGUAGE"] ?? "en";
		return ($lang === "tr") ? $tr : $en;
	}
}

$servers = is_array($remote_servers ?? null) ? $remote_servers : [];
$result = $remote_result ?? null;
$editing = $edit_server ?? null;

$token = htmlspecialchars($_SESSION["token"] ?? "", ENT_QUOTES);
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/server/">
				<i class="fas fa-arrow-left icon-blue"></i><?= __tr("Back", "Geri") ?>
			</a>
		</div>
		<div class="toolbar-right">
			<span style="font-size: 12px; color: var(--color-text-muted, #94a3b8);">
				<i class="fas fa-server"></i>
				<?= __tr(
					"SSH (key-only) remote command execution - every run is logged",
					"SSH (sadece anahtar) uzak komut çalıştırma - her çalıştırma loglanır",
				) ?>
			</span>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container" style="max-width: 1400px; padding: 15px 20px;">

	<!-- Result panel: output of the last connection test / command run -->
	<?php if (is_array($result) && !empty($result)): ?>
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
		<h3 style="margin: 0 0 10px; font-size: 1rem; color: var(--color-text, #f8fafc);">
			<i class="fas fa-terminal icon-<?= ($result["exit_code"] ?? 1) === 0 ? "green" : "red" ?>"></i>
			<?= ($result["kind"] ?? "") === "check"
				? __tr("Connection Test Result", "Bağlantı Testi Sonucu")
				: __tr("Command Result", "Komut Sonucu") ?>
			&mdash; <?= htmlspecialchars((string)($result["server"] ?? ""), ENT_QUOTES) ?>
			(<?= __tr("exit code", "çıkış kodu") ?>: <?= htmlspecialchars((string)($result["exit_code"] ?? ""), ENT_QUOTES) ?>)
		</h3>
		<?php if (($result["kind"] ?? "") === "run" && ($result["command"] ?? "") !== ""): ?>
		<p style="margin: 0 0 8px; font-family: monospace; font-size: 12px; color: var(--color-text-muted, #cbd5e1); word-break: break-all;">
			$ <?= htmlspecialchars((string)$result["command"], ENT_QUOTES) ?>
		</p>
		<?php endif; ?>
		<p style="margin: 0 0 8px; font-size: 11px; color: #eab308;">
			<i class="fas fa-triangle-exclamation"></i>
			<?= __tr(
				"Output is UNTRUSTED data from the remote server; it is displayed as plain text only.",
				"Çıktı uzak sunucudan gelen GÜVENİLMEZ veridir; yalnızca düz metin olarak gösterilir.",
			) ?>
		</p>
		<pre style="margin: 0; padding: 12px; border-radius: 6px; background: rgba(0,0,0,0.35); border: 1px solid var(--border-color, #334155); color: var(--color-text, #f8fafc); font-size: 12px; max-height: 400px; overflow: auto; white-space: pre-wrap; word-break: break-all;"><?= htmlspecialchars((string)($result["output"] ?? ""), ENT_QUOTES) ?></pre>
	</div>
	<?php endif; ?>

	<!-- Add / edit server form -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
		<h3 style="margin: 0 0 15px; font-size: 1rem; color: var(--color-text, #f8fafc);">
			<i class="fas fa-circle-plus icon-green"></i>
			<?= $editing
				? __tr("Edit Remote Server", "Uzak Sunucuyu Düzenle") . ": " . htmlspecialchars((string)$editing["name"], ENT_QUOTES)
				: __tr("Add Remote Server", "Uzak Sunucu Ekle") ?>
		</h3>
		<form method="post" action="/list/servers/">
			<input type="hidden" name="token" value="<?= $token ?>">
			<input type="hidden" name="action" value="add_server">
			<div style="display: grid; grid-template-columns: 140px 1fr 90px 140px 1fr 1fr auto; gap: 12px; align-items: end;">
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;"><?= __tr("Name", "Ad") ?></label>
					<input type="text" class="form-control" name="v_name" required maxlength="64" pattern="[a-zA-Z0-9_-]+"
						placeholder="web1"
						value="<?= $editing ? htmlspecialchars((string)$editing["name"], ENT_QUOTES) : "" ?>"
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;"><?= __tr("Host", "Host") ?></label>
					<input type="text" class="form-control" name="v_host" required maxlength="253"
						placeholder="203.0.113.10 / server.example.com"
						value="<?= $editing ? htmlspecialchars((string)($editing["HOST"] ?? ""), ENT_QUOTES) : "" ?>"
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;"><?= __tr("Port", "Port") ?></label>
					<input type="text" class="form-control" name="v_port" maxlength="5" pattern="[0-9]+"
						placeholder="22"
						value="<?= $editing ? htmlspecialchars((string)($editing["PORT"] ?? "22"), ENT_QUOTES) : "22" ?>"
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;"><?= __tr("User", "Kullanıcı") ?></label>
					<input type="text" class="form-control" name="v_user" maxlength="64" pattern="[a-zA-Z0-9._-]+"
						placeholder="root"
						value="<?= $editing ? htmlspecialchars((string)($editing["USER"] ?? "root"), ENT_QUOTES) : "root" ?>"
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;"><?= __tr("Key path", "Anahtar yolu") ?></label>
					<input type="text" class="form-control" name="v_key" maxlength="512"
						placeholder="/root/.ssh/id_ed25519"
						value="<?= $editing ? htmlspecialchars((string)($editing["KEY"] ?? "/root/.ssh/id_ed25519"), ENT_QUOTES) : "/root/.ssh/id_ed25519" ?>"
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
				</div>
				<div>
					<label style="display: block; font-size: 11px; font-weight: 600; color: var(--color-text-muted, #94a3b8); text-transform: uppercase; margin-bottom: 5px;"><?= __tr("Note", "Not") ?></label>
					<input type="text" class="form-control" name="v_note" maxlength="256"
						placeholder="optional"
						value="<?= $editing ? htmlspecialchars((string)($editing["NOTE"] ?? ""), ENT_QUOTES) : "" ?>"
						style="width: 100%; padding: 8px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-size: 13px;">
				</div>
				<div>
					<button type="submit" class="button button-primary" style="white-space: nowrap;">
						<i class="fas fa-floppy-disk"></i> <?= __tr("Save", "Kaydet") ?>
					</button>
				</div>
			</div>
			<p style="font-size: 11px; color: var(--color-text-muted, #94a3b8); margin: 10px 0 0;">
				<?= __tr(
					"Key path must be under /root/.ssh/ or /home/&lt;user&gt;/ (private keys are never uploaded, read or copied - only their existence is tested). Re-using a name updates the entry.",
					"Anahtar yolu /root/.ssh/ veya /home/&lt;kullanıcı&gt;/ altında olmalı (özel anahtarlar asla yüklenmez, okunmaz veya kopyalanmaz - yalnızca varlığı test edilir). Aynı ad tekrar kullanılırsa kayıt güncellenir.",
				) ?>
			</p>
		</form>
	</div>

	<?php if (empty($servers)): ?>
	<!-- Empty state -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 40px; text-align: center;">
		<i class="fas fa-server" style="font-size: 48px; color: var(--color-text-muted, #94a3b8); margin-bottom: 15px;"></i>
		<h3 style="color: var(--color-text, #f8fafc); margin: 0 0 8px;"><?= __tr("No Remote Servers", "Uzak Sunucu Yok") ?></h3>
		<p style="color: var(--color-text-muted, #94a3b8); font-size: 13px; margin: 0;">
			<?= __tr(
				"No remote servers registered yet. Add one above to test connectivity and run commands over key-only SSH.",
				"Henüz uzak sunucu kaydı yok. Yukarıdan ekleyerek bağlantı testi yapabilir ve sadece anahtarlı SSH üzerinden komut çalıştırabilirsiniz.",
			) ?>
		</p>
	</div>
	<?php else: ?>

	<!-- Server table -->
	<div style="background: var(--color-background-card, #1e222d); border: 1px solid var(--border-color, #334155); border-radius: 8px; overflow: hidden;">
		<div style="padding: 15px 20px; border-bottom: 1px solid var(--border-color, #334155); display: flex; justify-content: space-between; align-items: center;">
			<h3 style="margin: 0; font-size: 1rem; color: var(--color-text, #f8fafc);">
				<i class="fas fa-network-wired icon-blue"></i> <?= __tr("Registered Servers", "Kayıtlı Sunucular") ?>
			</h3>
			<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
				<?= count($servers) ?> <?= __tr("servers", "sunucu") ?>
			</span>
		</div>
		<div style="overflow-x: auto;">
			<table style="width: 100%; border-collapse: collapse; font-size: 13px;">
				<thead>
					<tr style="background: rgba(0,0,0,0.2);">
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= __tr("Name", "Ad") ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= __tr("Host", "Host") ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= __tr("Port", "Port") ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= __tr("User", "Kullanıcı") ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= __tr("Key", "Anahtar") ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= __tr("Note", "Not") ?></th>
						<th style="padding: 10px 15px; text-align: left; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= __tr("Last check", "Son check") ?></th>
						<th style="padding: 10px 15px; text-align: right; color: var(--color-text-muted, #94a3b8); font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;"><?= __tr("Actions", "İşlemler") ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($servers as $srv_name => $srv): ?>
					<tr style="border-bottom: 1px solid var(--border-color, #334155);">
						<td style="padding: 10px 15px; color: var(--color-text, #f8fafc); font-weight: 600; white-space: nowrap;"><?= htmlspecialchars((string)$srv_name, ENT_QUOTES) ?></td>
						<td style="padding: 10px 15px; color: var(--color-text-muted, #cbd5e1); font-family: monospace; font-size: 12px;"><?= htmlspecialchars((string)($srv["HOST"] ?? ""), ENT_QUOTES) ?></td>
						<td style="padding: 10px 15px; color: var(--color-text-muted, #cbd5e1); font-family: monospace; font-size: 12px;"><?= htmlspecialchars((string)($srv["PORT"] ?? ""), ENT_QUOTES) ?></td>
						<td style="padding: 10px 15px; color: var(--color-text-muted, #cbd5e1); white-space: nowrap;"><?= htmlspecialchars((string)($srv["USER"] ?? ""), ENT_QUOTES) ?></td>
						<td style="padding: 10px 15px; white-space: nowrap;">
							<?php if (($srv["KEY_EXISTS"] ?? "no") === "yes"): ?>
							<span style="color: #22c55e; font-weight: 700;" title="<?= __tr("Key file readable", "Anahtar dosyası okunabilir") ?>">&#10004;</span>
							<?php else: ?>
							<span style="color: #ef4444; font-weight: 700;" title="<?= __tr("Key file missing or not readable", "Anahtar dosyası yok ya da okunamıyor") ?>">&#10008;</span>
							<?php endif; ?>
						</td>
						<td style="padding: 10px 15px; color: var(--color-text-muted, #cbd5e1); max-width: 180px; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars((string)($srv["NOTE"] ?? ""), ENT_QUOTES) ?></td>
						<td style="padding: 10px 15px; white-space: nowrap; font-family: monospace; font-size: 11px;">
							<?php $st = (string)($srv["LAST_CHECK_STATUS"] ?? ""); ?>
							<?php if ($st === "ok"): ?>
							<span style="color: #22c55e; font-weight: 700;"><?= __tr("OK", "OK") ?></span>
							<span style="color: var(--color-text-muted, #94a3b8);"> <?= htmlspecialchars((string)($srv["LAST_CHECK"] ?? ""), ENT_QUOTES) ?></span>
							<?php elseif ($st === "fail"): ?>
							<span style="color: #ef4444; font-weight: 700;"><?= __tr("FAIL", "HATA") ?></span>
							<span style="color: var(--color-text-muted, #94a3b8);"> <?= htmlspecialchars((string)($srv["LAST_CHECK"] ?? ""), ENT_QUOTES) ?></span>
							<?php else: ?>
							<span style="color: var(--color-text-muted, #94a3b8);"><?= __tr("never", "hiç") ?></span>
							<?php endif; ?>
						</td>
						<td style="padding: 10px 15px; text-align: right; white-space: nowrap;">
							<form method="post" action="/list/servers/" style="display: inline;">
								<input type="hidden" name="token" value="<?= $token ?>">
								<input type="hidden" name="action" value="check_server">
								<input type="hidden" name="v_name" value="<?= htmlspecialchars((string)$srv_name, ENT_QUOTES) ?>">
								<button type="submit" class="button button-secondary button-small" style="font-size: 11px; padding: 4px 10px;">
									<i class="fas fa-plug-circle-check"></i> <?= __tr("Test", "Test") ?>
								</button>
							</form>
							<a class="button button-secondary button-small" style="font-size: 11px; padding: 4px 10px;" href="/list/servers/?edit=<?= urlencode((string)$srv_name) ?>">
								<i class="fas fa-pen"></i> <?= __tr("Edit", "Düzenle") ?>
							</a>
							<form method="post" action="/list/servers/"
								onsubmit="return confirm('<?= __tr("Delete server", "Sunucu silinsin mi") ?>: <?= htmlspecialchars((string)$srv_name, ENT_QUOTES) ?> ?');"
								style="display: inline;">
								<input type="hidden" name="token" value="<?= $token ?>">
								<input type="hidden" name="action" value="delete_server">
								<input type="hidden" name="v_name" value="<?= htmlspecialchars((string)$srv_name, ENT_QUOTES) ?>">
								<button type="submit" class="button button-secondary button-small" style="font-size: 11px; padding: 4px 10px; color: #ef4444;">
									<i class="fas fa-trash"></i> <?= __tr("Delete", "Sil") ?>
								</button>
							</form>
						</td>
					</tr>
					<tr style="border-bottom: 1px solid var(--border-color, #334155);">
						<td colspan="8" style="padding: 6px 15px 12px;">
							<form method="post" action="/list/servers/" style="display: inline-flex; gap: 8px; align-items: center; flex-wrap: wrap;">
								<input type="hidden" name="token" value="<?= $token ?>">
								<input type="hidden" name="action" value="run_server">
								<input type="hidden" name="v_name" value="<?= htmlspecialchars((string)$srv_name, ENT_QUOTES) ?>">
								<i class="fas fa-terminal icon-blue" style="font-size: 12px;"></i>
								<input type="text" class="form-control" name="v_command" required maxlength="2000"
									placeholder='<?= __tr("command (single line, max 2000 chars)", "komut (tek satır, en fazla 2000 karakter)") ?>'
									style="flex: 1; min-width: 320px; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--border-color, #334155); background: rgba(0,0,0,0.2); color: var(--color-text, #f8fafc); font-family: monospace; font-size: 12px;">
								<button type="submit" class="button button-primary button-small" style="font-size: 11px; padding: 5px 12px;">
									<i class="fas fa-play"></i> <?= __tr("Run", "Çalıştır") ?>
								</button>
							</form>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<p style="font-size: 11px; color: var(--color-text-muted, #94a3b8); margin: 12px 0 0;">
		<i class="fas fa-shield-halved"></i>
		<?= __tr(
			"Commands run with BatchMode SSH (key-only). Newlines are rejected; command length is capped at 2000 characters and output at 100KB. Every test and run is logged.",
			"Komutlar BatchMode SSH (sadece anahtar) ile çalışır. Satır sonu karakterleri reddedilir; komut uzunluğu 2000 karakter, çıktı 100KB ile sınırlıdır. Her test ve çalıştırma loglanır.",
		) ?>
	</p>
	<?php endif; ?>

</div>
