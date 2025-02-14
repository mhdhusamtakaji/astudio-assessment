<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{

    public function index(Request $request)
    {
        // Base query
        $query = Project::query();

        // If filters has an EAV attribute filter
        if ($request->has('attribute_name') && $request->has('attribute_value')) {
            $attrName = $request->input('attribute_name');
            $attrValue = $request->input('attribute_value');

            // Find the attribute
            $attr = Attribute::where('name', $attrName)->first();
            if ($attr) {
                // Join attribute_values
                $query->whereHas('attributeValues', function($q) use ($attr, $attrValue) {
                    $q->where('attribute_id', $attr->id)
                    ->where('value', $attrValue);
                });
            }
        }

        // For demonstration, let's just return everything
        return response()->json($query->get()->load('attributeValues'), 200);
    }


    public function show(Project $project)
    {
        // Eager load the relationship
        $project->load('attributeValues.attribute');

        // Transform if you want to return a flat structure
        $data = [
            'id'       => $project->id,
            'name'     => $project->name,
            'status'   => $project->status,
            'eav_data' => $project->attributeValues->map(function($val) {
                return [
                    'attribute_name' => $val->attribute->name,
                    'type'           => $val->attribute->type,
                    'value'          => $val->value,
                ];
            })
        ];

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {
        // Validate base Project data
        $request->validate([
            'name'   => 'required|string',
            'status' => 'nullable|string',
            // ...
        ]);
    
        // Create the project
        $project = Project::create([
            'name'   => $request->input('name'),
            'status' => $request->input('status'),
        ]);
    
        // If the request includes EAV data, handle it
        // For example, you might send it as an array of [attribute_id => value]
        if ($request->has('attributes')) {
            $this->syncAttributeValues($project, $request->input('attributes'));
        }
    
        return response()->json($project->load('attributeValues'), 201);
    }
    
    public function update(Request $request, Project $project)
    {
        // Validate
        $request->validate([
            'name'   => 'sometimes|string',
            'status' => 'sometimes|string',
        ]);
    
        // Update standard fields
        $project->update($request->only(['name', 'status']));
    
        // Update EAV
        if ($request->has('attributes')) {
            $this->syncAttributeValues($project, $request->input('attributes'));
        }
    
        return response()->json($project->load('attributeValues'), 200);
    }
    
    /**
     * A helper method to (create/update) EAV data for a project.
     * $attributes = [ [ "attribute_id" => 1, "value" => "IT" ], ... ]
     */
    private function syncAttributeValues(Project $project, array $attributes)
    {
        foreach ($attributes as $attr) {
            // Validate each entry if needed
            // Or do a second pass to ensure attribute_id is valid, etc.
    
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
