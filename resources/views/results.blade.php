<!doctype html>
<html>
<head>
  <meta charset="utf-8"><title>Results</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>body{font-family:system-ui;margin:24px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #eee;padding:8px;text-align:left}.card{padding:16px;border:1px solid #eee;border-radius:12px;margin:16px 0}</style>
</head>
<body>
  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2>Analysis Results</h2>
<form action="{{ route('export.pdf') }}" method="post">
  @csrf
  <input type="hidden" name="payload" value="{{ $payload }}">
  <button type="submit">Export PDF</button>
</form>


  </div>

  <div class="card">
    <h3>Category Totals</h3>    
    <canvas id="bar"></canvas>
  </div>    

  <div class="card">
    <h3>All Transactions</h3>
    <table>
      <thead><tr><th>Description</th><th>Amount</th><th>Category</th><th>Date</th><th>Currency</th></tr></thead>
      <tbody>
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
  </div>

<script>
  // Use a PHP value as fallback. (object)[] ensures a JS {} when empty.
  const totals = @json($summary['category_totals'] ?? (object)[]);
  const labels = Object.keys(totals);
  const values = Object.values(totals);

  new Chart(document.getElementById('bar'), {
    type: 'bar',
    data: { labels: labels, datasets: [{ label: 'Totals', data: values }] },
  });
</script>
</body>
</html>
