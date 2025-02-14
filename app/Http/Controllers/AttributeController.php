<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Traits\ValidationTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AttributeController extends Controller
{
    use ValidationTrait;

    /**
     * Require authentication for all endpoints.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * GET /api/attributes
     */
    public function index()
    {
        return response()->json(Attribute::all(), Response::HTTP_OK);
    }

    /**
     * POST /api/attributes
     */
    public function store(Request $request)
    {
        $data = $this->runValidation($request, [
            'name' => 'required|string|unique:attributes,name',
            'type' => 'required|string',
        ]);

        $attribute = Attribute::create([
            'name' => $data['name'],
            'type' => $data['type'],
        ]);

        return response()->json($attribute, Response::HTTP_CREATED);
    }

    /**
     * GET /api/attributes/{id}
     */
    public function show($id)
    {
        $attribute = Attribute::findOrFail($id);
        return response()->json($attribute, Response::HTTP_OK);
    }

    /**
     * PUT /api/attributes/{id}
     */
    public function update(Request $request, $id)
    {
        $attribute = Attribute::findOrFail($id);

        // We allow updates to name or type, but name must remain unique
        $data = $this->runValidation($request, [
            'name' => 'sometimes|string|unique:attributes,name,' . $attribute->id,
            'type' => 'sometimes|string',
        ]);

        $attribute->update($data);

        return response()->json($attribute, Response::HTTP_OK);
    }

    /**
     * DELETE /api/attributes/{id}
     */
    public function destroy($id)
    {
        $attribute = Attribute::findOrFail($id);
        $attribute->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
