<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TicketExportController extends Controller
{
    // public function exportPdf(Request $request)
    // {
    //     $type = $request->query('type', 'monthly');
    //     $query = Ticket::query();

    //     // Apply filters
    //     if ($type === 'monthly') {
    //         $month = $request->query('month', now()->month);
    //         $year = $request->query('year', now()->year);
    //         $query->whereYear('created_at', $year)->whereMonth('created_at', $month);
    //     } elseif ($type === 'yearly') {
    //         $year = $request->query('year', now()->year);
    //         $query->whereYear('created_at', $year);
    //     } elseif ($type === 'custom') {
    //         $from = $request->query('from');
    //         $to = $request->query('to');
    //         if ($from && $to) {
    //             $query->whereBetween('created_at', [$from, $to]);
    //         }
    //     }

    //     $tickets = $query->orderBy('created_at', 'desc')->get();

    //     $pdf = Pdf::loadView('exports.tickets', [
    //         'tickets' => $tickets,
    //         'type' => ucfirst($type),
    //         'date' => now()->format('F j, Y'),
    //     ])->setPaper('a4', 'portrait');

    //     $filename = "tickets_export_" . now()->format('Y_m_d_His') . ".pdf";

    //     return $pdf->download($filename);
    // }

    public function exportPdf(Request $request)
    {
        $type = $request->query('type', 'monthly');

        $status = $request->query('status', 'all');
        $tech = $request->query('tech', 'all');

        $query = Ticket::query();

        if($status != "all" && !empty($status)){
            $query->where('status', $status);
        }
        
        $range = "";

        // Apply filters
        if ($type === 'monthly') {
            $month = (int) $request->query('month', now()->month);
            $year = (int) $request->query('year', now()->year);

            $query->whereYear('created_at', $year)->whereMonth('created_at', $month);

            $monthName = Carbon::create()->month($month)->format('F');

            $range = "{$monthName}, {$year}";
        } elseif ($type === 'yearly') {
            $year = $request->query('year', now()->year);
            $query->whereYear('created_at', $year);

            $range = "{$year}";
        } elseif ($type === 'custom') {
            $from = $request->query('from');
            $to = $request->query('to');
            if ($from && $to) {
                $query->whereBetween('created_at', [$from, $to]);
            }

            $range = "{$from}, {$to}";
        }

        $tickets = $query->orderBy('created_at', 'desc')->get();

        $pdf = Pdf::loadView('exports.tickets', [
            'status' => $status != "all" ? "({$status})" : "",
            'tickets' => $tickets,
            'range' => ucfirst($range),
            'date' => now()->format('F j, Y'),
        ])->setPaper('a4', 'portrait');

        $filename = "tickets_export_" . now()->format('Y_m_d_His') . ".pdf";

        return $pdf->download($filename);
    }
}
