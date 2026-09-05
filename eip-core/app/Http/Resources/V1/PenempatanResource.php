<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenempatanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pegawai_id' => $this->pegawai_id,
            'unit_kerja_id' => $this->unit_kerja_id,
            'unit_kerja_nama' => $this->whenLoaded('unitKerja', fn () => $this->unitKerja?->nama),
            'jabatan_id' => $this->jabatan_id,
            'jabatan_nama' => $this->whenLoaded('jabatan', fn () => $this->jabatan?->nama),
            'tgl_mulai' => $this->tgl_mulai?->toDateString(),
            'tgl_selesai' => $this->tgl_selesai?->toDateString(),
            'is_posisi_utama' => $this->is_posisi_utama,
            'status' => $this->status->value,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
