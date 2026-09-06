<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\JabatanResource;
use App\Models\Jabatan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class JabatanController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Jabatan::query()->orderBy('nama');

        match ($request->input('status')) {
            'all' => null,
            'inactive' => $query->where('is_active', false),
            default => $query->where('is_active', true),
        };

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->input('jenis'));
        }

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>=', $request->date('updated_since'));
        }

        return JabatanResource::collection($query->paginate($request->integer('per_page', 50)));
    }

    public function show(Jabatan $jabatan): JabatanResource
    {
        return new JabatanResource($jabatan);
    }
}
