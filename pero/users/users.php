<?php
use Illuminate\Database\Capsule\Manager as Capsule;
require ADMIN_FILES.'auth/authMiddleware.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['csrf_token'])) {
    try {
        if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Request Forbidden!, Try Again", 403);
        }
        $response = [];
        
        if(isset($_GET['delete'])){
            $userId = $_GET['delete'];
            Capsule::table('users')
                ->where('user_id', $userId)
                ->update(['deleted_at' => date('Y-m-d H:i:s')]);
                
            $response = [
                'message' => 'User Deleted Successfully!!!'
            ];
            
        } elseif (isset($_POST['add_user'])) {
            // Reset the CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            $emailExists = Capsule::table('users')
                ->where('email', $_POST['email'])
                ->whereNull('deleted_at')
                ->exists();
                
            if ($emailExists) {
                throw new Exception("This email is already registered.", 400);
            }
            
            $userData = [
                'name' => sanitizeText($_POST['name']),
                'email' => sanitizeText($_POST['email']),
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'max_chats' => (int)$_POST['max_chats'],
                'chat_preference' => (int)$_POST['chat_preference'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            Capsule::table('users')->insert($userData);
            
            $_SESSION['success_message'] = 'User added successfully!';
            header('Location:?');
            exit;
            
        } elseif (isset($_POST['edit_user'])) {
            // Reset the CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $userId = $_POST['user_id'];
            
            $emailExists = Capsule::table('users')
                ->where('email', $_POST['email'])
                ->where('user_id', '!=', $userId)
                ->whereNull('deleted_at')
                ->exists();
                
            if ($emailExists) {
                throw new Exception("This email is already registered by another user.", 400);
            }
            
            $userData = [
                'name' => sanitizeText($_POST['name']),
                'email' => sanitizeText($_POST['email']),
                'max_chats' => (int)$_POST['max_chats'],
                'chat_preference' => (int)$_POST['chat_preference'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (!empty($_POST['password'])) {
                $userData['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            
            Capsule::table('users')
                ->where('user_id', $userId)
                ->update($userData);
            
            $_SESSION['success_message'] = 'User updated successfully!';
            header('Location:?');
            exit;
            
        } else {
            // Handle datatable request
            $draw = intval($_POST['draw']);
            $start = intval($_POST['start']);
            $length = intval($_POST['length']);
            $searchValue = sanitizeText($_POST['search']['value']);
            $orderColumnIndex = $_POST['order'][0]['column'];
            $orderDirection = $_POST['order'][0]['dir'];
            
            $columns = [
                'users.user_id',
                'users.name',
                'users.email',
                'users.max_chats',
                'users.chat_preference',
                'users.created_at'
            ];
            
            $orderColumnName = $columns[$orderColumnIndex];
            
            $query = Capsule::table('users')
                ->whereNull('deleted_at')
                ->select(
                    'user_id',
                    'name',
                    'email',
                    'max_chats',
                    'chat_preference',
                    'created_at'
                );
            
            // Apply search filter
            if (!empty($searchValue)) {
                $query->where(function($q) use ($searchValue) {
                    $q->where('name', 'like', '%' . $searchValue . '%')
                      ->orWhere('email', 'like', '%' . $searchValue . '%');
                });
            }
            
            $totalRecords = Capsule::table('users')->whereNull('deleted_at')->count();
            $filteredRecords = $query->count();
            
            $query->orderBy($orderColumnName, $orderDirection)
                  ->offset($start)
                  ->limit($length);
            
            $data = $query->get();
            
            $response = [
                "draw" => $draw,
                "recordsTotal" => $totalRecords,
                "recordsFiltered" => $filteredRecords,
                "data" => $data
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
        
    } catch (\Throwable $th) {
        $_SESSION['error_message'] = $th->getMessage();
        header("Refresh:0");
        exit;
    }
}

// Get user data for editing
$editUser = null;
if (isset($_GET['edit'])) {
    $editUser = Capsule::table('users')
        ->where('user_id', $_GET['edit'])
        ->whereNull('deleted_at')
        ->first();
        
    if (!$editUser) {
        $_SESSION['error_message'] = 'User not found';
        header("Refresh:0");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once(ADMIN_FILES.'layout/head.php'); ?>
    <style>
        .password-field {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php require_once ADMIN_FILES.'layout/topnav.php'; ?>
    <div id="layoutSidenav">
        <?php require_once ADMIN_FILES.'layout/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <?php if(isset($_GET['add']) || isset($_GET['edit'])): ?>
                        <h1 class="mt-4"><?= isset($_GET['edit']) ? 'Edit' : 'Add' ?> User</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item active">Users / <?= isset($_GET['edit']) ? 'Edit' : 'Add' ?> User</li>
                        </ol>
                        
                        <?php if(isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
                        <?php endif; ?>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-user me-1"></i>
                                        <?= isset($_GET['edit']) ? 'Edit' : 'Create New' ?> User
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">Full Name *</label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                    value="<?= $editUser->name ?? '' ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email *</label>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                    value="<?= $editUser->email ?? '' ?>" required>
                                            </div>
                                            
                                            <div class="mb-3 password-field">
                                                <label for="password" class="form-label"><?= isset($_GET['edit']) ? 'New ' : '' ?>Password <?= isset($_GET['edit']) ? '(leave blank to keep current)' : '*' ?></label>
                                                <input type="password" class="form-control" id="password" name="password" 
                                                    <?= !isset($_GET['edit']) ? 'required' : '' ?>>
                                                <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="max_chats" class="form-label">Max Chats *</label>
                                                <input type="number" class="form-control" id="max_chats" name="max_chats" 
                                                    value="<?= $editUser->max_chats ?? 1 ?>" min="1" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="chat_preference" class="form-label">Chat Preference *</label>
                                                <input type="number" class="form-control" id="chat_preference" name="chat_preference" 
                                                    value="<?= $editUser->chat_preference ?? 1 ?>" required>
                                            </div>
                                            
                                            <?php if(isset($_GET['edit'])): ?>
                                                <div class="mb-3">
                                                    <label class="form-label">Created At</label>
                                                    <input type="text" class="form-control" 
                                                        value="<?= date('Y-m-d H:i:s', strtotime($editUser->created_at)) ?>" readonly>
                                                </div>
                                                
                                                <input type="hidden" name="user_id" value="<?= $editUser->user_id ?>">
                                            <?php endif; ?>
                                            
                                            <div class="d-grid gap-2 mt-4">
                                                <button type="submit" name="<?= isset($_GET['edit']) ? 'edit_user' : 'add_user' ?>" 
                                                    class="btn btn-primary">
                                                    <i class="fas fa-save me-1"></i> 
                                                    <?= isset($_GET['edit']) ? 'Update' : 'Save' ?> User
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                </form>
                            </div>
                        </div>
                    
                    <?php else: ?>
                    
                        <h1 class="mt-4">Users</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item active">Users Management</li>
                        </ol>
                        
                        <?php if(isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                        <?php endif; ?>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-users me-1"></i>
                                        Users List
                                    </div>
                                    <div>
                                        <a href="?add=1" class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-plus"></i> Add User
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="datatable" class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Actions</th>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Max Chats</th>
                                                <th>Preference</th>
                                                <th>Created At</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
            <?php require_once ADMIN_FILES.'layout/footer.php' ?>
        </div>
    </div>
    <?php require_once(ADMIN_FILES.'layout/scripts.php'); ?>
    <script>
        $('#datatable').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "?",
                "type": "POST",
                "data": function(d) {
                    d.csrf_token = '<?= $_SESSION['csrf_token'] ?>';
                },
                "error": function(xhr, error, thrown) {
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        Swal.fire({
                            title: 'Error!',
                            text: "Error: " + xhr.responseJSON.error,
                            icon: 'error',
                            confirmButtonText: 'Okay',
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: "An error occurred while fetching data. Please try again",
                            icon: 'error',
                            confirmButtonText: 'Okay',
                        });
                    }
                }
            },
            "columns": [
                {
                    "data": "user_id",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return `
                            <a href="?edit=${data}" class="btn p-0 ps-1"><i class="fa-solid fa-pencil"></i></a>
                            <button onclick="deleteUser(${data})" class="btn p-0 ps-1"><i class="fa-solid fa-trash-can"></i></button>
                        `;
                    }
                },
                { "data": "user_id" },
                { 
                    "data": "name",
                    "render": function(data, type, row) {
                        return `<a href="?edit=${row.user_id}">${data}</a>`;
                    }
                },
                { "data": "email" },
                { "data": "max_chats" },
                { 
                    "data": "chat_preference",
                    "render": function(data) {
                        return data || 'Unknown';
                    }
                },
                { 
                    "data": "created_at",
                    "render": function(data) {
                        return new Date(data).toLocaleString();
                    }
                }
            ],
            "order": [[1, 'desc']]
        });
        
        function deleteUser(userId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '?delete=' + userId,
                        type: 'POST',
                        data: {
                            csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'The user has been deleted.',
                                'success'
                            ).then(() => {
                                $('#datatable').DataTable().ajax.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                xhr.responseJSON ? xhr.responseJSON.message : 'Something went wrong',
                                'error'
                            );
                        }
                    });
                }
            });
        }
        
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>