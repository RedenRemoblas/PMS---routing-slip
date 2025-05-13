<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\Leave;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function generatePdf($id)
    {
        $leave = Leave::with(['employee', 'leaveType', 'leaveDetails'])->findOrFail($id);
        $employee = $leave->employee;

        $pdf = PDF::loadView('leave_application_pdf', compact('leave', 'employee'));

        return $pdf->download('leave_application_form.pdf');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
