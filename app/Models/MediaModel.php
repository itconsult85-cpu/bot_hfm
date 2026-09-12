<?php

namespace App\Models;

use CodeIgniter\Model;

class MediaModel extends Model
{
    protected $table            = 'tb_panduan_media';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['keyword', 'teks_panduan', 'url_gambar', 'url_video'];
    protected $useAutoIncrement = true;
}