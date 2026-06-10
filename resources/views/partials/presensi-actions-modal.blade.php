<!-- Modal Edit Status -->
<div class="modal fade" id="editStatusModal" tabindex="-1" aria-labelledby="editStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-white border-bottom py-3">
                <h6 class="modal-title fw-bold text-dark" id="editStatusModalLabel">Edit Status Kehadiran</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editStatusForm">
                <input type="hidden" name="santri_id" id="edit_santri_id">
                <input type="hidden" name="tanggal" id="edit_tanggal">
                <input type="hidden" name="waktu_sholat" id="edit_waktu_sholat">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Status Kehadiran</label>
                        <select name="status" id="edit_status" class="form-select border-light bg-light rounded-3">
                            <option value="Hadir">Hadir</option>
                            <option value="Izin">Izin</option>
                            <option value="Alfa">Alpha</option>
                        </select>
                        <div class="form-text small mt-2">
                            Mengubah status menjadi <strong>Hadir</strong> akan mencatat waktu kehadiran saat ini.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-white btn-sm px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold" id="editSubmitBtn">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function editStatus(santriId, tanggal, waktuSholat, currentStatus) {
        document.getElementById('edit_santri_id').value = santriId;
        document.getElementById('edit_tanggal').value = tanggal;
        document.getElementById('edit_waktu_sholat').value = waktuSholat;
        document.getElementById('edit_status').value = currentStatus;
        
        var editModal = new bootstrap.Modal(document.getElementById('editStatusModal'));
        editModal.show();
    }

    // Handle edit form submission via AJAX
    document.getElementById('editStatusForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('editSubmitBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
        submitBtn.disabled = true;

        const santriId = document.getElementById('edit_santri_id').value;
        const tanggal = document.getElementById('edit_tanggal').value;
        const waktuSholat = document.getElementById('edit_waktu_sholat').value;
        const status = document.getElementById('edit_status').value;

        fetch('/api_presensi.php?action=update-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                santri_id: santriId,
                tanggal: tanggal,
                waktu_sholat: waktuSholat,
                status: status,
            })
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;

            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editStatusModal'));
                modal.hide();

                const row = document.querySelector(
                    `tr[data-santri-id="${santriId}"][data-tanggal="${tanggal}"][data-sholat="${waktuSholat}"]`
                );
                if (row) {
                    // Update status badge
                    const statusCell = row.querySelectorAll('td')[5];
                    if (statusCell) {
                        if (status === 'Alfa') {
                            statusCell.innerHTML = '<span class="badge badge-soft badge-soft-danger px-4">Alpha</span>';
                        } else if (status === 'Izin') {
                            statusCell.innerHTML = '<span class="badge badge-soft badge-soft-info px-4">Izin</span>';
                        } else {
                            statusCell.innerHTML = '<span class="badge badge-soft badge-soft-success px-4">Hadir</span>';
                        }
                    }

                    // Update waktu hadir
                    const waktuCell = row.querySelectorAll('td')[4];
                    if (waktuCell && data.data) {
                        const waktuHadir = data.data.waktu_hadir;
                        const d = new Date(tanggal + 'T00:00:00');
                        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                        const formattedDate = `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
                        if (waktuHadir) {
                            const displayTime = waktuHadir.substring(0, 5);
                            waktuCell.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <div class="fw-bold text-dark me-2">${displayTime}</div>
                                    <div class="small text-muted border-start ps-2">${formattedDate}</div>
                                </div>`;
                        } else {
                            waktuCell.innerHTML = `
                                <div class="d-flex align-items-center">
                                    <div class="fw-bold text-danger me-2">-</div>
                                    <div class="small text-muted border-start ps-2">${formattedDate}</div>
                                </div>`;
                        }
                    }

                    // Update edit button
                    const editBtn = row.querySelector('button[title="Edit Status"]');
                    if (editBtn) {
                        editBtn.setAttribute('onclick', `editStatus('${santriId}', '${tanggal}', '${waktuSholat}', '${status}')`);
                    }

                    row.classList.add('row-new-entry');
                    setTimeout(() => row.classList.remove('row-new-entry'), 2000);
                }

                showFlashMessage('success', data.message);
            } else {
                showFlashMessage('danger', data.message || 'Gagal memperbarui status.');
            }
        })
        .catch(err => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            console.error('Edit error:', err);
            showFlashMessage('danger', 'Terjadi kesalahan saat memperbarui status.');
        });
    });

    function deletePresensi(santriId, tanggal, waktuSholat) {
        if (!confirm('Apakah Anda yakin ingin menghapus data presensi ini?')) {
            return;
        }

        const row = document.querySelector(
            `tr[data-santri-id="${santriId}"][data-tanggal="${tanggal}"][data-sholat="${waktuSholat}"]`
        );
        
        if (row) {
            const deleteBtn = row.querySelector('button[title="Hapus"]');
            if (deleteBtn) {
                deleteBtn.disabled = true;
                deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm text-danger"></span>';
            }
        }

        fetch('/api_presensi.php?action=delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                santri_id: santriId,
                tanggal: tanggal,
                waktu_sholat: waktuSholat,
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (row) {
                    row.style.transition = 'opacity 0.4s ease-out, transform 0.4s ease-out';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(30px)';
                    setTimeout(() => {
                        row.remove();
                        const countEl = document.getElementById('recordCount');
                        const tbody = document.getElementById('kehadiranTbody');
                        if (countEl && tbody) {
                            const totalRows = tbody.querySelectorAll('tr[data-santri-id]').length;
                            if (totalRows > 0) {
                                countEl.textContent = `Menampilkan ${totalRows} data rekaman kehadiran terbaru.`;
                            } else {
                                tbody.innerHTML = `
                                    <tr id="emptyRow">
                                        <td colspan="8" class="text-center py-5">
                                            <div class="py-4 text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
                                                <h6 class="fw-bold">Belum Ada Data Presensi</h6>
                                                <p class="small mb-0">Data kehadiran akan muncul di sini setelah santri melakukan scan.</p>
                                            </div>
                                        </td>
                                    </tr>`;
                                countEl.textContent = '';
                            }
                        }
                    }, 400);
                }
                showFlashMessage('success', data.message);
            } else {
                if (row) {
                    const deleteBtn = row.querySelector('button[title="Hapus"]');
                    if (deleteBtn) {
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = '<i class="bi bi-trash text-danger"></i>';
                    }
                }
                showFlashMessage('danger', data.message || 'Gagal menghapus data.');
            }
        })
        .catch(err => {
            console.error('Delete error:', err);
            if (row) {
                const deleteBtn = row.querySelector('button[title="Hapus"]');
                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = '<i class="bi bi-trash text-danger"></i>';
                }
            }
            showFlashMessage('danger', 'Terjadi kesalahan saat menghapus data.');
        });
    }

    function showFlashMessage(type, message) {
        const existing = document.getElementById('ajaxFlashMessage');
        if (existing) existing.remove();

        const alertDiv = document.createElement('div');
        alertDiv.id = 'ajaxFlashMessage';
        alertDiv.className = `alert alert-${type} alert-dismissible fade show shadow-sm`;
        alertDiv.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 9999; min-width: 300px; max-width: 450px; animation: toastSlideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);';
        alertDiv.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <i class="bi ${type === 'success' ? 'bi-check-circle-fill text-success' : 'bi-exclamation-triangle-fill text-danger'} fs-5"></i>
                <span class="fw-semibold">${message}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);

        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.style.transition = 'opacity 0.3s ease-out';
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 300);
            }
        }, 4000);
    }
</script>
@endpush
