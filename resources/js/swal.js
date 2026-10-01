/**
 * Helper SweetAlert2 terpusat untuk seluruh SPA.
 *
 * Semua dialog (konfirmasi, sukses, error) memakai tema gelap senada
 * dengan aplikasi. Impor dari sini — bukan langsung dari 'sweetalert2' —
 * supaya styling konsisten dan mudah diubah dari satu tempat.
 */
import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

const base = {
    buttonsStyling: false,
    reverseButtons: true,
    customClass: {
        popup: 'swal-popup',
        title: 'swal-title',
        htmlContainer: 'swal-text',
        confirmButton: 'swal-btn swal-btn-confirm',
        cancelButton: 'swal-btn swal-btn-cancel',
        denyButton: 'swal-btn swal-btn-deny',
        icon: 'swal-icon',
    },
}

/**
 * Dialog konfirmasi (Ya / Batal). Return `true` bila user menyetujui.
 *
 * Contoh:
 *   if (await confirmDelete('Hapus pengguna', 'Tindakan ini permanen.')) { ... }
 */
export function fireConfirm({ title, text, confirmText = 'Ya, lanjutkan', icon = 'warning' }) {
    return Swal.fire({
        ...base,
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'Batal',
        focusCancel: true,
    }).then((result) => result.isConfirmed)
}

/** Notifikasi sukses — satu tombol "OK". */
export function fireSuccess(title, text = '') {
    return Swal.fire({ ...base, title, text, icon: 'success', confirmButtonText: 'OK' })
}

/** Notifikasi error — satu tombol "Coba Lagi". */
export function fireError(title, text = '') {
    return Swal.fire({ ...base, title, text, icon: 'error', confirmButtonText: 'Coba Lagi' })
}

/** Notifikasi info/peringatan netral. */
export function fireInfo(title, text = '') {
    return Swal.fire({ ...base, title, text, icon: 'info', confirmButtonText: 'Mengerti' })
}

/** Toast kecil di pojok kanan atas — untuk aksi ringan tanpa dialog penuh. */
export function fireToast(icon, title) {
    return Swal.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
        icon,
        title,
        customClass: { popup: 'swal-toast' },
    })
}

export default Swal
