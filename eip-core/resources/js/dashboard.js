import Chart from 'chart.js/auto';

// Chart utama: jumlah pegawai per unit kerja, toggle Semua/Dosen/Tendik.
const canvas = document.getElementById('unitChart');

if (canvas) {
    const dataEl = document.getElementById('unit-chart-data');
    const chartDataMap = JSON.parse(dataEl.textContent);

    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 240);
    gradient.addColorStop(0, 'rgba(79, 70, 229, 0.55)');
    gradient.addColorStop(1, 'rgba(79, 70, 229, 0.12)');

    const initial = chartDataMap.Semua;

    const unitChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: initial.labels,
            datasets: [
                {
                    data: initial.data,
                    backgroundColor: gradient,
                    borderRadius: 8,
                    maxBarThickness: 28,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0F172A',
                    titleFont: { size: 11, family: 'Plus Jakarta Sans', weight: 'bold' },
                    bodyFont: { size: 12, family: 'JetBrains Mono', weight: 'bold' },
                    padding: 10,
                    cornerRadius: 12,
                    displayColors: false,
                    callbacks: {
                        label: (context) => `${context.parsed.y} pegawai`,
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#94A3B8', font: { size: 10, family: 'Plus Jakarta Sans', weight: '500' } },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#94A3B8',
                        font: { size: 10, family: 'JetBrains Mono' },
                        precision: 0,
                    },
                    grid: { color: 'rgba(241, 245, 249, 0.8)' },
                },
            },
        },
    });

    document.querySelectorAll('[data-chart-filter]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const key = btn.dataset.chartFilter;

            document.querySelectorAll('[data-chart-filter]').forEach((b) => {
                b.classList.toggle('bg-white', b === btn);
                b.classList.toggle('shadow-xs', b === btn);
                b.classList.toggle('text-slate-900', b === btn);
                b.classList.toggle('font-bold', b === btn);
                b.classList.toggle('text-slate-500', b !== btn);
            });

            unitChart.data.labels = chartDataMap[key].labels;
            unitChart.data.datasets[0].data = chartDataMap[key].data;
            unitChart.update();
        });
    });
}

// Filter cepat tabel unit kerja.
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('keyup', (e) => {
        const filter = e.target.value.toLowerCase();
        document.querySelectorAll('#unitTable tbody tr').forEach((row) => {
            row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
        });
    });

    window.addEventListener('keydown', (e) => {
        if (e.key === '/' && document.activeElement?.tagName !== 'INPUT') {
            e.preventDefault();
            searchInput.focus();
        }
    });
}
