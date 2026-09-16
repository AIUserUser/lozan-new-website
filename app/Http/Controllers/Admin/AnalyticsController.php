<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsReport;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request)
    {
        $report = new AnalyticsReport((int) $request->query('range', 30));

        return view('admin.analytics', ['report' => $report->build()]);
    }
}
