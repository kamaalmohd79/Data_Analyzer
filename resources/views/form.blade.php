<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Analyze</title>
  <style>
    body {
      font-family: system-ui;
      margin: 24px
    }

    .card {
      max-width: 680px;
      margin: auto;
      padding: 24px;
      border: 1px solid #eee;
      border-radius: 12px
    }
  </style>
</head>

<body>
  <div class="card">
    <h2>Data Analyzer (Excel)</h2>
    {{-- Display the first validation error if any --}}
    @if ($errors->any()) <div style="color:#b00020">{{ $errors->first() }}</div> @endif

    {{-- File upload form --}}
    <form action="{{ route('analyze') }}" method="post" enctype="multipart/form-data">
      @csrf {{-- Laravel CSRF protection --}}
      <input type="file" name="file" required accept=".xlsx,.xls"> {{-- Excel file input --}}
      <button type="submit">Analyze</button>
    </form>

    {{-- Info about expected columns --}}
    <p>Expected columns: <b>MainDesc</b>, <b>AddDesc</b>, <b>Amount</b> (optional: Date, SpendCategory, Currency)</p>
  </div>
</body>

</html>