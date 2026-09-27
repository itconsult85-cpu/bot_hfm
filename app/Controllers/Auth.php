<?php

namespace App\Controllers;

use App\Models\AdminModel;

class Auth extends BaseController
{
    public function index()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to(base_url('AdminDashboard'));
        }
        return view('auth_login');
    }

    public function loginProcess()
    {
        $session = session();
        $now = time();
        $attempts = array_values(array_filter(
            (array) $session->get('login_attempts'),
            static fn ($timestamp) => is_int($timestamp) && ($now - $timestamp) < 900
        ));
        if (count($attempts) >= 5) {
            return redirect()->back()->with('error', 'Terlalu banyak percobaan login. Coba lagi dalam 15 menit.');
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $admin = (new AdminModel())->where('username', $username)->first();

        if (!$admin || !password_verify($password, (string) $admin['password'])) {
            $attempts[] = $now;
            $session->set('login_attempts', $attempts);
            return redirect()->back()->with('error', 'Username atau password salah.');
        }

        $session->remove('login_attempts');
        $session->regenerate(true);
        $session->set([
            'id'         => $admin['id'],
            'username'   => $admin['username'],
            'nama'       => $admin['nama_lengkap'],
            'isLoggedIn' => true,
        ]);
        return redirect()->to(base_url('AdminDashboard'));
    }

    public function logout()
    {
        session()->regenerate(true);
        session()->destroy();
        return redirect()->to(base_url('auth'));
    }

    // public function setup()
    // {
    //     $model = new AdminModel();
        
    //     // Bersihkan data admin lama yang error
    //     $model->where('username', 'admin')->delete();
        
    //     // Bikin admin baru dengan password yang dienkripsi langsung oleh server Kakak
    //     $model->insert([
    //         'username'     => 'admin',
    //         'password'     => password_hash('admin123', PASSWORD_DEFAULT),
    //         'nama_lengkap' => 'Administrator Utama'
    //     ]);

    //     echo "✅ Akun Admin berhasil di-reset! <br><br>";
    //     echo "Silakan login menggunakan:<br>";
    //     echo "Username: <b>admin</b> <br>";
    //     echo "Password: <b>admin123</b> <br><br>";
    //     echo "<a href='" . base_url('auth') . "'>Klik di sini untuk Login</a>";
    // }
}