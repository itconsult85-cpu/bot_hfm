
    const kickLepasIbForm = document.getElementById('kickLepasIbForm');
    if (kickLepasIbForm) {
        kickLepasIbForm.addEventListener('submit', event => {
            const count = Number(kickLepasIbForm.dataset.candidateCount || 0);
            const groupName = kickLepasIbForm.dataset.targetGroup || 'grup Telegram VIP aktif';
            const confirmed = window.confirm(
                `Konfirmasi tindakan massal:\n\nSistem akan memeriksa ${count} akun berstatus Lepas IB untuk grup "${groupName}". Hanya anggota yang masih berada di grup dan bukan admin/pemilik yang akan dikeluarkan. Status IB di database tidak diubah. Mereka mungkin dapat bergabung kembali jika masih memiliki tautan undangan.\n\nLanjutkan?`
            );
            if (!confirmed) {
                event.preventDefault();
                return;
            }
            const button = document.getElementById('kickLepasIbButton');
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memproses...';
        });
    }

    function showDetail(wa, telegram, nama, tipe, waktu, idTrading) {
        document.getElementById('detailWa').textContent = wa;
        document.getElementById('detailTelegram').textContent = telegram;
        document.getElementById('detailNama').textContent = nama;
        document.getElementById('detailIdTrading').textContent = idTrading;

        const tipeMap = {
            'masuk': 'Masuk',
            'keluar_sendiri': 'Keluar (Sendiri)',
            'keluar_di_remove': 'Keluar (Di-Remove)',
            'lepas_ib': 'Lepas IB'
        };
        document.getElementById('detailTipe').textContent = tipeMap[tipe] || tipe;

        const date = new Date(waktu);
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        document.getElementById('detailWaktu').textContent = date.toLocaleDateString('id-ID', options);

        new bootstrap.Modal(document.getElementById('modalDetail')).show();
    }

    function editIdLog(idHfm) {
        if (!idHfm || idHfm === '-') {
            alert('Tidak ada ID Trading yang terdeteksi untuk diedit.');
            return;
        }
        document.getElementById('quick_old_id').value = idHfm;
        document.getElementById('quick_new_id').value = idHfm;

        new bootstrap.Modal(document.getElementById('modalEditId')).show();
    }

    document.querySelectorAll('.form-control, .form-select').forEach(el => {
        el.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
    });

    document.querySelectorAll('.btn-hapus-log').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama');

            document.getElementById('teksNamaHapusLog').innerText = nama;

            const linkHapus = document.getElementById('linkHapusLogModal');
            if (linkHapus) linkHapus.href = "/AdminDashboard/hapusLog/" + id;
        });
    });
