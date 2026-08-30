<?php
$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
?>

<!-- Begin toolbar -->
<div class="toolbar">
	<div class="toolbar-inner">
		<div class="toolbar-buttons">
			<a class="button button-secondary button-back" href="/list/web/">
				<i class="fas fa-arrow-left icon-blue"></i><?= tohtml( _("Back")) ?>
			</a>
			<a href="/add/web/" class="button button-secondary">
				<i class="fas fa-circle-plus icon-green"></i><?= tohtml( _("Add Web Domain")) ?>
			</a>
		</div>
		<div class="toolbar-buttons">
			<form method="post" action="/list/domain-expiry/" style="display:inline-block; margin:0;">
				<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
				<input type="hidden" name="scan_expirations" value="1">
				<button type="submit" class="button button-secondary" title="<?= tohtml($is_tr ? "Tüm domain sürelerini tara, süresi dolanları askıya al ve bildirim gönder" : "Scan all expirations") ?>">
					<i class="fas fa-arrows-rotate icon-purple"></i><?= tohtml($is_tr ? "Şimdi Tara & Denetle" : "Scan & Check Now") ?>
				</button>
			</form>
		</div>
	</div>
</div>
<!-- End toolbar -->

<div class="container" style="max-width: 1400px; padding: 20px 15px;">

	<!-- Page Heading & Notice -->
	<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
		<div>
			<h1 style="font-size: 22px; font-weight: 700; margin: 0 0 6px 0; color: #1e293b; display: flex; align-items: center; gap: 10px;">
				<i class="fas fa-hourglass-half" style="color: #3b82f6;"></i>
				<?= tohtml($is_tr ? "Domain Geçerlilik, Lisans & Süre Takip Paneli" : "Domain Validity & Expiry Dashboard") ?>
			</h1>
			<p style="margin: 0; color: #64748b; font-size: 13.5px; line-height: 1.5;">
				<?= tohtml($is_tr ? "Tüm web sitelerinizin eklenme tarihi, kalan gün süreleri ve abonelik durumları. Süresi dolan siteler ve yönlendirmeleri otomatik durdurulur/askıya alınır; süre tanımlandığında anında tekrar açılır." : "Manage domain subscription lifetimes, remaining days, and automated suspension on expiry.") ?>
			</p>
		</div>
	</div>

	<?php show_alert_message($_SESSION); ?>

	<!-- 5 Summary KPI Cards -->
	<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; margin-bottom: 24px;">
		<!-- 1. Total Domains -->
		<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 14px;">
			<div style="width: 44px; height: 44px; border-radius: 8px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 20px;">
				<i class="fas fa-globe"></i>
			</div>
			<div>
				<small style="color: #64748b; font-weight: 600; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Toplam Domain" : "Total Domains") ?></small>
				<div style="font-size: 22px; font-weight: 800; color: #1e293b; line-height: 1.2;"><?= (int)($summary["total_domains"] ?? count($domains)) ?></div>
			</div>
		</div>

		<!-- 2. Active Domains -->
		<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 14px;">
			<div style="width: 44px; height: 44px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 20px;">
				<i class="fas fa-circle-check"></i>
			</div>
			<div>
				<small style="color: #64748b; font-weight: 600; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Aktif Siteler" : "Active Sites") ?></small>
				<div style="font-size: 22px; font-weight: 800; color: #059669; line-height: 1.2;"><?= (int)($summary["active_domains"] ?? 0) ?></div>
			</div>
		</div>

		<!-- 3. Expiring Soon (< 30 Days) -->
		<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 14px;">
			<div style="width: 44px; height: 44px; border-radius: 8px; background: #fffbeb; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 20px;">
				<i class="fas fa-clock"></i>
			</div>
			<div>
				<small style="color: #64748b; font-weight: 600; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Yakında Dolacak (<30G)" : "Expiring Soon (<30d)") ?></small>
				<div style="font-size: 22px; font-weight: 800; color: #d97706; line-height: 1.2;"><?= (int)($summary["expiring_soon"] ?? 0) ?></div>
			</div>
		</div>

		<!-- 4. Expired / Suspended -->
		<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 14px;">
			<div style="width: 44px; height: 44px; border-radius: 8px; background: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 20px;">
				<i class="fas fa-circle-xmark"></i>
			</div>
			<div>
				<small style="color: #64748b; font-weight: 600; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Süresi Dolan / Askıda" : "Expired / Suspended") ?></small>
				<div style="font-size: 22px; font-weight: 800; color: #dc2626; line-height: 1.2;"><?= (int)($summary["expired"] ?? 0) ?></div>
			</div>
		</div>

		<!-- 5. Unlimited -->
		<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 14px;">
			<div style="width: 44px; height: 44px; border-radius: 8px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 20px;">
				<i class="fas fa-infinity"></i>
			</div>
			<div>
				<small style="color: #64748b; font-weight: 600; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;"><?= tohtml($is_tr ? "Sınırsız Lisans" : "Unlimited Lifetime") ?></small>
				<div style="font-size: 22px; font-weight: 800; color: #7c3aed; line-height: 1.2;"><?= (int)($summary["unlimited"] ?? 0) ?></div>
			</div>
		</div>
	</div>

	<!-- Main Table Container -->
	<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); overflow: hidden;">
		
		<!-- Table Header Toolbar / Filter & Search -->
		<div style="padding: 14px 18px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
			<!-- Filter Tabs -->
			<div style="display: flex; gap: 6px; flex-wrap: wrap;" id="filter-tabs">
				<button type="button" class="btn-filter active" onclick="filterTable('all', this)" style="padding: 5px 12px; border-radius: 20px; font-size: 12.5px; font-weight: 600; cursor: pointer; border: 1px solid #cbd5e1; background: #1e293b; color: #fff;">
					<?= tohtml($is_tr ? "Tümü" : "All") ?> (<?= count($domains) ?>)
				</button>
				<button type="button" class="btn-filter" onclick="filterTable('active', this)" style="padding: 5px 12px; border-radius: 20px; font-size: 12.5px; font-weight: 600; cursor: pointer; border: 1px solid #cbd5e1; background: #fff; color: #334155;">
					🟢 <?= tohtml($is_tr ? "Aktif" : "Active") ?>
				</button>
				<button type="button" class="btn-filter" onclick="filterTable('expiring_soon', this)" style="padding: 5px 12px; border-radius: 20px; font-size: 12.5px; font-weight: 600; cursor: pointer; border: 1px solid #cbd5e1; background: #fff; color: #334155;">
					🟠 <?= tohtml($is_tr ? "Yakında Dolacak" : "Expiring Soon") ?>
				</button>
				<button type="button" class="btn-filter" onclick="filterTable('expired', this)" style="padding: 5px 12px; border-radius: 20px; font-size: 12.5px; font-weight: 600; cursor: pointer; border: 1px solid #cbd5e1; background: #fff; color: #334155;">
					🔴 <?= tohtml($is_tr ? "Süresi Dolanlar" : "Expired") ?>
				</button>
				<button type="button" class="btn-filter" onclick="filterTable('unlimited', this)" style="padding: 5px 12px; border-radius: 20px; font-size: 12.5px; font-weight: 600; cursor: pointer; border: 1px solid #cbd5e1; background: #fff; color: #334155;">
					🟣 <?= tohtml($is_tr ? "Sınırsız" : "Unlimited") ?>
				</button>
			</div>

			<!-- Live Search -->
			<div style="position: relative; min-width: 240px;">
				<i class="fas fa-magnifying-glass" style="position: absolute; left: 10px; top: 10px; color: #94a3b8; font-size: 13px;"></i>
				<input type="text" id="domain-search-input" onkeyup="searchDomains()" placeholder="<?= tohtml($is_tr ? "Domain veya kullanıcı ara…" : "Search domains or users…") ?>" class="form-control" style="padding-left: 32px; font-size: 13px; height: 34px; border-radius: 6px;">
			</div>
		</div>

		<!-- Table Form for Bulk Operations -->
		<form method="post" action="/list/domain-expiry/" id="bulk-form">
			<input type="hidden" name="token" value="<?= tohtml($_SESSION["token"]) ?>">
			<input type="hidden" name="bulk_expiry_action" value="1">

			<div style="overflow-x: auto;">
				<table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
					<thead>
						<tr style="background: #f1f5f9; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; white-space: nowrap;">
							<th style="padding: 12px 16px; width: 40px;">
								<input type="checkbox" id="select-all" onclick="toggleSelectAll(this)" style="cursor: pointer;">
							</th>
							<th style="padding: 12px 16px;"><?= tohtml($is_tr ? "Domain Adı" : "Domain Name") ?></th>
							<?php if (($_SESSION["userContext"] ?? "") === "admin") { ?>
								<th style="padding: 12px 16px;"><?= tohtml($is_tr ? "Sahibi" : "Owner") ?></th>
							<?php } ?>
							<th style="padding: 12px 16px;"><?= tohtml($is_tr ? "Ekleme Tarihi" : "Creation Date") ?></th>
							<th style="padding: 12px 16px;"><?= tohtml($is_tr ? "Bitiş Tarihi" : "Expiration Date") ?></th>
							<th style="padding: 12px 16px;"><?= tohtml($is_tr ? "Kalan Süre & Durum" : "Remaining & Status") ?></th>
							<th style="padding: 12px 16px;"><?= tohtml($is_tr ? "Yayın / Yönlendirme" : "Routing") ?></th>
							<th style="padding: 12px 16px; text-align: right;"><?= tohtml($is_tr ? "Hızlı Süre Uzatma" : "Quick Extend") ?></th>
						</tr>
					</thead>
					<tbody id="domain-table-body">
						<?php if (empty($domains)): ?>
							<tr>
								<td colspan="8" style="text-align: center; padding: 36px 16px; color: #94a3b8;">
									<i class="fas fa-globe fa-2x u-mb10" style="display: block; opacity: 0.5;"></i>
									<?= tohtml($is_tr ? "Henüz kayıtlı web domaini bulunmuyor." : "No web domains found.") ?>
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
							<tr class="domain-row" data-status="<?= tohtml($d_status) ?>" data-domain="<?= tohtml(strtolower($d_name)) ?>" data-user="<?= tohtml(strtolower($d_user)) ?>" style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
								<td style="padding: 12px 16px;">
									<input type="checkbox" name="domains[]" value="<?= tohtml($d_user . ':' . $d_name) ?>" class="domain-checkbox" onclick="updateBulkCounter()" style="cursor: pointer;">
								</td>
								<td style="padding: 12px 16px; font-weight: 700;">
									<div style="display: flex; align-items: center; gap: 8px;">
										<i class="fas fa-earth-americas" style="color: <?= ($d_suspended === 'yes') ? '#dc2626' : '#3b82f6' ?>;"></i>
										<a href="/edit/web/?domain=<?= tohtml(urlencode($d_name)) ?>&user=<?= tohtml(urlencode($d_user)) ?>&token=<?= tohtml($_SESSION['token']) ?>" style="color: #1e293b; text-decoration: none; font-size: 13.5px;" title="<?= tohtml($is_tr ? "Domaini Düzenle" : "Edit Domain") ?>">
											<?= tohtml($d_name) ?>
										</a>
										<a href="http://<?= tohtml($d_name) ?>" target="_blank" rel="noopener" style="color: #94a3b8; font-size: 11px;" title="<?= tohtml($is_tr ? "Siteyi Ziyaret Et" : "Visit Site") ?>">
											<i class="fas fa-arrow-up-right-from-square"></i>
										</a>
									</div>
								</td>
								<?php if (($_SESSION["userContext"] ?? "") === "admin") { ?>
									<td style="padding: 12px 16px; color: #475569; font-weight: 600;">
										<i class="fas fa-user-circle u-mr5" style="color: #94a3b8;"></i><?= tohtml($d_user) ?>
									</td>
								<?php } ?>
								<td style="padding: 12px 16px; color: #64748b;">
									<?= tohtml($d_created) ?> <small style="color: #94a3b8;"><?= tohtml($d_created_time) ?></small>
								</td>
								<td style="padding: 12px 16px; font-weight: 700;">
									<?php if ($d_expiry === 'unlimited') { ?>
										<span style="color: #7c3aed;"><i class="fas fa-infinity u-mr5"></i><?= tohtml($is_tr ? "Süresiz" : "Unlimited") ?></span>
									<?php } else { ?>
										<span style="color: <?= ($d_status === 'expired') ? '#dc2626' : '#1e293b' ?>;"><?= tohtml($d_expiry) ?></span>
									<?php } ?>
								</td>
								<td style="padding: 12px 16px;">
									<?php if ($d_status === 'unlimited') { ?>
										<span style="background: #ede9fe; color: #6d28d9; padding: 3px 9px; border-radius: 99px; font-weight: 700; font-size: 11.5px;">
											<i class="fas fa-infinity u-mr5"></i><?= tohtml($is_tr ? "Sınırsız Lisans" : "Unlimited") ?>
										</span>
									<?php } elseif ($d_status === 'expired') { ?>
										<span style="background: #fee2e2; color: #991b1b; padding: 3px 9px; border-radius: 99px; font-weight: 700; font-size: 11.5px;">
											<i class="fas fa-circle-xmark u-mr5"></i><?= tohtml($is_tr ? "Süresi Doldu" : "Expired") ?> (<?= abs($d_days) ?> <?= tohtml($is_tr ? "gün önce" : "days ago") ?>)
										</span>
									<?php } elseif ($d_status === 'expiring_soon') { ?>
										<span style="background: #fef3c7; color: #92400e; padding: 3px 9px; border-radius: 99px; font-weight: 700; font-size: 11.5px;">
											<i class="fas fa-clock u-mr5"></i><?= tohtml($d_days) ?> <?= tohtml($is_tr ? "gün kaldı (Kritik)" : "days left") ?>
										</span>
									<?php } else { ?>
										<span style="background: #dcfce7; color: #166534; padding: 3px 9px; border-radius: 99px; font-weight: 700; font-size: 11.5px;">
											<i class="fas fa-circle-check u-mr5"></i><?= tohtml($d_days) ?> <?= tohtml($is_tr ? "gün kaldı" : "days left") ?>
										</span>
									<?php } ?>
								</td>
								<td style="padding: 12px 16px;">
									<?php if ($d_suspended === 'yes') { ?>
										<span style="color: #dc2626; font-weight: 700; font-size: 12px;"><i class="fas fa-ban u-mr5"></i><?= tohtml($is_tr ? "Durduruldu / Askıda" : "Suspended") ?></span>
									<?php } else { ?>
										<span style="color: #16a34a; font-weight: 700; font-size: 12px;"><i class="fas fa-signal u-mr5"></i><?= tohtml($is_tr ? "Yayında" : "Live") ?></span>
									<?php } ?>
								</td>
								<td style="padding: 12px 16px; text-align: right; white-space: nowrap;">
									<div style="display: inline-flex; gap: 4px; align-items: center;">
										<button type="button" onclick="quickExtend('<?= tohtml($d_user) ?>', '<?= tohtml($d_name) ?>', '3d')" class="button button-secondary" style="font-size: 11px; padding: 3px 7px;" title="<?= tohtml($is_tr ? "3 Günlük Demo Ekle" : "+3 Days Trial") ?>">
											+3G
										</button>
										<button type="button" onclick="quickExtend('<?= tohtml($d_user) ?>', '<?= tohtml($d_name) ?>', '1m')" class="button button-secondary" style="font-size: 11px; padding: 3px 7px;" title="<?= tohtml($is_tr ? "1 Ay Uzat" : "+1 Month") ?>">
											+1A
										</button>
										<button type="button" onclick="quickExtend('<?= tohtml($d_user) ?>', '<?= tohtml($d_name) ?>', '1y')" class="button" style="font-size: 11px; padding: 3px 9px; background: #2563eb; color: #fff; font-weight: 700;" title="<?= tohtml($is_tr ? "1 Yıl Standart Satış Uzat" : "+1 Year Standard") ?>">
											+1Y
										</button>
										<button type="button" onclick="quickExtend('<?= tohtml($d_user) ?>', '<?= tohtml($d_name) ?>', '5y')" class="button button-secondary" style="font-size: 11px; padding: 3px 7px;" title="<?= tohtml($is_tr ? "5 Yıl Uzat" : "+5 Years") ?>">
											+5Y
										</button>
										<button type="button" onclick="quickExtend('<?= tohtml($d_user) ?>', '<?= tohtml($d_name) ?>', 'unlimited')" class="button button-secondary" style="font-size: 11px; padding: 3px 7px;" title="<?= tohtml($is_tr ? "Sınırsız / Süresiz Yap" : "Set Unlimited") ?>">
											♾️
										</button>
										<button type="button" onclick="openDateModal('<?= tohtml($d_user) ?>', '<?= tohtml($d_name) ?>', '<?= tohtml($d_expiry) ?>')" class="button button-secondary" style="font-size: 11px; padding: 3px 7px;" title="<?= tohtml($is_tr ? "Özel Tarih Seç" : "Custom Date") ?>">
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

			<!-- Bulk Action Toolbar Footer -->
			<div style="padding: 14px 18px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
				<div style="color: #64748b; font-size: 13px;">
					<span id="selected-count" style="font-weight: 700; color: #1e293b;">0</span> <?= tohtml($is_tr ? "domain seçildi" : "domains selected") ?>
				</div>
				<div style="display: flex; gap: 8px; align-items: center;">
					<select name="bulk_action_type" id="bulk-action-type" class="form-select" style="font-size: 12.5px; height: 34px; padding: 4px 10px;" onchange="document.getElementById('bulk-date-wrap').style.display = (this.value === 'custom') ? 'inline-block' : 'none';">
						<option value="1y">📅 <?= tohtml($is_tr ? "Seçilenleri +1 Yıl Uzat (Standart)" : "+1 Year (Standard)") ?></option>
						<option value="3d">⏳ <?= tohtml($is_tr ? "Seçilenleri +3 Gün Uzat (Demo)" : "+3 Days (Trial)") ?></option>
						<option value="1m">🗓️ <?= tohtml($is_tr ? "Seçilenleri +1 Ay Uzat" : "+1 Month") ?></option>
						<option value="5y">📅 <?= tohtml($is_tr ? "Seçilenleri +5 Yıl Uzat" : "+5 Years") ?></option>
						<option value="unlimited">♾️ <?= tohtml($is_tr ? "Seçilenleri Sınırsız Yap" : "Set Unlimited") ?></option>
						<option value="custom">✏️ <?= tohtml($is_tr ? "Özel Tarihe Ayarla…" : "Set Custom Date…") ?></option>
					</select>
					<div id="bulk-date-wrap" style="display: none;">
						<input type="date" name="bulk_custom_date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" class="form-control" style="font-size: 12.5px; height: 34px; max-width: 150px;">
					</div>
					<button type="submit" class="button" style="font-size: 12.5px; padding: 6px 14px;">
						<i class="fas fa-arrow-right u-mr5"></i><?= tohtml($is_tr ? "Uygula" : "Apply") ?>
					</button>
				</div>
			</div>
		</form>
	</div>

</div>

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
<div id="custom-date-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center;" onclick="if(event.target===this) closeDateModal();">
	<div style="background:#ffffff; max-width:420px; width:92%; border-radius:8px; padding:24px; box-shadow:0 10px 25px rgba(0,0,0,0.4);">
		<h3 style="margin:0 0 10px 0; font-size:17px; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:8px;">
			<i class="fas fa-calendar-days" style="color:#2563eb;"></i>
			<?= tohtml($is_tr ? "Özel Bitiş Tarihi Tanımla" : "Set Custom Expiry Date") ?>
		</h3>
		<p style="margin:0 0 16px 0; font-size:13px; color:#64748b;">
			<strong id="modal-domain-label" style="color:#1e293b;"></strong> <?= tohtml($is_tr ? "domaini için yeni lisans bitiş tarihini seçin:" : "select new expiration date:") ?>
		</p>
		<div style="margin-bottom: 20px;">
			<label style="display:block; font-size:12px; font-weight:700; color:#475569; margin-bottom:5px;"><?= tohtml($is_tr ? "Bitiş Tarihi (YYYY-MM-DD)" : "Expiry Date") ?></label>
			<input type="date" id="modal-date-input" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" class="form-control" style="width:100%; font-size:14px; padding:8px 12px;">
		</div>
		<div style="display:flex; justify-content:flex-end; gap:10px;">
			<button type="button" onclick="closeDateModal()" class="button button-secondary" style="font-size:13px;"><?= tohtml($is_tr ? "İptal" : "Cancel") ?></button>
			<button type="button" onclick="applyModalDate()" class="button" style="font-size:13px; background:#2563eb; color:#fff; font-weight:700;"><?= tohtml($is_tr ? "Kaydet ve Uygula" : "Save & Apply") ?></button>
		</div>
	</div>
</div>

<script>
let activeModalUser = '';
let activeModalDomain = '';

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
		const row = cb.closest('tr');
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

function filterTable(status, btn) {
	// Update active button
	document.querySelectorAll('.btn-filter').forEach(b => {
		b.style.background = '#fff';
		b.style.color = '#334155';
	});
	btn.style.background = '#1e293b';
	btn.style.color = '#fff';

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
	const rows = document.querySelectorAll('.domain-row');
	rows.forEach(row => {
		const dName = row.getAttribute('data-domain') || '';
		const dUser = row.getAttribute('data-user') || '';
		if (dName.includes(query) || dUser.includes(query)) {
			row.style.display = '';
		} else {
			row.style.display = 'none';
		}
	});
	updateBulkCounter();
}
</script>
