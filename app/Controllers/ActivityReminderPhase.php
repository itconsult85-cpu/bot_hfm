<?php

namespace App\Controllers;

use Config\Database;

class ActivityReminderPhase extends BaseController
{
    public function index()
    {
        return view('activity_reminder_phases/index', [
            'title' => 'Pengaturan Fase Pengingat',
            'phases' => Database::connect()->table('activity_reminder_phases')->orderBy('days_after_join', 'ASC')->get()->getResultArray(),
        ]);
    }

    public function store()
    {
        $data = $this->payload();
        Database::connect()->table('activity_reminder_phases')->insert($data);
        return redirect()->to('/activity-reminder-phases')->with('pesan', 'Fase berhasil ditambahkan.');
    }

    public function update($id)
    {
        Database::connect()->table('activity_reminder_phases')->where('id', (int) $id)->update($this->payload());
        return redirect()->to('/activity-reminder-phases')->with('pesan', 'Fase berhasil diperbarui.');
    }

    public function delete($id)
    {
        Database::connect()->table('activity_reminder_phases')->where('id', (int) $id)->delete();
        return redirect()->to('/activity-reminder-phases')->with('pesan', 'Fase berhasil dihapus.');
    }

    private function payload(): array
    {
        $key = preg_replace('/[^a-z0-9_-]/i', '_', trim((string) $this->request->getPost('phase_key')));
        return [
            'phase_key' => strtolower($key),
            'phase_name' => trim((string) $this->request->getPost('phase_name')),
            'days_after_join' => max(0, (int) $this->request->getPost('days_after_join')),
            'message' => (string) $this->request->getPost('message'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
        ];
    }
}
