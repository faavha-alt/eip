<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UnitKerjaResource;
use App\Models\UnitKerja;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UnitKerjaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = UnitKerja::query()->with('kepala')->orderBy('nama');

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        }

        if ($request->filled('jenis_unit')) {
            $query->where('jenis_unit', $request->input('jenis_unit'));
        }

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>=', $request->date('updated_since'));
        }

        return UnitKerjaResource::collection($query->paginate($request->integer('per_page', 50)));
    }

    public function show(UnitKerja $unitKerja): UnitKerjaResource
    {
        return new UnitKerjaResource($unitKerja->load('kepala'));
    }
}
