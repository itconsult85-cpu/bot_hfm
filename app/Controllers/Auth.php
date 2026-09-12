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
        $model = new AdminModel();
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $admin = $model->where('username', $username)->first();

        if ($admin) {
            if (password_verify($password, $admin['password'])) {
                session()->set([
                    'id'          => $admin['id'],
                    'username'    => $admin['username'],
                    'nama'        => $admin['nama_lengkap'],
                    'isLoggedIn'  => true,
                ]);
                return redirect()->to(base_url('AdminDashboard'));
            } else {
                return redirect()->back()->with('error', 'Password salah.');
            }
        } else {
            return redirect()->back()->with('error', 'Username tidak ditemukan.');
        }
    }

    public function logout()
    {
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