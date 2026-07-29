<?php
$isAdmin = checkRole('admin');
$success = null;
$error = null;
$allowedRoles = [
    'admin' => 'Admin',
    'viewer' => 'Viewer',
];

if (!$isAdmin) {
    ?>
    <div class="content-section">
        <div class="alert alert-danger mb-0">
            <i class="fas fa-lock"></i> Akses ditolak. Hanya admin yang bisa mengelola user.
        </div>
    </div>
    <?php
    return;
}

if (!function_exists('userPageValidateRole')) {
    function userPageValidateRole($role, $allowedRoles) {
        $role = sanitize($role);
        if (!isset($allowedRoles[$role])) {
            throw new RuntimeException('Role user tidak valid.');
        }

        return $role;
    }
}

if (!function_exists('userPageValidateUsername')) {
    function userPageValidateUsername($username) {
        $username = sanitize($username);
        if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            throw new RuntimeException('Username harus 3-50 karakter dan hanya boleh berisi huruf, angka, titik, underscore, atau dash.');
        }

        return $username;
    }
}

if (!function_exists('userPageValidateEmail')) {
    function userPageValidateEmail($email) {
        $email = sanitize($email);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Format email tidak valid.');
        }

        return $email;
    }
}

if (!function_exists('userPageAdminCount')) {
    function userPageAdminCount() {
        $row = fetchOne("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
        return (int) ($row['total'] ?? 0);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_user_form'] ?? '') === 'users') {
    $action = $_POST['_user_action'] ?? 'create';
    $recordId = (int) ($_POST['id'] ?? 0);

    try {
        if ($action === 'delete') {
            if ($recordId <= 0) {
                throw new RuntimeException('ID user tidak valid.');
            }

            if ($recordId === (int) ($_SESSION['user_id'] ?? 0)) {
                throw new RuntimeException('Akun yang sedang login tidak bisa dihapus.');
            }

            $targetUser = fetchOne('SELECT * FROM users WHERE id = ?', [$recordId]);
            if (!$targetUser) {
                throw new RuntimeException('User tidak ditemukan.');
            }

            if (($targetUser['role'] ?? '') === 'admin' && userPageAdminCount() <= 1) {
                throw new RuntimeException('Minimal harus ada satu admin aktif.');
            }

            $reportCount = fetchOne('SELECT COUNT(*) AS total FROM generated_reports WHERE generated_by = ?', [$recordId]);
            if ((int) ($reportCount['total'] ?? 0) > 0) {
                throw new RuntimeException('User ini masih tercatat sebagai pembuat laporan. Ubah role/statusnya, jangan hapus datanya.');
            }

            query('DELETE FROM users WHERE id = ?', [$recordId]);
            $success = 'User berhasil dihapus.';
        } else {
            $role = userPageValidateRole($_POST['role'] ?? '', $allowedRoles);
            $fullName = sanitize($_POST['full_name'] ?? '');
            $email = userPageValidateEmail($_POST['email'] ?? '');
            $notes = sanitize($_POST['notes'] ?? '');
            $password = (string) ($_POST['password'] ?? '');

            if ($action === 'update') {
                if ($recordId <= 0) {
                    throw new RuntimeException('ID user tidak valid.');
                }

                $targetUser = fetchOne('SELECT * FROM users WHERE id = ?', [$recordId]);
                if (!$targetUser) {
                    throw new RuntimeException('User tidak ditemukan.');
                }

                if ((int) $targetUser['id'] === (int) ($_SESSION['user_id'] ?? 0) && $role !== 'admin') {
                    throw new RuntimeException('Akun yang sedang login tidak bisa mengubah rolenya sendiri menjadi viewer.');
                }

                if (($targetUser['role'] ?? '') === 'admin' && $role !== 'admin' && userPageAdminCount() <= 1) {
                    throw new RuntimeException('Minimal harus ada satu admin aktif.');
                }

                if ($password !== '') {
                    if (strlen($password) < 6) {
                        throw new RuntimeException('Password minimal 6 karakter.');
                    }

                    query(
                        'UPDATE users SET role = ?, full_name = ?, email = ?, notes = ?, password = ? WHERE id = ?',
                        [$role, $fullName, $email, $notes, password_hash($password, PASSWORD_DEFAULT), $recordId]
                    );
                } else {
                    query(
                        'UPDATE users SET role = ?, full_name = ?, email = ?, notes = ? WHERE id = ?',
                        [$role, $fullName, $email, $notes, $recordId]
                    );
                }

                if ($recordId === (int) ($_SESSION['user_id'] ?? 0)) {
                    $_SESSION['user_role'] = $role;
                    $_SESSION['full_name'] = $fullName !== '' ? $fullName : $targetUser['username'];
                }

                $success = 'User berhasil diperbarui.';
            } else {
                $username = userPageValidateUsername($_POST['username'] ?? '');

                if (strlen($password) < 6) {
                    throw new RuntimeException('Password minimal 6 karakter.');
                }

                $existingUser = fetchOne('SELECT id FROM users WHERE username = ?', [$username]);
                if ($existingUser) {
                    throw new RuntimeException('Username sudah dipakai.');
                }

                query(
                    'INSERT INTO users (username, password, role, full_name, email, notes) VALUES (?, ?, ?, ?, ?, ?)',
                    [$username, password_hash($password, PASSWORD_DEFAULT), $role, $fullName, $email, $notes]
                );

                $success = 'User baru berhasil ditambahkan.';
            }
        }
    } catch (PDOException $e) {
        $error = 'Gagal memproses user: ' . $e->getMessage();
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

$users = fetchAll("SELECT id, username, role, full_name, email, notes, created_at, updated_at FROM users ORDER BY FIELD(role, 'admin', 'viewer'), username ASC");
$userMap = [];
foreach ($users as $user) {
    $userMap[(string) $user['id']] = $user;
}

$adminCount = 0;
$viewerCount = 0;
foreach ($users as $user) {
    if (($user['role'] ?? '') === 'admin') {
        $adminCount++;
    } elseif (($user['role'] ?? '') === 'viewer') {
        $viewerCount++;
    }
}
?>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-users-cog"></i> User Management</h4>
            <p class="text-muted mb-0">Kelola akun admin dan viewer untuk akses aplikasi.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-danger"><?php echo (int) $adminCount; ?> admin</span>
            <span class="badge bg-secondary"><?php echo (int) $viewerCount; ?> viewer</span>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="fas fa-user-plus"></i> Tambah User</h6>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="_user_form" value="users">
                <input type="hidden" name="_user_action" value="create">

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" maxlength="50" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" minlength="6" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <?php foreach ($allowedRoles as $role => $label): ?>
                                <option value="<?php echo htmlspecialchars($role); ?>"><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" maxlength="100">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" maxlength="100">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan User
                </button>
            </form>
        </div>
    </div>
</div>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h4 class="mb-0"><i class="fas fa-table"></i> Daftar User</h4>
        <span class="badge bg-secondary"><?php echo count($users); ?> user</span>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover data-table user-data-table" width="100%">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Created</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php
                    $role = $user['role'] ?? 'viewer';
                    $roleClass = $role === 'admin' ? 'danger' : ($role === 'viewer' ? 'secondary' : 'info');
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><span class="badge bg-<?php echo $roleClass; ?>"><?php echo strtoupper(htmlspecialchars($role)); ?></span></td>
                        <td><?php echo htmlspecialchars($user['full_name'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($user['email'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars(formatDate($user['created_at'])); ?></td>
                        <td class="text-end text-nowrap table-action-buttons">
                            <button type="button" class="btn btn-outline-warning btn-sm user-edit-btn" data-record-id="<?php echo (int) $user['id']; ?>" title="Edit user">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ((int) $user['id'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                                <button type="button" class="btn btn-outline-danger btn-sm user-delete-btn" data-record-id="<?php echo (int) $user['id']; ?>" title="Hapus user">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="userEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" id="userEditForm">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_user_form" value="users">
                    <input type="hidden" name="_user_action" value="update">
                    <input type="hidden" name="id" id="userEditId">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="userEditUsername" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="userEditRole" required>
                                <?php foreach ($allowedRoles as $role => $label): ?>
                                    <option value="<?php echo htmlspecialchars($role); ?>"><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control user-edit-field" name="full_name" data-field="full_name" maxlength="100">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control user-edit-field" name="email" data-field="email" maxlength="100">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" class="form-control" name="password" minlength="6" placeholder="Kosongkan jika tidak diganti">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control user-edit-field" name="notes" data-field="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="userDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash"></i> Hapus User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_user_form" value="users">
                    <input type="hidden" name="_user_action" value="delete">
                    <input type="hidden" name="id" id="userDeleteId">
                    <p class="mb-0">Yakin ingin menghapus user <strong id="userDeleteLabel"></strong>? User yang punya riwayat laporan tidak bisa dihapus.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function() {
    var userRows = <?php echo json_encode($userMap, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    function getUserRow(id) {
        return userRows[String(id)] || null;
    }

    $(document).on('click', '.user-edit-btn', function() {
        var row = getUserRow($(this).data('record-id'));
        if (!row) return;

        $('#userEditId').val(row.id);
        $('#userEditUsername').val(row.username || '');
        $('#userEditRole').val(row.role === 'admin' ? 'admin' : 'viewer');
        $('#userEditForm [name="password"]').val('');
        $('#userEditForm .user-edit-field').each(function() {
            var field = $(this).data('field');
            $(this).val(row[field] === null || row[field] === undefined ? '' : row[field]);
        });

        bootstrap.Modal.getOrCreateInstance(document.getElementById('userEditModal')).show();
    });

    $(document).on('click', '.user-delete-btn', function() {
        var row = getUserRow($(this).data('record-id'));
        if (!row) return;

        $('#userDeleteId').val(row.id);
        $('#userDeleteLabel').text(row.username || ('#' + row.id));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('userDeleteModal')).show();
    });

    if ($.fn.DataTable) {
        $('.user-data-table').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
            },
            order: [[1, 'asc'], [0, 'asc']],
            columnDefs: [
                { targets: -1, orderable: false, searchable: false }
            ]
        });
    }
});
</script>
