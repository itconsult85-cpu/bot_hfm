<?php

namespace App\Controllers;

use App\Models\MediaModel;

class MediaAdmin extends BaseController
{
    public function index()
    {
        $model = new MediaModel();
        $data['media'] = $model->findAll();
        
        return view('admin_media', $data);
    }

    // --- FUNGSI UPLOAD ---
public function upload()
{
    $model = new MediaModel();
    $keyword = $this->request->getPost('keyword');
    $teks = $this->request->getPost('teks_panduan');

    $gambar = $this->request->getFile('gambar');
    $video = $this->request->getFile('video');

    $url_gambar = '';
    $url_video = '';
    $path = FCPATH . 'media';

    if (!is_dir($path)) mkdir($path, 0777, true);

    // PROSES GAMBAR DENGAN NAMA ASLI
    if ($gambar && $gambar->isValid() && !$gambar->hasMoved()) {
        $namaGambar = $gambar->getName(); // Pakai nama asli
        $gambar->move($path, $namaGambar);
        $url_gambar = base_url('media/' . $namaGambar);
    }

    // PROSES VIDEO DENGAN NAMA ASLI
    if ($video && $video->isValid() && !$video->hasMoved()) {
        $namaVideo = $video->getName(); // Pakai nama asli
        $video->move($path, $namaVideo);
        $url_video = base_url('media/' . $namaVideo);
    }

    $model->insert([
        'keyword' => strtolower($keyword),
        'teks_panduan' => $teks,
        'url_gambar' => $url_gambar,
        'url_video' => $url_video
    ]);

    return redirect()->to(base_url('MediaAdmin'))->with('sukses', 'Data berhasil ditambah dengan nama file asli!');
}

// --- FUNGSI UPDATE ---
public function update()
{
    $model = new MediaModel();
    $id = $this->request->getPost('id');
    
    // Ambil data lama dari database
    $oldData = $model->find($id);
    if (!$oldData) {
        return redirect()->back()->with('error', 'Data tidak ditemukan!');
    }

    $url_gambar = $oldData['url_gambar'];
    $url_video = $oldData['url_video'];
    
    // ALAMAT ABSOLUT: Menggunakan FCPATH agar tepat mengarah ke folder public/media
    $storagePath = FCPATH . 'media/';

    $gambar = $this->request->getFile('gambar');
    $video = $this->request->getFile('video');

    // --- PROSES UPDATE GAMBAR ---
    if ($gambar && $gambar->isValid() && !$gambar->hasMoved()) {
        // Hapus file lama jika kolom url_gambar tidak kosong
        if (!empty($oldData['url_gambar'])) {
            $namaFileLama = basename($oldData['url_gambar']);
            $fullPathLama = $storagePath . $namaFileLama;
            
            if (file_exists($fullPathLama)) {
                unlink($fullPathLama);
            }
        }
        
        $namaGambar = $gambar->getName();
        $gambar->move($storagePath, $namaGambar);
        $url_gambar = base_url('media/' . $namaGambar);
    }

    // --- PROSES UPDATE VIDEO ---
    if ($video && $video->isValid() && !$video->hasMoved()) {
        // Hapus file lama jika kolom url_video tidak kosong
        if (!empty($oldData['url_video'])) {
            $namaVideoLama = basename($oldData['url_video']);
            $fullPathVideoLama = $storagePath . $namaVideoLama;
            
            // LOG DEBUG (Opsional): Bisa cek log jika tetap tidak terhapus
            if (file_exists($fullPathVideoLama)) {
                unlink($fullPathVideoLama);
            }
        }

        $namaVideo = $video->getName();
        $video->move($storagePath, $namaVideo);
        $url_video = base_url('media/' . $namaVideo);
    }

    $model->update($id, [
        'keyword' => strtolower($this->request->getPost('keyword')),
        'teks_panduan' => $this->request->getPost('teks_panduan'),
        'url_gambar' => $url_gambar,
        'url_video' => $url_video
    ]);

    return redirect()->to(base_url('MediaAdmin'))->with('sukses', 'Update berhasil & file lama telah dibersihkan.');
}

    public function hapus()
{
    // Ambil ID dari POST
    $id = $this->request->getPost('id');

    if (!$id) {
        return redirect()->back()->with('error', 'ID tidak valid');
    }

    $model = new MediaModel();
    $data = $model->find($id);

    if ($data) {
        // Hapus file fisik (Gambar)
        if (!empty($data['url_gambar'])) {
            $fileGambar = FCPATH . 'media/' . basename($data['url_gambar']);
            if (file_exists($fileGambar)) {
                unlink($fileGambar);
            }
        }

        // Hapus file fisik (Video)
        if (!empty($data['url_video'])) {
            $fileVideo = FCPATH . 'media/' . basename($data['url_video']);
            if (file_exists($fileVideo)) {
                unlink($fileVideo);
            }
        }

        // Hapus data dari database menggunakan model
        $model->delete($id);
        
        return redirect()->to(base_url('MediaAdmin'))->with('sukses', 'Data dan file fisik berhasil dihapus!');
    }

    return redirect()->to(base_url('MediaAdmin'))->with('error', 'Data tidak ditemukan!');
}
}