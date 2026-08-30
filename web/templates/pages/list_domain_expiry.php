<?php
$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back js-button-back" href="/list/web/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml(__tr("Back to Web", "Web Sitelerine Dön")) ?>
			</a>
			<a href="/add/web/" class="button button-secondary">
				<i class="fas fa-circle-plus icon-green"></i><?= tohtml( _("Add Web Domain")) ?>
			</a>
			<form method="post" action="/list/domain-expiry/" style="display:inline;" onsubmit="const b = this.querySelector('button'); b.disabled=true; b.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> ' + ('<?= $is_tr ? "Taranıyor..." : "Scanning..." ?>');">
				<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
				<input type="hidden" name="scan_expirations" value="1">
				<button type="submit" class="button button-secondary" title="<?= tohtml($is_tr ? "Tüm domain sürelerini tara, süresi dolanları askıya al ve bildirim gönder" : "Scan all expirations") ?>">
					<i class="fas fa-arrows-rotate icon-purple"></i><?= tohtml($is_tr ? "Şimdi Tara & Denetle" : "Scan & Check Now") ?>
				</button>
			</form>
		</div>
		<div class="toolbar-right">
			<span style="font-size: 11px; color: var(--color-text-muted, #94a3b8);">
				<i class="fas fa-shield-halved icon-blue"></i>
				<?= tohtml($is_tr ? "Admin Lisans & Süre Yönetim Portalı" : "Admin Domain Lifecycle Portal") ?>
			</span>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container" style="max-width: 1400px; padding: 20px 24px; margin: 0 auto;">

	<!-- Page Title Header -->
	<div style="margin-bottom: 22px;">
		<h1 style="font-size: 1.5rem; font-weight: 700; margin: 0 0 6px 0; color: var(--color-text, #fff); display: flex; align-items: center; gap: 10px;">
			<i class="fas fa-hourglass-half" style="color: var(--icon-color-orange, #f97316);"></i>
			<?= tohtml($is_tr ? "Domain Geçerlilik, Lisans & Süre Takip Paneli" : "Domain Validity & Expiry Dashboard") ?>
		</h1>
		<p style="color: var(--color-text-muted, #94a3b8); margin: 0; font-size: 13px; line-height: 1.5;">
			<?= tohtml($is_tr ? "Tüm web sitelerinizin lisans sürelerini, bitiş tarihlerini ve yayın durumlarını buradan yönetebilirsiniz. Süresi dolan siteler otomatik askıya alınır; süre tanımlandığında veya açıldığında anında yayına girer." : "Manage domain subscription lifetimes, remaining days, and automated suspension on expiry.") ?>
		</p>
	</div>

	<?php show_alert_message($_SESSION); ?>

	<!-- Top Stats Overview Grid -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 15px; margin-bottom: 24px;">
		
		<!-- Stat 1: Total Domains -->
		<div style="background: var(--color-background, #282828); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<div style="font-size:11px; text-transform:uppercase; font-weight:700; color: var(--color-text-muted, #94a3b8); letter-spacing:0.5px;">
						<?= tohtml($is_tr ? "Toplam Domain" : "Total Domains") ?>
					</div>
					<h2 style="margin:6px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--color-text, #fff);">
						<?= (int)($summary["total_domains"] ?? count($domains)) ?>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(56, 189, 248, 0.12); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-globe fa-lg" style="color:var(--icon-color-blue, #38bdf8);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:11.5px; color:var(--color-text-muted, #94a3b8);">
				🌐 <?= tohtml($is_tr ? "Kayıtlı tüm web siteleri" : "All registered domains") ?>
			</div>
		</div>

		<!-- Stat 2: Active Sites -->
		<div style="background: var(--color-background, #282828); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<div style="font-size:11px; text-transform:uppercase; font-weight:700; color: var(--color-text-muted, #94a3b8); letter-spacing:0.5px;">
						<?= tohtml($is_tr ? "Aktif Siteler" : "Active Sites") ?>
					</div>
					<h2 style="margin:6px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--icon-color-green, #22c55e);">
						<?= (int)($summary["active_domains"] ?? 0) ?>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(34, 197, 94, 0.12); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-circle-check fa-lg" style="color:var(--icon-color-green, #22c55e);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:11.5px; color:var(--color-text-muted, #94a3b8);">
				🟢 <?= tohtml($is_tr ? "Lisansı geçerli ve yayında" : "Active subscription & live") ?>
			</div>
		</div>

		<!-- Stat 3: Expiring Soon (< 30 Days) -->
		<div style="background: var(--color-background, #282828); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<div style="font-size:11px; text-transform:uppercase; font-weight:700; color: var(--color-text-muted, #94a3b8); letter-spacing:0.5px;">
						<?= tohtml($is_tr ? "Yakında Dolacak (<30G)" : "Expiring Soon (<30d)") ?>
					</div>
					<h2 style="margin:6px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--icon-color-orange, #f97316);">
						<?= (int)($summary["expiring_soon"] ?? 0) ?>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(249, 115, 22, 0.12); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-clock fa-lg" style="color:var(--icon-color-orange, #f97316);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:11.5px; color:var(--color-text-muted, #94a3b8);">
				⏳ <?= tohtml($is_tr ? "30 günden az süresi kalanlar" : "Less than 30 days left") ?>
			</div>
		</div>

		<!-- Stat 4: Expired / Suspended -->
		<div style="background: var(--color-background, #282828); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<div style="font-size:11px; text-transform:uppercase; font-weight:700; color: var(--color-text-muted, #94a3b8); letter-spacing:0.5px;">
						<?= tohtml($is_tr ? "Süresi Dolan / Askıda" : "Expired / Suspended") ?>
					</div>
					<h2 style="margin:6px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--color-danger, #ef4444);">
						<?= (int)($summary["expired"] ?? 0) ?>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(239, 68, 68, 0.12); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-ban fa-lg" style="color:var(--color-danger, #ef4444);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:11.5px; color:var(--color-text-muted, #94a3b8);">
				🔴 <?= tohtml($is_tr ? "Süresi bittiği için kapatılanlar" : "Suspended due to expiry") ?>
			</div>
		</div>

		<!-- Stat 5: Unlimited Lifetime -->
		<div style="background: var(--color-background, #282828); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 16px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
			<div style="display:flex; justify-content:space-between; align-items:center;">
				<div>
					<div style="font-size:11px; text-transform:uppercase; font-weight:700; color: var(--color-text-muted, #94a3b8); letter-spacing:0.5px;">
						<?= tohtml($is_tr ? "Sınırsız Lisans" : "Unlimited Lifetime") ?>
					</div>
					<h2 style="margin:6px 0 0 0; font-size:1.6rem; font-weight:bold; color:var(--icon-color-purple, #a855f7);">
						<?= (int)($summary["unlimited"] ?? 0) ?>
					</h2>
				</div>
				<div style="width:40px; height:40px; border-radius:8px; background:rgba(168, 85, 247, 0.12); display:flex; align-items:center; justify-content:center;">
					<i class="fas fa-infinity fa-lg" style="color:var(--icon-color-purple, #a855f7);"></i>
				</div>
			</div>
			<div style="margin-top:10px; font-size:11.5px; color:var(--color-text-muted, #94a3b8);">
				♾️ <?= tohtml($is_tr ? "Bitiş süresi olmayan kalıcı siteler" : "No expiry restriction") ?>
			</div>
		</div>
	</div>

	<!-- Search & Filter Controls Card -->
	<div style="background: var(--color-background, #282828); border: 1px solid var(--border-color, #334155); border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
		<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
			<div style="flex: 1; min-width: 260px;">
				<input type="text" id="domain-search-input" class="form-control" placeholder="<?= tohtml($is_tr ? "🔍 Domain veya kullanıcı ara…" : "🔍 Search domain or user…") ?>" onkeyup="searchDomains();" style="width: 100%; font-size: 13.5px; padding: 8px 12px;">
			</div>
			<div style="display: flex; gap: 10px; align-items: center;">
				<label class="form-label u-text-bold" style="margin: 0; font-size: 12px; white-space: nowrap; color: var(--color-text, #fff);"><?= tohtml($is_tr ? "Durum Filtresi:" : "Filter Status:") ?></label>
				<select id="status-filter-select" class="form-select" onchange="filterDomainsBySelect();" style="font-size: 12.5px; padding: 6px 12px;">
					<option value="all"><?= tohtml($is_tr ? "Tüm Siteler" : "All Sites") ?> (<?= count($domains) ?>)</option>
					<option value="active">🟢 <?= tohtml($is_tr ? "Aktif Siteler" : "Active") ?></option>
					<option value="expiring_soon">🟠 <?= tohtml($is_tr ? "Yakında Dolacak (<30G)" : "Expiring Soon") ?></option>
					<option value="expired">🔴 <?= tohtml($is_tr ? "Süresi Dolanlar (Askıda)" : "Expired") ?></option>
					<option value="unlimited">🟣 <?= tohtml($is_tr ? "Sınırsız Lisanslar" : "Unlimited") ?></option>
				</select>
			</div>
		</div>
	</div>

	<!-- Main Domain Expiry Table Container -->
	<form method="post" action="/list/domain-expiry/" id="bulk-form">
		<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
		<input type="hidden" name="bulk_expiry_action" value="1">

		<div style="background: var(--color-background, #282828); border: 1px solid var(--border-color, #334155); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
			
			<div style="overflow-x: auto; width: 100%;">
				<table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
					<thead>
						<tr style="background: rgba(0, 0, 0, 0.25); border-bottom: 2px solid var(--border-color, #334155);">
							<th style="padding: 12px 14px; width: 42px; text-align: center;">
								<input type="checkbox" id="select-all" onclick="toggleSelectAll(this)" style="cursor: pointer; transform: scale(1.1);">
							</th>
							<th style="padding: 12px 14px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Domain Adı & Sahibi" : "Domain & Owner") ?></th>
							<th style="padding: 12px 14px; width: 130px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Ekleme Tarihi" : "Created Date") ?></th>
							<th style="padding: 12px 14px; width: 130px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Bitiş Tarihi" : "Expiry Date") ?></th>
							<th style="padding: 12px 14px; width: 170px; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Kalan Süre & Durum" : "Remaining & Status") ?></th>
							<th style="padding: 12px 14px; width: 130px; text-align: center; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Yayın Durumu" : "Site Status") ?></th>
							<th style="padding: 12px 16px; width: 260px; text-align: right; color: var(--color-text-muted, #94a3b8); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Hızlı Süre Uzatma" : "Quick Action") ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($domains)): ?>
							<tr>
								<td colspan="7" style="padding: 40px; text-align: center; color: var(--color-text-muted, #94a3b8);">
									<i class="fas fa-circle-info fa-2x u-mb10" style="display:block; opacity:0.5;"></i>
									<?= tohtml($is_tr ? "Henüz kayıtlı web sitesi bulunamadı." : "No web domains configured yet.") ?>
								</td>
							</tr>
						<?php else: ?>
							<?php foreach ($domains as $d):
								$d_name = $d["domain"] ?? "";
								$d_user = $d["user"] ?? $user_plain;
								$d_created = $d["created_date"] ?? "-";
								$d_created_time = $d["created_time"] ?? "";
								$d_expiry = $d["expiry_date"] ?? "1y";
								$d_days = (int)($d["days_left"] ?? 0);
								$d_status = $d["status"] ?? "active";
								$d_suspended = $d["suspended"] ?? "no";
							?>
								<tr class="domain-row" data-domain="<?= tohtml(strtolower($d_name)) ?>" data-user="<?= tohtml(strtolower($d_user)) ?>" data-status="<?= tohtml($d_status) ?>" style="border-bottom: 1px solid var(--border-color, #334155); transition: background 0.15s;">
									
									<!-- Checkbox -->
									<td style="padding: 12px 14px; text-align: center; vertical-align: middle;">
										<input type="checkbox" name="domains[]" value="<?= tohtml($d_user . ':' . $d_name) ?>" class="domain-checkbox" onclick="updateBulkCounter()" style="cursor: pointer; transform: scale(1.1);">
									</td>

									<!-- Domain & Owner -->
									<td style="padding: 12px 14px; vertical-align: middle;">
										<div style="display: flex; align-items: center; gap: 10px;">
											<i class="fas fa-earth-americas" style="font-size: 16px; color: <?= ($d_suspended === 'yes') ? 'var(--color-danger, #ef4444)' : 'var(--icon-color-blue, #38bdf8)' ?>; flex-shrink: 0;"></i>
											<div style="display: flex; flex-direction: column; gap: 2px; min-width: 0;">
												<div style="display: flex; align-items: center; gap: 6px;">
													<a href="/edit/web/?domain=<?= tohtml(urlencode($d_name)) ?>&user=<?= tohtml(urlencode($d_user)) ?>&token=<?= tohtml($_SESSION['token']) ?>" class="u-text-bold" style="color: var(--color-text, #fff); text-decoration: none; font-size: 13.5px;">
														<?= tohtml($d_name) ?>
													</a>
													<a href="http://<?= tohtml($d_name) ?>" target="_blank" rel="noopener" style="color: var(--color-text-muted, #94a3b8); font-size: 11px;" title="<?= tohtml($is_tr ? "Siteye Git" : "Visit") ?>">
														<i class="fas fa-arrow-up-right-from-square"></i>
													</a>
												</div>
												<?php if (($_SESSION["userContext"] ?? "") === "admin") { ?>
													<small style="color: var(--color-text-muted, #94a3b8); font-size: 11px;">
														<i class="fas fa-user u-mr5"></i><?= tohtml($d_user) ?>
													</small>
												<?php } ?>
											</div>
										</div>
									</td>

									<!-- Created Date -->
									<td style="padding: 12px 14px; vertical-align: middle; color: var(--color-text-muted, #94a3b8); font-size: 12.5px; white-space: nowrap;">
										<?= tohtml($d_created) ?>
									</td>

									<!-- Expiry Date -->
									<td style="padding: 12px 14px; vertical-align: middle; font-size: 13px; font-weight: 700; white-space: nowrap;">
										<?php if ($d_expiry === 'unlimited') { ?>
											<span style="color: var(--icon-color-purple, #a855f7);"><i class="fas fa-infinity u-mr5"></i><?= tohtml($is_tr ? "Süresiz" : "Unlimited") ?></span>
										<?php } else { ?>
											<span style="color: <?= ($d_status === 'expired') ? 'var(--color-danger, #ef4444)' : 'var(--color-text, #fff)' ?>;"><?= tohtml($d_expiry) ?></span>
										<?php } ?>
									</td>

									<!-- Remaining Days & Badge -->
									<td style="padding: 12px 14px; vertical-align: middle; white-space: nowrap;">
										<?php if ($d_status === 'unlimited') { ?>
											<span class="badge badge-purple" style="font-size: 11px; padding: 4px 8px;">
												<i class="fas fa-infinity u-mr5"></i><?= tohtml($is_tr ? "Sınırsız Lisans" : "Unlimited") ?>
											</span>
										<?php } elseif ($d_status === 'expired') { ?>
											<span class="badge badge-danger" style="font-size: 11px; padding: 4px 8px;">
												<i class="fas fa-circle-xmark u-mr5"></i><?= tohtml($is_tr ? "Süresi Doldu" : "Expired") ?> (<?= abs($d_days) ?>g <?= tohtml($is_tr ? "önce" : "ago") ?>)
											</span>
										<?php } elseif ($d_status === 'expiring_soon') { ?>
											<span class="badge badge-warning" style="font-size: 11px; padding: 4px 8px;">
												<i class="fas fa-clock u-mr5"></i><?= tohtml($d_days) ?> <?= tohtml($is_tr ? "gün kaldı" : "days left") ?>
											</span>
										<?php } else { ?>
											<span class="badge badge-secondary" style="font-size: 11px; padding: 4px 8px; color: var(--icon-color-green, #22c55e);">
												<i class="fas fa-circle-check u-mr5"></i><?= tohtml($d_days) ?> <?= tohtml($is_tr ? "gün kaldı" : "days left") ?>
											</span>
										<?php } ?>
									</td>

									<!-- Website Live/Suspended Status & Direct Open/Close Toggle -->
									<td style="padding: 12px 14px; vertical-align: middle; text-align: center; white-space: nowrap;">
										<?php if ($d_suspended === 'yes') { ?>
											<button type="button" onclick="toggleSiteStatus('<?= tohtml($d_user) ?>', '<?= tohtml($d_name) ?>', 'unsuspend')" class="button button-secondary" style="font-size: 11px; padding: 4px 9px; color: var(--icon-color-green, #22c55e); border-color: rgba(34, 197, 94, 0.4);" title="<?= tohtml($is_tr ? "Siteyi ve Yönlendirmeyi Aç / Yayına Al" : "Open / Unsuspend Site") ?>">
												<i class="fas fa-play u-mr5"></i><?= tohtml($is_tr ? "Siteyi Aç" : "Open Site") ?>
											</button>
										<?php } else { ?>
											<button type="button" onclick="if(confirm('<?= tohtml($is_tr ? "Siteyi ve yönlendirmeyi durdurup kapatmak istediğinize emin misiniz?" : "Are you sure you want to stop/suspend this site?") ?>')) toggleSiteStatus('<?= tohtml($d_user) ?>', '<?= tohtml($d_name) ?>', 'suspend')" class="button button-secondary" style="font-size: 11px; padding: 4px 9px; color: var(--color-danger, #ef4444); border-color: rgba(239, 68, 68, 0.4);" title="<?= tohtml($is_tr ? "Siteyi ve Yönlendirmeyi Kapat / Askıya Al" : "Stop / Suspend Site") ?>">
												<i class="fas fa-pause u-mr5"></i><?= tohtml($is_tr ? "Siteyi Kapat" : "Close Site") ?>
											</button>
										<?php } ?>
									</td>

									<!-- Quick Duration Actions -->
									<td style="padding: 12px 16px; vertical-align: middle; text-align: right; white-space: nowrap;">
										<div style="display: inline-flex; gap: 4px; align-items: center; justify-content: flex-end;">
											<button type="button" onclick="quickExtend('<?= tohtml($d_user) ?>', '<?= tohtml($d_name) ?>', '3d')" class="button button-secondary" style="font-size: 11px; padding: 4px 7px;" title="<?= tohtml($is_tr ? "3 Günlük Demo Ekle" : "+3 Days Trial") ?>">
												+3G
											</button>
											<button type="button" onclick="quickExtend('<?= tohtml($d_user) ?>', '<?= tohtml($d_name) ?>', '1m')" class="button button-secondary" style="font-size: 11px; padding: 4px 7px;" title="<?= tohtml($is_tr ? "1 Ay Uzat" : "+1 Month") ?>">
												+1A
											</button>
											<button type="button" onclick="quickExtend('<?= tohtml($d_user) ?>', '<?= tohtml($d_name) ?>', '1y')" class="button button-primary" style="font-size: 11px; padding: 4px 9px; font-weight: 700;" title="<?= tohtml($is_tr ? "1 Yıl Standart Satış Uzat" : "+1 Year Standard") ?>">
												+1Y
											</button>
											<button type="button" onclick="quickExtend('<?= tohtml($d_user) ?>', '<?= tohtml($d_name) ?>', '5y')" class="button button-secondary" style="font-size: 11px; padding: 4px 7px;" title="<?= tohtml($is_tr ? "5 Yıl Uzat" : "+5 Years") ?>">
												+5Y
											</button>
											<button type="button" onclick="quickExtend('<?= tohtml($d_user) ?>', '<?= tohtml($d_name) ?>', 'unlimited')" class="button button-secondary" style="font-size: 11px; padding: 4px 7px;" title="<?= tohtml($is_tr ? "Sınırsız / Süresiz Yap" : "Set Unlimited") ?>">
												♾️
											</button>
											<button type="button" onclick="openDateModal('<?= tohtml($d_user) ?>', '<?= tohtml($d_name) ?>', '<?= tohtml($d_expiry) ?>')" class="button button-secondary" style="font-size: 11px; padding: 4px 7px;" title="<?= tohtml($is_tr ? "Özel Tarih Seç" : "Custom Date") ?>">
												<i class="fas fa-calendar-days"></i>
											</button>
										</div>
									</td>

								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<!-- Table Footer Bulk Actions -->
			<div style="padding: 12px 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: rgba(0,0,0,0.25); border-top: 1px solid var(--border-color, #334155);">
				<div style="font-size: 12.5px; color: var(--color-text-muted, #94a3b8);">
					<span id="selected-count" class="u-text-bold" style="color: var(--color-text, #fff);">0</span> <?= tohtml($is_tr ? "domain seçildi" : "domains selected") ?>
				</div>
				<div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
					<select name="bulk_action_type" id="bulk-action-type" class="form-select" style="font-size: 12px; height: 32px; padding: 2px 8px;" onchange="document.getElementById('bulk-date-wrap').style.display = (this.value === 'custom') ? 'inline-block' : 'none';">
						<option value="1y">📅 <?= tohtml($is_tr ? "Seçilenleri +1 Yıl Uzat (Standart)" : "+1 Year (Standard)") ?></option>
						<option value="3d">⏳ <?= tohtml($is_tr ? "Seçilenleri +3 Gün Uzat (Demo)" : "+3 Days (Trial)") ?></option>
						<option value="1m">🗓️ <?= tohtml($is_tr ? "Seçilenleri +1 Ay Uzat" : "+1 Month") ?></option>
						<option value="5y">📅 <?= tohtml($is_tr ? "Seçilenleri +5 Yıl Uzat" : "+5 Years") ?></option>
						<option value="unlimited">♾️ <?= tohtml($is_tr ? "Seçilenleri Sınırsız Yap" : "Set Unlimited") ?></option>
						<option value="custom">✏️ <?= tohtml($is_tr ? "Özel Tarihe Ayarla…" : "Set Custom Date…") ?></option>
						<option value="unsuspend">🟢 <?= tohtml($is_tr ? "Seçilen Siteleri Aç (Yayına Al)" : "Open Selected Sites") ?></option>
						<option value="suspend">🔴 <?= tohtml($is_tr ? "Seçilen Siteleri Kapat (Durdur)" : "Close Selected Sites") ?></option>
					</select>
					<div id="bulk-date-wrap" style="display: none;">
						<input type="date" name="bulk_custom_date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" class="form-control" style="font-size: 12px; height: 32px; max-width: 140px;">
					</div>
					<button type="submit" class="button button-primary" style="font-size: 12px; padding: 4px 12px;">
						<i class="fas fa-arrow-right u-mr5"></i><?= tohtml($is_tr ? "Uygula" : "Apply") ?>
					</button>
				</div>
			</div>

		</div>
	</form>

</div>

<!-- Hidden Standalone Site Status Toggle Form -->
<form id="single-toggle-form" method="post" action="/list/domain-expiry/" style="display:none;">
	<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
	<input type="hidden" name="toggle_domain_status" value="1">
	<input type="hidden" name="toggle_user" id="st-user" value="">
	<input type="hidden" name="toggle_domain" id="st-domain" value="">
	<input type="hidden" name="toggle_action" id="st-action" value="">
</form>

<!-- Hidden Standalone Single Extend Form -->
<form id="single-extend-form" method="post" action="/list/domain-expiry/" style="display:none;">
	<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
	<input type="hidden" name="update_single_expiry" value="1">
	<input type="hidden" name="exp_user" id="se-user" value="">
	<input type="hidden" name="exp_domain" id="se-domain" value="">
	<input type="hidden" name="exp_value" id="se-value" value="">
	<input type="hidden" name="custom_date" id="se-custom-date" value="">
</form>

<!-- Modal: Custom Date Picker -->
<div id="custom-date-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; justify-content:center; align-items:center;" onclick="if(event.target===this) closeDateModal();">
	<div class="card" style="max-width:420px; width:92%; border-radius:8px; padding:24px; border:1px solid var(--border-color, #334155); background:var(--color-background, #282828); box-shadow:0 10px 30px rgba(0,0,0,0.5);">
		<h3 style="margin:0 0 10px 0; font-size:16px; font-weight:700; color:var(--color-text, #fff); display:flex; align-items:center; gap:8px;">
			<i class="fas fa-calendar-days icon-blue"></i>
			<?= tohtml($is_tr ? "Özel Bitiş Tarihi Tanımla" : "Set Custom Expiry Date") ?>
		</h3>
		<p style="color:var(--color-text-muted, #94a3b8); margin:0 0 16px 0; font-size:13px;">
			<strong id="modal-domain-label" style="color:var(--color-text, #fff);"></strong> <?= tohtml($is_tr ? "domaini için yeni lisans bitiş tarihini seçin:" : "select new expiration date:") ?>
		</p>
		<div style="margin-bottom: 20px;">
			<label class="form-label u-text-bold" style="display:block; font-size:12px; margin-bottom:5px; color:var(--color-text, #fff);"><?= tohtml($is_tr ? "Bitiş Tarihi (YYYY-MM-DD)" : "Expiry Date") ?></label>
			<input type="date" id="modal-date-input" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" class="form-control" style="width:100%; font-size:14px;">
		</div>
		<div style="display:flex; justify-content:flex-end; gap:10px;">
			<button type="button" onclick="closeDateModal()" class="button button-secondary" style="font-size:12.5px;"><?= tohtml($is_tr ? "İptal" : "Cancel") ?></button>
			<button type="button" onclick="applyModalDate()" class="button button-primary" style="font-size:12.5px;"><?= tohtml($is_tr ? "Kaydet ve Uygula" : "Save & Apply") ?></button>
		</div>
	</div>
</div>

<script>
let activeModalUser = '';
let activeModalDomain = '';

function toggleSiteStatus(user, domain, action) {
	document.getElementById('st-user').value = user;
	document.getElementById('st-domain').value = domain;
	document.getElementById('st-action').value = action;
	document.getElementById('single-toggle-form').submit();
}

function quickExtend(user, domain, value) {
	document.getElementById('se-user').value = user;
	document.getElementById('se-domain').value = domain;
	document.getElementById('se-value').value = value;
	document.getElementById('se-custom-date').value = '';
	document.getElementById('single-extend-form').submit();
}

function openDateModal(user, domain, currentExpiry) {
	activeModalUser = user;
	activeModalDomain = domain;
	document.getElementById('modal-domain-label').innerText = domain;
	if (currentExpiry && currentExpiry !== 'unlimited' && currentExpiry.length === 10) {
		document.getElementById('modal-date-input').value = currentExpiry;
	}
	document.getElementById('custom-date-modal').style.display = 'flex';
}

function closeDateModal() {
	document.getElementById('custom-date-modal').style.display = 'none';
}

function applyModalDate() {
	const chosenDate = document.getElementById('modal-date-input').value;
	if (!chosenDate) return;
	document.getElementById('se-user').value = activeModalUser;
	document.getElementById('se-domain').value = activeModalDomain;
	document.getElementById('se-value').value = 'custom';
	document.getElementById('se-custom-date').value = chosenDate;
	document.getElementById('single-extend-form').submit();
}

function toggleSelectAll(master) {
	const checkboxes = document.querySelectorAll('.domain-checkbox');
	checkboxes.forEach(cb => {
		const row = cb.closest('.domain-row');
		if (row && row.style.display !== 'none') {
			cb.checked = master.checked;
		}
	});
	updateBulkCounter();
}

function updateBulkCounter() {
	const checked = document.querySelectorAll('.domain-checkbox:checked').length;
	document.getElementById('selected-count').innerText = checked;
}

function filterDomainsBySelect() {
	const status = document.getElementById('status-filter-select').value;
	const rows = document.querySelectorAll('.domain-row');
	rows.forEach(row => {
		const rStatus = row.getAttribute('data-status');
		if (status === 'all' || rStatus === status) {
			row.style.display = '';
		} else {
			row.style.display = 'none';
		}
	});
	updateBulkCounter();
}

function searchDomains() {
	const query = document.getElementById('domain-search-input').value.toLowerCase().trim();
	const status = document.getElementById('status-filter-select').value;
	const rows = document.querySelectorAll('.domain-row');
	rows.forEach(row => {
		const dName = row.getAttribute('data-domain') || '';
		const dUser = row.getAttribute('data-user') || '';
		const rStatus = row.getAttribute('data-status') || '';
		const matchStatus = (status === 'all' || rStatus === status);
		const matchQuery = (dName.includes(query) || dUser.includes(query));
		if (matchStatus && matchQuery) {
			row.style.display = '';
		} else {
			row.style.display = 'none';
		}
	});
	updateBulkCounter();
}
</script>
