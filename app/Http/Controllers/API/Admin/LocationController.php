<?php

namespace App\Http\Controllers\API\Admin;

use App\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Location;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->query('page');
        $perPage = $request->query('per_page');
        $key = $request->query('key'); //get all without parent child structure
        $query = Location::query();

        if ($request->query('trashed')) {
            $query->inActive()->orderBy('updated_at', 'desc');
        } else {
            $query->active()->orderBy('id', 'desc');
        }

        if (!$key || $key != "not_tree") {
            $query->whereNull('parent')->with('childLocations');
        }

        if ($page && $perPage) {
            $locations = $query->paginate($perPage, ['*'], 'page', $page);
        } else {
            $locations = $query->get();
        }

        return ApiResponse::success($locations, 'Get all locations ', 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:locations,name'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(null, 'Field validation error', 400);
        }

        $model_class = Location::class;
        $slug = generateSlug($model_class, $request->name);

        $location = Location::create([
            "name" => $request->name,
            'slug' => $slug,
            "parent" => $request->parent_id ?? null,
            "status" => 1,
            "is_deleted" => 0
        ]);
        $parent_location = null;

        if (!empty($request->parent_id)) {
            $parent_location = Location::where('id', $request->parent_id)->first(['id', 'name', 'slug', 'status', 'is_deleted']);
        }

        $location = [
            'id' => $location->id,
            'name' => $location->name,
            'slug' => $location->slug,
            'status' => $location->status,
            'is_deleted' => $location->is_deleted,
            'parent' => $parent_location ? [
                'id' => $parent_location->id,
                'name' => $parent_location->name,
                'status' => $parent_location->status,
                'is_deleted' => $parent_location->is_deleted
            ] : null
        ];

        return ApiResponse::success($location, 'Location create successful', 200);
    }

    public function show(string $id)
    {
        $location = Location::where('id', $id)->first(['id', 'name', 'status', 'parent', 'is_deleted']);

        if (!empty($location)) {
            if ($location->parent != null) {
                $parent_location = Location::where('id', $location->parent)->first(['id', 'name', 'status', 'is_deleted']);
                $location->parent = $parent_location ?? null;
            }
            return ApiResponse::success($location, 'Location detail successful', 200);
        }

        return ApiResponse::error(null, 'No location found with your provided id', 404);
    }

    public function update(Request $request, string $id)
    {
        $location = Location::find($id);

        if ($location) {
            $location->update([
                'name' => $request->name ?? $location->name,
                'parent' => $request->parent_id ?? $location->parent,
                'status' => 1,
                'is_deleted' => 0
            ]);

            $parent_location = null;
            if ($request->has('parent_id')) {
                $parent_location = Location::where('id', $request->parent_id)->first(['id', 'name', 'status', 'is_deleted']);
            }

            $location = [
                "id" => $location->id,
                "name" => $location->name,
                "status" => $location->status,
                "is_deleted" => $location->is_deleted,
                "parent" => $parent_location ?  [
                    "id" => $parent_location->id,
                    "name" => $parent_location->name,
                    "status" => $parent_location->status,
                    "is_deleted" => $parent_location->is_deleted
                ] : null
            ];

            return ApiResponse::success($location, 'Location update successful', 200);
        }

        return ApiResponse::error(null, 'No location found with your provided id', 404);
    }

    public function destroy(string $id)
    {
        $location = Location::find($id);
        if ($location) {
            $location->update([
                'is_deleted' => 1
            ]);
            return ApiResponse::success(null, 'Location deleted successfully', 200);
        } else {
            return ApiResponse::error(null, 'No location found with your provided id', 404);
        }
    }

    public function unArchive(string $id)
    {
        $location = Location::find($id);
        if ($location) {
            $location->update([
                'is_deleted' => 0
            ]);
            return ApiResponse::success(null, 'Location unarchived successful', 200);
        } else {
            return ApiResponse::error(null, 'No location found with your provided id', 404);
        }
    }

    public function permanentDestroy(string $id)
    {
        $location = Location::find($id);

        if ($location) {
            $location->delete();
            return ApiResponse::success(null, 'Location permanently deleted', 200);
        } else {
            return ApiResponse::error(null, 'No location found with your provided id', 404);
        }
    }
}
