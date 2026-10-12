
    document.querySelectorAll('.btn-hapus').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama') || 'Member BOSSCUAN';
            document.getElementById('teksIdHapus').innerText = id;
            document.getElementById('teksNamaHapus').innerText = nama;
            const linkHapus = document.getElementById('linkHapusModal');
            if (linkHapus) linkHapus.href = "mock" + id;
        });
    });

    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_old_id').value = this.getAttribute('data-id');
            document.getElementById('edit_wa').value = this.getAttribute('data-wa');
            document.getElementById('edit_currency').value = this.getAttribute('data-currency');
            document.getElementById('edit_deposit').value = this.getAttribute('data-deposit');
            document.getElementById('edit_status').value = this.getAttribute('data-status');
            document.getElementById('edit_trade').value = this.getAttribute('data-trade');
            new bootstrap.Modal(document.getElementById('modalEdit')).show();
        });
    });

    async function syncData(btn, id_hfm, isBulk = false) {
        try {
            btn.innerHTML = '<i class="bi bi-hourglass-split spinner-border spinner-border-sm me-1"></i> Loading...';
            btn.disabled = true;
            btn.classList.replace('btn-outline-primary', 'btn-light');

            const targetUrl = "mock" + id_hfm;
            const response = await fetch(targetUrl);

            if (!response.ok) {
                alert("Server Error! HTTP Status: " + response.status);
                throw new Error("HTTP Error " + response.status);
            }

            const resText = await response.text();
            let res;
            try {
                res = JSON.parse(resText);
            } catch (e) {
                alert("Sistem Error! Server tidak mengembalikan data JSON.");
                throw new Error("Invalid JSON");
            }

            if (res.status === 'sukses') {
                let currency = (res.data.currency && res.data.currency !== '') ? res.data.currency : 'USD';
                let depoVal = parseFloat(res.data.deposit);
                let depoFormatted = currency === 'IDR' ? currency + ' ' + depoVal.toLocaleString('id-ID') : currency + ' ' + depoVal.toFixed(2);

                let depoEl = document.getElementById('depo-' + id_hfm);
                if (depoEl) depoEl.innerText = depoFormatted;

                if (res.data.nama) {
                    let elemNama = document.getElementById('nama-' + id_hfm);
                    if (elemNama) elemNama.innerText = res.data.nama;
                }

                if (Object.prototype.hasOwnProperty.call(res.data, 'email')) {
                    const emailEl = document.getElementById('email-' + id_hfm);
                    if (emailEl) emailEl.innerText = res.data.email || '-';
                }
                if (Object.prototype.hasOwnProperty.call(res.data, 'campaign')) {
                    const campaignEl = document.getElementById('campaign-' + id_hfm);
                    if (campaignEl) campaignEl.innerText = res.data.campaign || '-';
                }

                let rawTrade = res.data.last_trade;
                let tradeEl = document.getElementById('trade-' + id_hfm);
                if (tradeEl) {
                    if (rawTrade && rawTrade !== '0000-00-00 00:00:00' && rawTrade !== 'N/A') {
                        if (rawTrade.includes('-')) {
                            let parts = rawTrade.split(' ')[0].split('-');
                            let bulanIndo = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
                            let tgl = parseInt(parts[2]) + ' ' + bulanIndo[parseInt(parts[1]) - 1] + ' ' + parts[0];
                            tradeEl.innerHTML = `<span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill fw-semibold small"><i class="bi bi-graph-up me-1"></i>${tgl}</span>`;
                        }
                    } else {
                        tradeEl.innerHTML = `<span class="badge bg-light border text-muted rounded-pill small">Belum Trading</span>`;
                    }
                }

                let statusEl = document.getElementById('status-' + id_hfm);
                if (statusEl) statusEl.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 small fw-semibold">Aktif</span>';

                const row = document.querySelector(`.member-item[data-idhfm="${id_hfm}"]`);
                if (row) {
                    row.setAttribute('data-status', 'aktif');
                    if (Object.prototype.hasOwnProperty.call(res.data, 'email')) row.setAttribute('data-email', String(res.data.email || '').toLowerCase());
                    if (Object.prototype.hasOwnProperty.call(res.data, 'campaign')) row.setAttribute('data-campaign', String(res.data.campaign || '').toLowerCase());
                }

            } else if (res.status === 'lepas_ib') {
                let statusEl = document.getElementById('status-' + id_hfm);
                if (statusEl) statusEl.innerHTML = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 small fw-semibold">Lepas IB</span>';

                const row = document.querySelector(`.member-item[data-idhfm="${id_hfm}"]`);
                if (row) row.setAttribute('data-status', 'lepas_ib');

            } else {
                alert("Gagal Sync: " + (res.pesan || "Terjadi kesalahan sistem"));
            }

            if (!isBulk && typeof window.applyFilters === 'function') window.applyFilters();

        } catch (err) {
            console.error('Terjadi Error di JS:', err);
        } finally {
            btn.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> Selesai';
            setTimeout(() => {
                btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Cek API';
                btn.disabled = false;
                btn.classList.replace('btn-light', 'btn-outline-primary');
            }, 2000);
        }
    }

    async function syncSemua() {
        const btnSemua = document.getElementById('btnSyncSemua');

        const itemsToSync = typeof window.getFilteredItems === 'function' ?
            window.getFilteredItems() :
            Array.from(document.querySelectorAll('.member-item'));

        if (itemsToSync.length === 0) {
            alert("Tidak ada data member untuk di-sync.");
            return;
        }

        btnSemua.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyinkronkan...';
        btnSemua.disabled = true;

        for (let i = 0; i < itemsToSync.length; i++) {
            let item = itemsToSync[i];
            let btn = item.querySelector('.btn-sync-single');
            let id_hfm = item.getAttribute('data-idhfm');

            if (btn && id_hfm) {
                await syncData(btn, id_hfm, true);

                await new Promise(resolve => setTimeout(resolve, 1000));
            }
        }

        if (typeof window.applyFilters === 'function') window.applyFilters();

        btnSemua.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> Sinkronisasi Selesai';
        btnSemua.classList.replace('btn-primary', 'btn-success');

        setTimeout(() => {
            btnSemua.innerHTML = '<i class="bi bi-cloud-arrow-down-fill me-2"></i> Sync Semua';
            btnSemua.classList.replace('btn-success', 'btn-primary');
            btnSemua.disabled = false;
        }, 3000);
    }

    document.addEventListener("DOMContentLoaded", function() {
        const itemsPerPage = 100;
        let currentPage = 1;
        const allItems = Array.from(document.querySelectorAll('.member-item'));
        let filteredItems = [...allItems];
        window.getFilteredItems = () => filteredItems;
        const paginationControls = document.getElementById('paginationControls');
        const pageInfo = document.getElementById('pageInfo');

        function renderPage(page) {
            currentPage = page;
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            allItems.forEach(item => item.classList.add('d-none'));
            const paginatedItems = filteredItems.slice(start, end);
            paginatedItems.forEach(item => item.classList.remove('d-none'));
            updatePaginationInfo();
            renderPaginationControls();
        }

        function updatePaginationInfo() {
            const total = filteredItems.length;
            if (total === 0) {
                pageInfo.innerHTML = "Tidak ada data ditemukan.";
                return;
            }
            const start = ((currentPage - 1) * itemsPerPage) + 1;
            const end = Math.min(currentPage * itemsPerPage, total);
            pageInfo.innerHTML = `Menampilkan <b>${start}</b> hingga <b>${end}</b> dari <b>${total}</b> data`;
        }

        function renderPaginationControls() {
            paginationControls.innerHTML = '';
            const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
            if (totalPages <= 1) return;

            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = currentPage === 1 ?
                `<span class="page-link shadow-sm rounded-start-pill" aria-hidden="true">&laquo;</span>` :
                `<a class="page-link shadow-sm rounded-start-pill" href="#" aria-label="Halaman sebelumnya">&laquo;</a>`;
            prevLi.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage > 1) renderPage(currentPage - 1);
            });
            paginationControls.appendChild(prevLi);

            let maxVisibleButtons = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisibleButtons / 2));
            let endPage = Math.min(totalPages, startPage + maxVisibleButtons - 1);

            if (endPage - startPage + 1 < maxVisibleButtons) {
                startPage = Math.max(1, endPage - maxVisibleButtons + 1);
            }

            if (startPage > 1) {
                const firstLi = document.createElement('li');
                firstLi.className = `page-item`;
                firstLi.innerHTML = `<a class="page-link shadow-sm" href="#" aria-label="Halaman pertama">1</a>`;
                firstLi.addEventListener('click', (e) => {
                    e.preventDefault();
                    renderPage(1);
                });
                paginationControls.appendChild(firstLi);

                if (startPage > 2) {
                    const dots = document.createElement('li');
                    dots.className = `page-item disabled`;
                    dots.innerHTML = `<span class="page-link shadow-sm border-0 text-muted">...</span>`;
                    paginationControls.appendChild(dots);
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link shadow-sm" href="#" ${i === currentPage ? 'aria-current="page"' : ''}>${i}</a>`;
                li.addEventListener('click', (e) => {
                    e.preventDefault();
                    renderPage(i);
                });
                paginationControls.appendChild(li);
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const dots = document.createElement('li');
                    dots.className = `page-item disabled`;
                    dots.innerHTML = `<span class="page-link shadow-sm border-0 text-muted">...</span>`;
                    paginationControls.appendChild(dots);
                }
                const lastLi = document.createElement('li');
                lastLi.className = `page-item`;
                lastLi.innerHTML = `<a class="page-link shadow-sm" href="#" aria-label="Halaman terakhir">${totalPages}</a>`;
                lastLi.addEventListener('click', (e) => {
                    e.preventDefault();
                    renderPage(totalPages);
                });
                paginationControls.appendChild(lastLi);
            }

            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = currentPage === totalPages ?
                `<span class="page-link shadow-sm rounded-end-pill" aria-hidden="true">&raquo;</span>` :
                `<a class="page-link shadow-sm rounded-end-pill" href="#" aria-label="Halaman berikutnya">&raquo;</a>`;
            nextLi.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage < totalPages) renderPage(currentPage + 1);
            });
            paginationControls.appendChild(nextLi);
        }

        const searchInput = document.getElementById('searchInput');
        const filterStatus = document.getElementById('filterStatus');
        const filterCurrency = document.getElementById('filterCurrency');

        window.applyFilters = function() {
            const keyword = searchInput ? searchInput.value.toLowerCase() : '';
            const statusVal = filterStatus ? filterStatus.value.toLowerCase() : '';
            const currencyVal = filterCurrency ? filterCurrency.value.toLowerCase() : '';

            filteredItems = allItems.filter(item => {
                const nama = item.getAttribute('data-nama') || '';
                const idhfm = item.getAttribute('data-idhfm') || '';
                const nowa = item.getAttribute('data-nowa') || '';
                const telegram = item.getAttribute('data-idtelegram') || '';
                const email = item.getAttribute('data-email') || '';
                const campaign = item.getAttribute('data-campaign') || '';
                const status = (item.getAttribute('data-status') || '').toLowerCase();
                const currency = (item.getAttribute('data-currency') || '').toLowerCase();

                const matchText = nama.includes(keyword) || idhfm.includes(keyword) || nowa.includes(keyword) || telegram.includes(keyword) || email.includes(keyword) || campaign.includes(keyword);
                const matchStatus = statusVal === '' || status === statusVal;
                const matchCurrency = currencyVal === '' || currency === currencyVal;

                return matchText && matchStatus && matchCurrency;
            });

            renderPage(1);
        };

        if (searchInput) searchInput.addEventListener('input', window.applyFilters);
        if (filterStatus) filterStatus.addEventListener('change', window.applyFilters);
        if (filterCurrency) filterCurrency.addEventListener('change', window.applyFilters);

        renderPage(1);

        const formEdit = document.getElementById('formEditMember');
        if (formEdit) {
            formEdit.addEventListener('submit', async function(e) {
                e.preventDefault();

                const btnSubmit = this.querySelector('button[type="submit"]');
                const originalText = btnSubmit.innerHTML;
                btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
                btnSubmit.disabled = true;

                const formData = new FormData(this);

                try {
                    const targetUrl = "mock";
                    const response = await fetch(targetUrl, {
                        method: 'POST',
                        body: formData
                    });

                    const res = await response.json();

                    if (res.status === 'sukses') {
                        const oldId = formData.get('old_id_hfm');
                        const newId = formData.get('id_hfm');
                        const statusVal = formData.get('status');
                        const currencyVal = formData.get('currency');
                        const depoVal = formData.get('deposit');
                        const noWaVal = formData.get('no_wa');
                        const lastTradeVal = formData.get('last_trade');

                        const row = document.querySelector(`.member-item[data-idhfm="${oldId}"]`);

                        if (row) {
                            row.setAttribute('data-idhfm', newId);
                            row.setAttribute('data-nowa', noWaVal);
                            row.setAttribute('data-currency', currencyVal);
                            row.setAttribute('data-status', statusVal);

                            const depoEl = row.querySelector('[id^="depo-"]');
                            if (depoEl) depoEl.id = 'depo-' + newId;

                            const tradeEl = row.querySelector('[id^="trade-"]');
                            if (tradeEl) tradeEl.id = 'trade-' + newId;

                            const statusElContainer = row.querySelector('[id^="status-"]');
                            if (statusElContainer) statusElContainer.id = 'status-' + newId;

                            const namaEl = row.querySelector('[id^="nama-"]');
                            if (namaEl) namaEl.id = 'nama-' + newId;

                            if (depoEl) {
                                depoEl.innerText = `${currencyVal} ${parseFloat(depoVal).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                            }

                            if (statusElContainer) {
                                if (statusVal === 'aktif') {
                                    statusElContainer.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 small fw-semibold">Aktif</span>';
                                } else {
                                    statusElContainer.innerHTML = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 small fw-semibold">Lepas IB</span>';
                                }
                            }

                            const hfmTextEl = row.querySelector('.text-muted.small.mt-1 div:nth-child(1)');
                            if (hfmTextEl) hfmTextEl.innerHTML = `<i class="bi bi-hash text-primary"></i> ${newId}`;

                            const waTextEl = row.querySelector('.text-muted.small.mt-1 div:nth-child(2)');
                            if (waTextEl) {
                                // Karena sekarang WA dan Telegram beda baris (div), kita hanya update teks WA-nya saja
                                waTextEl.innerHTML = `<i class="bi bi-whatsapp text-success"></i> ${noWaVal}`;
                            }

                            const btnEditElement = row.querySelector('.btn-edit');
                            if (btnEditElement) {
                                btnEditElement.setAttribute('data-id', newId);
                                btnEditElement.setAttribute('data-wa', noWaVal);
                                btnEditElement.setAttribute('data-currency', currencyVal);
                                btnEditElement.setAttribute('data-deposit', depoVal);
                                btnEditElement.setAttribute('data-status', statusVal);
                                btnEditElement.setAttribute('data-trade', lastTradeVal);
                            }

                            const btnSync = row.querySelector('.btn-sync-single');
                            if (btnSync) {
                                btnSync.setAttribute('onclick', `syncData(this, '${newId}')`);
                            }
                        }

                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEdit'));
                        modal.hide();

                        window.applyFilters();

                        if (row) {
                            const btnSync = row.querySelector('.btn-sync-single');
                            if (btnSync) syncData(btnSync, newId);
                        }

                    } else {
                        alert("Gagal mengupdate: " + res.pesan);
                    }
                } catch (err) {
                    console.error("Terjadi error AJAX:", err);
                    alert("Gagal terhubung ke server.");
                } finally {
                    btnSubmit.innerHTML = originalText;
                    btnSubmit.disabled = false;
                }
            });
        }
    });

    document.addEventListener("DOMContentLoaded", function() {
        const qrModal = document.getElementById('modalQR');
        const qrCanvas = document.getElementById('qrCanvas');
        const qrContainer = document.getElementById('qrContainer');
        const botStatusBadge = document.getElementById('botStatusBadge');
        let botPollingInterval;
        const NODE_API_URL = "http://103.89.4.144:3000/api/bot-status";

        function checkBotStatus() {
            fetch(NODE_API_URL).then(res => res.json()).then(data => {
                if (data.status === "Waiting for Scan" && data.qr) {
                    qrContainer.classList.remove('d-none');
                    QRCode.toCanvas(qrCanvas, data.qr, {
                        width: 200,
                        margin: 1
                    });
                    botStatusBadge.className = "badge bg-warning text-dark p-2 px-3 rounded-pill";
                    botStatusBadge.innerHTML = "<i class='bi bi-hourglass-split me-1'></i> Menunggu Scan";
                } else if (data.status === "Connected") {
                    qrContainer.classList.add('d-none');
                    botStatusBadge.className = "badge bg-success p-2 px-3 rounded-pill";
                    botStatusBadge.innerHTML = "<i class='bi bi-check-circle-fill me-1'></i> Bot Terhubung";
                } else {
                    qrContainer.classList.add('d-none');
                    botStatusBadge.className = "badge bg-danger p-2 px-3 rounded-pill";
                    botStatusBadge.innerHTML = "<i class='bi bi-x-circle-fill me-1'></i> Terputus / Loading";
                }
            }).catch(() => {
                qrContainer.classList.add('d-none');
                botStatusBadge.className = "badge bg-secondary p-2 px-3 rounded-pill";
                botStatusBadge.innerHTML = "<i class='bi bi-wifi-off me-1'></i> Gagal Konek ke Node.js";
            });
        }
        if (qrModal) {
            qrModal.addEventListener('show.bs.modal', function() {
                checkBotStatus();
                botPollingInterval = setInterval(checkBotStatus, 3000);
            });
            qrModal.addEventListener('hide.bs.modal', function() {
                clearInterval(botPollingInterval);
            });
        }
    });

    async function restartBot() {
        if (!await showAppConfirm('Yakin ingin merestart bot WhatsApp?', {
                title: 'Restart bot WhatsApp',
                confirmText: 'Restart sekarang'
            })) return;
        fetch('http://103.89.4.144:3000/api/restart-bot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token: ''
                })
            })
            .then(res => res.json()).then(data => {
                alert(data.pesan || 'Restart berhasil');
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalQR'));
                if (modal) modal.hide();
            })
            .catch(err => alert('Gagal menghubungi server: ' + err.message));
    }

    async function resetSesi() {
        if (!await showAppConfirm('HATI-HATI!\nIni akan MENGHAPUS sesi WhatsApp bot.\nLanjutkan?', {
                title: 'Reset sesi WhatsApp',
                confirmText: 'Ya, reset sesi',
                danger: true
            })) return;
        fetch('http://103.89.4.144:3000/api/reset-sesi', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token: ''
                })
            })
            .then(res => res.json()).then(data => {
                alert(data.pesan || 'Sesi direset');
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalQR'));
                if (modal) modal.hide();
            })
            .catch(err => alert('Gagal menghubungi server: ' + err.message));
    }
