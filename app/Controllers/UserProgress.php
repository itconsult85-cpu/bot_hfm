<?php

namespace App\Controllers;

use App\Models\UserProgressModel;

class UserProgress extends BaseController
{
    protected $progressModel;

    public function __construct()
    {
        $this->progressModel = new UserProgressModel();
    }

    public function index()
    {
        $search = $this->request->getGet('search');
        $step = $this->request->getGet('step');

        if (!empty($search)) {
            $this->progressModel->groupStart()
                ->like('user_name', $search)
                ->orLike('user_id', $search)
                ->groupEnd();
        }

        if (!empty($step)) {
            $this->progressModel->where('current_step', $step);
        }

        $this->progressModel->orderBy('last_active', 'DESC');

        $data = [
            'title'       => 'Progress Pendaftar Bot',
            'progress'    => $this->progressModel->paginate(10),
            'pager'       => $this->progressModel->pager,
            'search'      => $search,
            'step_filter' => $step
        ];

        return view('user_progress/index', $data);
    }

    public function delete($user_id)
    {
        $this->progressModel->delete($user_id);

        $urlBot = "http://127.0.0.1:3000/reset-user";

        $ch = curl_init($urlBot);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['user_id' => $user_id]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_exec($ch);
        curl_close($ch);

        return redirect()->to('/user-progress')->with('pesan', 'Data dihapus & Memori Bot otomatis di-reset!');
    }
}
