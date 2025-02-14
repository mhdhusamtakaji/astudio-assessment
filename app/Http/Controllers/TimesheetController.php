<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use App\Traits\ValidationTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TimesheetController extends Controller
{
    use ValidationTrait;

    public function __construct()
    {
        // All methods require passport-authenticated requests
        $this->middleware('auth:api');
    }

    /**
     * GET /api/timesheets
     * Possibly filter by user, or show all if admin
     */
    public function index()
    {
        //  only timesheets of the logged-in user:
        $timesheets = Timesheet::where('user_id', auth()->id())->get();
        // Otherwise, to show all:
        // $timesheets = Timesheet::all();

        return response()->json($timesheets, Response::HTTP_OK);
    }

    /**
     * POST /api/timesheets
     */
    public function store(Request $request)
    {
        // Validate input
        $data = $this->runValidation($request, [
            'task_name'  => 'required|string',
            'date'       => 'required|date',
            'hours'      => 'required|numeric',
            'project_id' => 'required|exists:projects,id',
        ]);

        // supposedly, the user_id is the current authenticated user
        $timesheet = Timesheet::create([
            'user_id'    => auth()->id(),
            'project_id' => $data['project_id'],
            'task_name'  => $data['task_name'],
            'date'       => $data['date'],
            'hours'      => $data['hours'],
        ]);

        return response()->json($timesheet, Response::HTTP_CREATED);
    }

    /**
     * GET /api/timesheets/{timesheet}
     */
    public function show(Timesheet $timesheet)
    {
        //  ownership check - only the user who created the timesheet can view it
        if ($timesheet->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return response()->json($timesheet, Response::HTTP_OK);
    }

    /**
     * PUT/PATCH /api/timesheets/{timesheet}
     */
    public function update(Request $request, Timesheet $timesheet)
    {
        // ownership check
        if ($timesheet->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        // Validate only the fields we expect
        $data = $this->runValidation($request, [
            'task_name' => 'sometimes|string',
            'date'      => 'sometimes|date',
            'hours'     => 'sometimes|numeric',
        ]);

        // Update fields that are present
        $timesheet->update($data);

        return response()->json($timesheet, Response::HTTP_OK);
    }

    /**
     * DELETE /api/timesheets/{timesheet}
     */
    public function destroy(Timesheet $timesheet)
    {
        // ownership check
        if ($timesheet->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $timesheet->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
