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

        // EAV filtering example
        if ($request->has('attribute_name') && $request->has('attribute_value')) {
            $attrName  = $request->input('attribute_name');
            $attrValue = $request->input('attribute_value');

            // Locate the corresponding Attribute by name
            $attribute = Attribute::where('name', $attrName)->first();
            if ($attribute) {
                // Filter projects that have an AttributeValue matching the given value
                $query->whereHas('attributeValues', function ($q) use ($attribute, $attrValue) {
                    $q->where('attribute_id', $attribute->id)
                      ->where('value', $attrValue);
                });
            }
        }

        // Retrieve all matching projects, eager loading attribute values
        $projects = $query->get()->load('attributeValues');

        return response()->json($projects, 200);
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
