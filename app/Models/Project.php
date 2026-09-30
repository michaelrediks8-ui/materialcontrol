<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable; 

class Project extends Model
{
    use Searchable; 

    protected $fillable = ['nama_project', 'kode_project', 'aktif'];

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'kode_project' => $this->kode_project,
            'nama_project' => $this->nama_project,
        ];
    }
}