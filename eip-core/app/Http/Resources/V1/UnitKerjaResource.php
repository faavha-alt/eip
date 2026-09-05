<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitKerjaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organisasi_id' => $this->organisasi_id,
            'parent_id' => $this->parent_id,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'jenis_unit' => $this->jenis_unit->value,
            'kepala_id' => $this->kepala_id,
            'kepala_nama' => $this->whenLoaded('kepala', fn () => $this->kepala?->nama_lengkap),
            'is_active' => $this->is_active,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
