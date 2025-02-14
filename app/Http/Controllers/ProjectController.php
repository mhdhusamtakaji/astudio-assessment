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
        // All methods require an authenticated user
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of projects, optionally filtered by both
     * regular columns and EAV attributes.
     * Example:
     * GET /api/projects?filters[name][=]=ProjectA&filters[department][like]=IT
     */
    public function index(Request $request)
    {
        $query = Project::query();

        // Retrieve 'filters' from query parameters (default to empty array)
        $filters = $request->query('filters', []);

        foreach ($filters as $field => $condition) {
            // Each $condition should be an array with a single operator => value pair
            if (!is_array($condition) || empty($condition)) {
                continue;
            }

            $operator = array_key_first($condition);   // e.g. '=' or 'like'
            $value    = $condition[$operator];         // e.g. 'ProjectA'

            // Determine if this is a regular column in the projects table
            $isRegularColumn = in_array($field, ['id', 'name', 'status', 'created_at', 'updated_at']);

            if ($isRegularColumn) {
                $this->applyRegularColumnFilter($query, $field, $operator, $value);
            } else {
                $this->applyEavFilter($query, $field, $operator, $value);
            }
        }

        // Eager load related attribute values and return results
        $projects = $query->get()->load('attributeValues.attribute');
        return response()->json($projects, 200);
    }

    /**
     * Show details of a single project, including its EAV data.
     */
    public function show(Project $project)
    {
        $project->load('attributeValues.attribute');

        // Format EAV fields into a cleaner structure
        $data = [
            'id'       => $project->id,
            'name'     => $project->name,
            'status'   => $project->status,
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
     * Store a new project. Accepts optional EAV attributes.
     */
    public function store(Request $request)
    {
        $data = $this->runValidation($request, [
            'name'   => 'required|string',
            'status' => 'nullable|string',
        ]);

        $project = Project::create([
            'name'   => $data['name'],
            'status' => $data['status'] ?? null,
        ]);

        // If the request has EAV attributes, process them
        if ($request->has('attributes')) {
            $this->syncAttributeValues($project, $request->input('attributes'));
        }

        return response()->json($project->load('attributeValues'), 201);
    }

    /**
     * Update an existing project. Accepts optional EAV attributes.
     */
    public function update(Request $request, Project $project)
    {
        $data = $this->runValidation($request, [
            'name'   => 'sometimes|string',
            'status' => 'sometimes|string',
        ]);

        if (isset($data['name'])) {
            $project->name = $data['name'];
        }
        if (isset($data['status'])) {
            $project->status = $data['status'];
        }
        $project->save();

        // If the request has EAV attributes, process them
        if ($request->has('attributes')) {
            $this->syncAttributeValues($project, $request->input('attributes'));
        }

        return response()->json($project->load('attributeValues'), 200);
    }

    /**
     * Delete the specified project, automatically removing
     * any related EAV data (due to foreign key constraints).
     */
    public function destroy(Project $project)
    {
        $project->delete();
        return response()->json(null, 204);
    }

    /**
     * Applies a filter for a regular (native) column in the projects table.
     */
    private function applyRegularColumnFilter($query, $column, $operator, $value)
    {
        switch (strtolower($operator)) {
            case '=':
            case '>':
            case '<':
                $query->where($column, $operator, $value);
                break;
            case 'like':
                $query->where($column, 'LIKE', "%{$value}%");
                break;
            default:
                // Unknown or unsupported operator: ignore or handle as needed
                break;
        }
    }

    /**
     * Applies a filter for an EAV attribute by joining through attribute_values.
     */
    private function applyEavFilter($query, $attributeName, $operator, $value)
    {
        $attribute = Attribute::where('name', $attributeName)->first();
        if (!$attribute) {
            // Skip filtering if the attribute is not found
            return;
        }

        switch (strtolower($operator)) {
            case '=':
            case '>':
            case '<':
                $query->whereHas('attributeValues', function ($q) use ($attribute, $operator, $value) {
                    $q->where('attribute_id', $attribute->id)->where('value', $operator, $value);
                });
                break;
            case 'like':
                $query->whereHas('attributeValues', function ($q) use ($attribute, $value) {
                    $q->where('attribute_id', $attribute->id)->where('value', 'LIKE', "%{$value}%");
                });
                break;
            default:
                // Unknown or unsupported operator: ignore or handle as needed
                break;
        }
    }

    /**
     * Syncs EAV attributes for a project by updating or creating records
     * in the attribute_values table.
     * 
     * Example payload:
     * "attributes": [
     *   { "attribute_id": 1, "value": "IT" },
     *   { "attribute_id": 2, "value": "2024-01-01" }
     * ]
     */
    private function syncAttributeValues(Project $project, array $attributes)
    {
        foreach ($attributes as $attr) {
            // Basic keys check could be done here, if desired
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
