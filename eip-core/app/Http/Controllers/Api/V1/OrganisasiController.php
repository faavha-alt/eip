<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrganisasiResource;
use App\Models\Organisasi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganisasiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Organisasi::query()->orderBy('nama');

        match ($request->input('status')) {
            'all' => null,
            'inactive' => $query->where('is_active', false),
            default => $query->where('is_active', true),
        };

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>=', $request->date('updated_since'));
        }

        return OrganisasiResource::collection($query->paginate($request->integer('per_page', 50)));
    }

    public function show(Organisasi $organisasi): OrganisasiResource
    {
        return new OrganisasiResource($organisasi);
    }
}
