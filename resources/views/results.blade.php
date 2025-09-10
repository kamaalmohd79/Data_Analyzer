<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Results</title>
  <style>
    body {
      font-family: system-ui;
      margin: 24px
    }

    table {
      border-collapse: collapse;
      width: 100%
    }

    th,
    td {
      border: 1px solid #eee;
      padding: 8px;
      text-align: left
    }

    .card {
      padding: 16px;
      border: 1px solid #eee;
      border-radius: 12px;
      margin: 16px 0
    }
  </style>
</head>

<body>
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>Analysis Results</h2>
    <!-- PDF export form -->
    <form action="{{ route('export.pdf') }}" method="post">
      @csrf
      <input type="hidden" name="payload" value="{{ $payload }}">
      <button type="submit">Export PDF</button>
    </form>
  </div>
  <div class="card">
    <div style="display:flex; align-items:center; gap:12px;">
      <h3 style="margin:0;">Category Totals</h3>
      <div style="margin-left:auto;">
        <!-- Chart type selector -->
        <label for="chartType" style="font-weight:600; margin-right:6px;">View as:</label>
        <select id="chartType">
          <option value="bar" selected>Vertical Bar</option>
          <option value="horizontal">Horizontal Bar</option>
          <option value="stacked">Stacked Bar (pos/neg)</option>
          <option value="line">Line</option>
          <option value="area">Area</option>
          <option value="pie">Pie</option>
          <option value="doughnut">Doughnut</option>
          <option value="radar">Radar</option>
          <option value="polarArea">Polar Area</option>
        </select>
      </div>
    </div>
    <!-- Chart container -->
    <div class="chart-wrap">
      <canvas id="categoryChart" class="chart-canvas"></canvas>
    </div>
  </div>
  <div class="card" id="tx-card">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
      <h3 style="margin:0;">All Transactions</h3>
      <div id="tx-meta" style="font-size:12px;color:#666;"></div>
    </div>
    <table>
      <thead>
        <tr>
          <th>Description</th>
          <th>Amount</th>
          <th>Category</th>
          <th>Date</th>
          <th>Currency</th>
        </tr>
      </thead>
      <tbody id="transactionsBody">
        <!-- Render each transaction row -->
        @foreach($rows as $r)
        <tr>
          <td>{{ $r['description'] }}</td>
          <td>{{ number_format($r['amount'],2) }}</td>
          <td>{{ $r['category'] }}</td>
          <td>{{ $r['date'] ?? '' }}</td>
          <td>{{ $r['currency'] ?? '' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <!-- Pagination controls (injected by JS) -->
    <div id="tx-pagination" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;justify-content:flex-end;margin-top:12px;">
      <!-- buttons injected by JS -->
    </div>
  </div>
  
  <!-- Chart.js library for rendering charts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
  <script>
    // ===== Prepare data for chart =====
    const totals = {!! json_encode($summary['category_totals'] ?? (object)[]) !!};
    const labels = Object.keys(totals);
    const values = Object.values(totals);
    // Early exit if no data (optional but helpful)
    if (!labels.length) {
      console.warn('No category_totals data available');
    }
    // ===== Styling helpers =====
    const fmt = new Intl.NumberFormat('en-US', {
      maximumFractionDigits: 0
    });
    const baseColors = [
      'rgba(59,130,246,0.6)', 'rgba(16,185,129,0.6)', 'rgba(245,158,11,0.6)', 'rgba(239,68,68,0.6)',
      'rgba(168,85,247,0.6)', 'rgba(14,165,233,0.6)', 'rgba(234,88,12,0.6)', 'rgba(34,197,94,0.6)',
    ];
    const borderColors = baseColors.map(c => c.replace('0.6', '1'));

    // Dataset for bar charts
    function barsDataset() {
      return {
        label: 'Totals',
        data: values,
        backgroundColor: labels.map((_, i) => baseColors[i % baseColors.length]),
        borderColor: labels.map((_, i) => borderColors[i % borderColors.length]),
        borderWidth: 1
      };
    }

    // Datasets for stacked bar chart (positive/negative)
    function stackedDatasets() {
      const pos = values.map(v => v > 0 ? v : 0);
      const neg = values.map(v => v < 0 ? v : 0);
      return [{
          label: 'Positive',
          data: pos,
          stack: 's',
          backgroundColor: 'rgba(34,197,94,0.6)',
          borderColor: 'rgba(34,197,94,1)'
        },
        {
          label: 'Negative',
          data: neg,
          stack: 's',
          backgroundColor: 'rgba(239,68,68,0.6)',
          borderColor: 'rgba(239,68,68,1)'
        },
      ];
    }

    // Common chart options
    function commonOptions(yTitle = 'Value') {
      return {
        responsive: true,
        plugins: {
          legend: {
            position: 'top'
          },
          tooltip: {
            callbacks: {
              label: ctx => `${ctx.dataset.label || ctx.label}: ${fmt.format(ctx.parsed)}`
            }
          }
        },
        scales: {
          x: {
            ticks: {
              autoSkip: true,
              maxRotation: 0
            }
          },
          y: {
            beginAtZero: true,
            ticks: {
              callback: v => fmt.format(v)
            },
            title: {
              display: true,
              text: yTitle
            }
          }
        }
      };
    }

    // Build chart config based on selected type
    function buildConfig(kind) {
      const simpleOptions = {
        responsive: true,
        plugins: {
          legend: {
            position: 'top'
          },
          tooltip: {
            callbacks: {
              label: ctx => `${ctx.label}: ${fmt.format(ctx.parsed || ctx.raw)}`
            }
          }
        }
      };
      if (kind === 'horizontal') {
        return {
          type: 'bar',
          data: {
            labels,
            datasets: [barsDataset()]
          },
          options: {
            ...commonOptions(),
            indexAxis: 'y'
          }
        };
      }
      if (kind === 'stacked') {
        return {
          type: 'bar',
          data: {
            labels,
            datasets: stackedDatasets()
          },
          options: {
            ...commonOptions(),
            scales: {
              x: {
                stacked: true
              },
              y: {
                stacked: true,
                beginAtZero: true
              }
            }
          }
        };
      }
      if (kind === 'line' || kind === 'area') {
        return {
          type: 'line',
          data: {
            labels,
            datasets: [{
              label: 'Totals',
              data: values,
              borderColor: 'rgba(59,130,246,1)',
              backgroundColor: 'rgba(59,130,246,0.2)',
              borderWidth: 2,
              pointRadius: 3,
              tension: 0.25,
              fill: (kind === 'area')
            }]
          },
          options: commonOptions()
        };
      }
      if (['pie', 'doughnut', 'radar', 'polarArea'].includes(kind)) {
        const typeMap = {
          pie: 'pie',
          doughnut: 'doughnut',
          radar: 'radar',
          polarArea: 'polarArea'
        };
        const ds = (kind === 'radar') ? [{
          label: 'Totals',
          data: values,
          backgroundColor: 'rgba(59,130,246,0.2)',
          borderColor: 'rgba(59,130,246,1)'
        }] : [{
          label: 'Totals',
          data: values,
          backgroundColor: labels.map((_, i) => baseColors[i % baseColors.length])
        }];
        return {
          type: typeMap[kind],
          data: {
            labels,
            datasets: ds
          },
          options: simpleOptions
        };
      }
      // Default to vertical bar
      return {
        type: 'bar',
        data: {
          labels,
          datasets: [barsDataset()]
        },
        options: commonOptions()
      };
    }
    let chart;

    // Render chart of selected type
    function render(kind) {
      const canvas = document.getElementById('categoryChart'); // <-- FIXED ID
      const ctx = canvas.getContext('2d');
      if (chart) chart.destroy();
      chart = new Chart(ctx, buildConfig(kind));
    }
    // Listen for chart type changes
    document.getElementById('chartType').addEventListener('change', (e) => render(e.target.value));
    // initial render
    render('bar');
  </script>
  <!-- Pagination Script -->
  <script>
    // Paginate the transactions table
    (function paginateTransactions() {
      const PER_PAGE = 15;
      const tbody = document.getElementById('transactionsBody');
      if (!tbody) return;
      const rows = Array.from(tbody.querySelectorAll('tr'));
      const total = rows.length;
      const pages = Math.max(1, Math.ceil(total / PER_PAGE));
      let current = 1;
      const pagEl = document.getElementById('tx-pagination');
      const metaEl = document.getElementById('tx-meta');

      // Render a specific page of transactions
      function renderPage(n) {
        current = Math.min(Math.max(1, n), pages);
        const startIdx = (current - 1) * PER_PAGE;
        const endIdx = Math.min(startIdx + PER_PAGE, total);
        // show only rows for the current page
        rows.forEach((tr, i) => {
          tr.style.display = (i >= startIdx && i < endIdx) ? '' : 'none';
        });
        // update meta (e.g., “1–20 of 143”)
        if (metaEl) {
          const startDisp = total ? startIdx + 1 : 0;
          metaEl.textContent = `${startDisp}–${endIdx} of ${total}`;
        }
        // rebuild controls
        buildControls();
      }

      // Create a pagination button
      function button(label, onClick, disabled = false, active = false) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = label;
        btn.disabled = disabled;
        btn.style.cssText = `
      padding:6px 10px;border:1px solid #ddd;background:#fff;cursor:pointer;border-radius:8px;
      font-size:12px;${active ? 'font-weight:700;background:#f5f5f5;' : ''}
      ${disabled ? 'opacity:.6;cursor:not-allowed;' : ''}
    `;
        if (!disabled) btn.addEventListener('click', onClick);
        return btn;
      }

      // Build pagination controls
      function buildControls() {
        if (!pagEl) return;
        pagEl.innerHTML = '';
        // Prev
        pagEl.appendChild(button('‹ Prev', () => renderPage(current - 1), current === 1));
        // Page numbers (compact window)
        const windowSize = 5;
        let start = Math.max(1, current - Math.floor(windowSize / 2));
        let end = Math.min(pages, start + windowSize - 1);
        if (end - start + 1 < windowSize) start = Math.max(1, end - windowSize + 1);
        if (start > 1) {
          pagEl.appendChild(button('1', () => renderPage(1), false, current === 1));
          if (start > 2) pagEl.append('…');
        }
        for (let p = start; p <= end; p++) {
          pagEl.appendChild(button(String(p), () => renderPage(p), false, p === current));
        }
        if (end < pages) {
          if (end < pages - 1) pagEl.append('…');
          pagEl.appendChild(button(String(pages), () => renderPage(pages), false, current === pages));
        }
        // Next
        pagEl.appendChild(button('Next ›', () => renderPage(current + 1), current === pages));
      }
      // initial paint
      renderPage(1);
    })();
  </script>
  <style>
    /* Chart container styling */
    .chart-wrap {
      max-width: 700px;
      /* keeps it compact on page/PDF */
      margin: 0 auto;
    }

    .chart-canvas {
      width: 100%;
      height: 380px;
      /* final height you want in page/PDF */
    }
  </style>
</body>

</html>