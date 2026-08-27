import { getCssVariable, post } from './helpers';

// Create Chart.js charts from in-page data on Task Monitor page
export default async function initRrdCharts() {
	const chartCanvases = document.querySelectorAll('.js-rrd-chart');

	if (!chartCanvases.length) {
		return;
	}

	const Chart = await loadChartJs();

	for (const chartCanvas of chartCanvases) {
		const service = chartCanvas.dataset.service;
		const period = chartCanvas.dataset.period;
		const rrdData = await post('/list/rrd/ajax.php', { service, period });
		const chartData = prepareChartData(rrdData, period);
		const chartOptions = getChartOptions(rrdData.unit, rrdData.service);

		new Chart(chartCanvas, {
			type: 'line',
			data: chartData,
			options: chartOptions,
		});
	}
}

async function loadChartJs() {
	// NOTE: String expression used to prevent ESBuild from resolving
	// the import on build (Chart.js is a separate bundle)
	const chartJsBundlePath = '/js/dist/chart.js-auto.min.js';
	const chartJsModule = await import(`${chartJsBundlePath}`);
	return chartJsModule.Chart;
}

function prepareChartData(rrdData, period) {
	return {
		labels: rrdData.data.map((_, index) => {
			const timestamp = rrdData.meta.start + index * rrdData.meta.step;
			const date = new Date(timestamp * 1000);
			return formatLabel(date, period);
		}),
		datasets: rrdData.meta.legend.map((legend, legendIndex) => {
			const lineColor = getCssVariable(`--chart-line-${legendIndex + 1}-color`);

			return {
				label: legend,
				data: rrdData.data.map((dataPoint) => dataPoint[legendIndex]),
				tension: 0.3,
				pointStyle: false,
				borderWidth: 2,
				borderColor: lineColor,
			};
		}),
	};
}

function formatLabel(date, period) {
	const options = {
		daily: { hour: '2-digit', minute: '2-digit', hour12: false },
		weekly: { weekday: 'short', day: 'numeric' },
		monthly: { month: 'short', day: 'numeric' },
		yearly: { month: 'long' },
		biennially: { month: 'long', year: 'numeric' },
		triennially: { month: 'long', year: 'numeric' },
	};

	return date.toLocaleString([], options[period]);
}

/**
 * Smart value formatter that converts raw numbers into human-readable strings
 * based on the service type and unit.
 */
function formatValue(value, unit, service) {
	if (value === null || value === undefined || isNaN(value)) return '0';

	const absVal = Math.abs(value);

	// Memory: values are in KB from RRD, convert to MB or GB
	if (unit === 'MB' || service === 'mem') {
		if (absVal >= 1048576) {
			// KB -> GB (1048576 KB = 1 GB)
			return (value / 1048576).toFixed(1) + ' GB';
		} else if (absVal >= 1024) {
			// KB -> MB
			return (value / 1024).toFixed(0) + ' MB';
		}
		return value.toFixed(0) + ' KB';
	}

	// Network: values are in bytes/sec from RRD
	if (unit === 'KB/s' || (service && service.startsWith('net_'))) {
		if (absVal >= 1073741824) {
			return (value / 1073741824).toFixed(2) + ' GB/s';
		} else if (absVal >= 1048576) {
			return (value / 1048576).toFixed(1) + ' MB/s';
		} else if (absVal >= 1024) {
			return (value / 1024).toFixed(1) + ' KB/s';
		}
		return value.toFixed(0) + ' B/s';
	}

	// Database queries: add /s suffix
	if (unit === 'Sorgu/sn' || (service && (service.startsWith('mysql_') || service.startsWith('pgsql_')))) {
		if (absVal >= 1000000) {
			return (value / 1000000).toFixed(1) + 'M/s';
		} else if (absVal >= 1000) {
			return (value / 1000).toFixed(1) + 'K/s';
		}
		return value.toFixed(0) + '/s';
	}

	// Connections / Queue / generic counts
	if (absVal >= 1000000) {
		return (value / 1000000).toFixed(1) + 'M';
	} else if (absVal >= 10000) {
		return (value / 1000).toFixed(1) + 'K';
	}

	// Small numbers: show with max 2 decimal places
	if (Number.isInteger(value)) {
		return value.toString();
	}
	return value.toFixed(2);
}

/**
 * Human-readable unit label for Y axis title
 */
function getUnitLabel(unit) {
	const labels = {
		'MB': 'Bellek (MB/GB)',
		'KB/s': 'Bant Genişliği',
		'Sorgu/sn': 'Sorgu/sn',
		'Bağlantı': 'Bağlantı Sayısı',
		'Kuyruk': 'Kuyruk Boyutu',
		'Yük': 'Yük Ortalaması',
	};
	return labels[unit] || unit || '';
}

function getChartOptions(unit, service) {
	const labelColor = getCssVariable('--chart-label-color');
	const gridColor = getCssVariable('--chart-grid-color');

	return {
		responsive: true,
		maintainAspectRatio: false,
		interaction: {
			mode: 'index',
			intersect: false,
		},
		plugins: {
			legend: {
				position: 'bottom',
				labels: {
					color: labelColor,
					padding: 15,
					usePointStyle: true,
					pointStyleWidth: 12,
				},
			},
			tooltip: {
				backgroundColor: 'rgba(15, 23, 42, 0.95)',
				titleColor: '#f8fafc',
				bodyColor: '#cbd5e1',
				borderColor: 'rgba(56, 189, 248, 0.3)',
				borderWidth: 1,
				padding: 10,
				displayColors: true,
				callbacks: {
					label: function (context) {
						const label = context.dataset.label || '';
						const val = context.parsed.y;
						return ` ${label}: ${formatValue(val, unit, service)}`;
					},
				},
			},
		},
		scales: {
			x: {
				ticks: {
					color: labelColor,
					maxTicksLimit: 12,
					maxRotation: 0,
				},
				grid: {
					color: gridColor,
				},
			},
			y: {
				title: {
					display: Boolean(unit),
					text: getUnitLabel(unit),
					color: labelColor,
					font: { size: 11 },
				},
				ticks: {
					color: labelColor,
					callback: function (value) {
						return formatValue(value, unit, service);
					},
					maxTicksLimit: 8,
				},
				grid: {
					color: gridColor,
				},
				beginAtZero: true,
			},
		},
	};
}
