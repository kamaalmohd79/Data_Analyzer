<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <style>
    /* Keep styles minimal for DomPDF performance */
    body {
      font-family: DejaVu Sans, Arial, sans-serif;
      font-size: 11px;
      margin: 14px;
    }

    h2,
    h3 {
      margin: 6px 0;
    }

    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      margin-top: 6px;
    }

    th,
    td {
      padding: 4px 6px;
      text-align: left;
    }

    th {
      border-bottom: 1px solid #888;
    }

    tr+tr td {
      border-top: 1px solid #ddd;
    }

    .page-break {
      page-break-after: always;
    }
  </style>
</head>

<body>
  <h2>Transaction Analysis</h2>

  <h3>Category Totals</h3>
  <table>
    <thead>
      <tr>
        <th>Category</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      {{-- Loop through each category and display its total --}}
      @foreach(($summary['category_totals'] ?? []) as $cat => $total)
      <tr>
        <td>{{ $cat }}</td>
        <td>{{ number_format((float)$total, 2) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="page-break"></div> {{-- Force page break for PDF --}}

  <h3>All Transactions</h3>

  @php
  // Chunk rows to avoid huge single tables (helps DomPDF memory)
  $chunks = array_chunk($rows ?? [], 400); // 400 rows per page; adjust if needed
  @endphp

  {{-- Loop through each chunk of transactions --}}
  @foreach($chunks as $i => $chunk)
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
    <tbody>
      {{-- Display each transaction row --}}
      @foreach($chunk as $r)
      <tr>
        <td>{{ $r['description'] }}</td>
        <td>{{ number_format((float)$r['amount'], 2) }}</td>
        <td>{{ $r['category'] }}</td>
        <td>{{ $r['date'] ?? '' }}</td>
        <td>{{ $r['currency'] ?? '' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  {{-- Add a page break after each chunk except the last --}}
  @if($i < count($chunks) - 1)
    <div class="page-break">
    </div>
    @endif
    @endforeach
</body>

</html>