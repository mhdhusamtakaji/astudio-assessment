<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Project;
use App\Traits\ValidationTrait;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use ValidationTrait;

    public function __construct()
    {
        // all routes here require authentication
        $this->middleware('auth:api');
    }

    /**
     * GET /api/projects
     *
     * Basic index with optional EAV filter by:
     * ?attribute_name=department&attribute_value=IT
     */
    public function index(Request $request)
    {
        $query = Project::query();

        // 1. Check if 'filters' is present in the query string
        $filters = $request->query('filters', []);

        // 2. Loop through each filter
        foreach ($filters as $field => $condition) {
            // $field = 'name' or 'department' or 'created_at', etc.
            // $condition might be something like ['=' => 'ProjectA'] or ['like' => 'IT']

            // a) If $condition is not an array or empty, skip
            if (!is_array($condition) || empty($condition)) {
                continue;
            }

            // b) We only expect a single operator => value pair (e.g., ['=' => 'ProjectA'])
            //   If there's more than one, you might extend logic or loop over them
            $operator = array_key_first($condition);   // e.g. '=' or 'like'
            $value    = $condition[$operator];         // e.g. 'ProjectA'

            // c) Decide if $field is a "regular column" or an EAV attribute.
            //    E.g. check if the column exists in the 'projects' table schema or not.
            //    For simplicity, let’s do an in_array check of known columns 
            //    or rely on a separate method (or reflection). For a production app,
            //    you might want a more robust check.
            $isRegularColumn = in_array($field, [
                'id', 'name', 'status', 'created_at', 'updated_at'
            ]);

            // d) Apply the filter
            if ($isRegularColumn) {
                // Filter by a native column on the projects table
                $this->applyRegularColumnFilter($query, $field, $operator, $value);
            } else {
                // Filter by EAV attribute
                $this->applyEavFilter($query, $field, $operator, $value);
            }
        }

        // 3. Finally, fetch results (with EAV relationship loaded)
        $projects = $query->get()->load('attributeValues.attribute');

        return response()->json($projects, 200);
    }

    /**
     * Apply a filter to a native project column.
     */
    private function applyRegularColumnFilter($query, $column, $operator, $value)
    {
        // Map textual operators to actual SQL
        switch (strtolower($operator)) {
            case '=':
            case '>':
            case '<':
                // e.g. $query->where('name', '=', 'ProjectA')
                $query->where($column, $operator, $value);
                break;
            case 'like':
                // Convert 'like' to SQL wildcard
                $query->where($column, 'LIKE', "%{$value}%");
                break;
            default:
                // You might throw an exception or ignore unknown operators
                // e.g. $query->where($column, $value)
                break;
        }
    }

    /**
     * Apply a filter to an EAV attribute via a join on attributeValues.
     * We must find an Attribute record by $field (the attribute name).
     */
    private function applyEavFilter($query, $attributeName, $operator, $value)
    {
        // 1. Locate the attribute by 'name' in the attributes table
        $attribute = \App\Models\Attribute::where('name', $attributeName)->first();
        if (!$attribute) {
            // No such attribute => optionally skip or return no results
            // We'll skip if the attribute is not found
            return;
        }

        // 2. We'll use whereHas('attributeValues') with the operator
        switch (strtolower($operator)) {
            case '=':
            case '>':
            case '<':
                $query->whereHas('attributeValues', function ($q) use ($attribute, $operator, $value) {
                    $q->where('attribute_id', $attribute->id)
                    ->where('value', $operator, $value);
                });
                break;
            case 'like':
                $query->whereHas('attributeValues', function ($q) use ($attribute, $value) {
                    $q->where('attribute_id', $attribute->id)
                    ->where('value', 'LIKE', "%{$value}%");
                });
                break;
            default:
                // Unknown operator => ignore or throw an exception
                break;
        }
    }


    /**
     * GET /api/projects/{project}
     * Show a single project with its dynamic attributes in a user-friendly format.
     */
    public function show(Project $project)
    {
        // Eager load the relationship
        $project->load('attributeValues.attribute');

        // Transform the data for a cleaner response
        $data = [
            'id'     => $project->id,
            'name'   => $project->name,
            'status' => $project->status,
            'eav_data' => $project->attributeValues->map(function ($val) {
                return [
                    'attribute_name' => $val->attribute->name,
                    'type'           => $val->attribute->type,
                    'value'          => $val->value,
                ];
            }),
        ];

        return response()->json($data, 200);
    }

    /**
     * POST /api/projects
     * Create a new project, optionally with EAV data.
     */
    public function store(Request $request)
    {
        // Validate base project data using the trait
        $data = $this->runValidation($request, [
            'name'   => 'required|string',
            'status' => 'nullable|string',
            // we can validate EAV inputs if needed:
            // 'attributes'                   => 'array',
            // 'attributes.*.attribute_id'    => 'required|integer|exists:attributes,id',
            // 'attributes.*.value'           => 'required',
        ]);

        // Create the project with validated data
        $project = Project::create([
            'name'   => $data['name'],
            'status' => $data['status'] ?? null,
        ]);

        // If the request includes EAV data, handle it
        if ($request->has('attributes')) {
            $this->syncAttributeValues($project, $request->input('attributes'));
        }

        // Return the newly created project with loaded attribute values
        return response()->json($project->load('attributeValues'), 201);
    }

    /**
     * PUT or PATCH /api/projects/{project}
     * Update an existing project, optionally with EAV data.
     */
    public function update(Request $request, Project $project)
    {
        // Validate only the fields that may appear
        $data = $this->runValidation($request, [
            'name'   => 'sometimes|string',
            'status' => 'sometimes|string',
            // 'attributes' => ...
        ]);

        // Update standard fields
        if (isset($data['name'])) {
            $project->name = $data['name'];
        }
        if (isset($data['status'])) {
            $project->status = $data['status'];
        }
        $project->save();

        // Update EAV if provided
        if ($request->has('attributes')) {
            $this->syncAttributeValues($project, $request->input('attributes'));
        }

        return response()->json($project->load('attributeValues'), 200);
    }

    /**
     * DELETE /api/projects/{project}
     * Remove the specified project (and cascade EAV data if set up that way).
     */
    public function destroy(Project $project)
    {
        $project->delete();
        return response()->json(null, 204);
    }

    /**
     * Helper method to (create/update) EAV data for a project.
     * $attributes = [
     *    [ "attribute_id" => 1, "value" => "IT" ],
     *    [ "attribute_id" => 2, "value" => "2024-01-01" ]
     * ]
     */
    private function syncAttributeValues(Project $project, array $attributes)
    {
        foreach ($attributes as $attr) {
            // Example basic validation (feel free to expand):
            //   Ensure that 'attribute_id' & 'value' keys exist, etc.

            AttributeValue::updateOrCreate(
                [
                    'attribute_id' => $attr['attribute_id'],
                    'entity_id'    => $project->id,
                ],
                [
                    'value' => $attr['value'],
                ]
            );
        }
    }
}
