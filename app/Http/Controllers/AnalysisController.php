<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class AnalysisController extends Controller
{
    public function form()
    {
        return view('form');
    }

    public function analyze(Request $request)
    {
        // Validate upload
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        // ----- Ensure tmp dir exists
        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        // ----- Save uploaded file with extension using manual move (reliable on Windows)
        $uploaded  = $request->file('file');
        $extension = $uploaded->getClientOriginalExtension(); // xlsx/xls
        $filename  = uniqid('excel_', true) . '.' . $extension;
        $excelPath = $tmpDir . DIRECTORY_SEPARATOR . $filename;
        $uploaded->move($tmpDir, $filename);

        if (!file_exists($excelPath)) {
            return back()->withErrors('Upload failed, file not saved at: ' . $excelPath)->withInput();
        }

        $script = base_path('python/analyze.py');
        if (!file_exists($script)) {
            return back()->withErrors('Analyzer script not found: ' . $script)->withInput();
        }

        // ----- Resolve python binary
        $pythonEnv = env('PYTHON_BIN');
        $candidates = array_filter([
            $pythonEnv,
            'C:\\Users\\HASAN\\AppData\\Local\\Programs\\Python\\Python313\\python.exe',
            'C:\\Program Files\\Python313\\python.exe',
            'C:\\Python313\\python.exe',
            'py',       // Windows launcher
            'python',   // PATH
            'python3',
        ]);

        $python = null;
        foreach ($candidates as $bin) {
            try {
                $probe = new Process([$bin, '--version']);
                $probe->setTimeout(5);
                $probe->run();
                if ($probe->isSuccessful()) { $python = $bin; break; }
            } catch (\Throwable $e) { /* try next */ }
        }

        if (!$python) {
            return back()->withErrors('Python not found. Set PYTHON_BIN in .env to your python.exe path.')->withInput();
        }

        // ----- Run analyzer (force UTF-8, ignore warnings)
        // We also pass an env var to ensure UTF-8 output.
        $env = ['PYTHONIOENCODING' => 'utf-8'];
        $cmd = [$python, '-X', 'utf8', '-W', 'ignore', $script, $excelPath];
        $process = new Process($cmd, null, $env);
        $process->setTimeout(180);
        $process->run();

        $stdout = $process->getOutput();
        $stderr = $process->getErrorOutput();

        // ----- Always keep a short debug trail (helps us see what's happening)
        $debugId = Str::random(6);
        try {
            file_put_contents(storage_path("logs/analyzer_{$debugId}.out.txt"), $stdout ?? '');
            file_put_contents(storage_path("logs/analyzer_{$debugId}.err.txt"), $stderr ?? '');
        } catch (\Throwable $e) {
            // ignore logging failures
        }

        if (!$process->isSuccessful()) {
            $msg = trim($stderr ?: $stdout ?: 'Analyzer failed with no output.');
            return back()->withErrors('Analyzer failed: ' . $msg)->withInput();
        }

        // ----- Parse JSON safely; show raw snippet if malformed
        $data = json_decode($stdout, true);
        if (!is_array($data) || !array_key_exists('ok', $data)) {
            $snippet = mb_substr(trim($stdout), 0, 500, 'UTF-8');
            return back()->withErrors(
                'Analyzer returned non-JSON (see storage/logs/analyzer_'.$debugId.'.out.txt). Raw: ' . ($snippet ?: '[empty]')
            )->withInput();
        }

        if (!$data['ok']) {
            $msg = $data['error'] ?? 'Unknown error from analyzer';
            return back()->withErrors($msg)->withInput();
        }

        return view('results', [
            'rows'    => $data['rows'],
            'summary' => $data['summary'],
            'payload' => base64_encode(json_encode($data)),
        ]);
    }

public function exportPdf(Request $request)
{
    try {
        // ---- Decode payload (POST from results page)
        $payloadBase64 = $request->input('payload');
        if (!$payloadBase64) {
            return response('No data to export. Please run Analyze first.', 400);
        }

        $decoded = base64_decode($payloadBase64, true);
        if ($decoded === false) {
            return response('Base64 decode failed.', 400);
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload)) {
            return response('Invalid export data.', 400);
        }

        $rows    = $payload['rows'] ?? [];
        $summary = $payload['summary'] ?? [];

        // ---- Safety: increase memory + time for local dev
        @ini_set('memory_limit', '1024M');   // try 1024M (1 GB)
        @set_time_limit(120);                // 2 minutes

        // ---- Render blade to HTML first (quick check)
        if (!view()->exists('pdf')) {
            return response('View [pdf] not found at resources/views/pdf.blade.php', 500);
        }
        $html = view('pdf', compact('rows', 'summary'))->render();
        if (trim($html) === '') {
            return response('PDF view rendered empty HTML.', 500);
        }

        // ---- Build PDF with conservative options
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        $pdf->set_option('dpi', 96);
        $pdf->set_option('isRemoteEnabled', false);
        $pdf->set_option('isHtml5ParserEnabled', true);

        return $pdf->download('analysis.pdf');

    } catch (\Throwable $e) {
        return response('Export failed: ' . $e->getMessage(), 500);
    }
}
    




}
