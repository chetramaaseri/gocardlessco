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
            $contactId = $_GET['delete'];
            Capsule::table('contact_requests')
                ->where('contact_request_id', $contactId)
                ->delete();
            $response = [
                'message' => 'Contact request deleted successfully!'
            ];
            
        } elseif(isset($_GET['update_status'])){
            $contactId = $_GET['update_status'];
            $newStatus = $_POST['status'];
            Capsule::table('contact_requests')
                ->where('contact_request_id', $contactId)
                ->update(['status' => sanitizeText($newStatus)]);
            $response = [
                'message' => 'Status updated successfully!'
            ];
            
        } else {
            // Handle datatable request
            $draw = intval($_POST['draw']);
            $start = intval($_POST['start']);
            $length = intval($_POST['length']);
            $searchValue = sanitizeText($_POST['search']['value']);
            $orderColumnIndex = $_POST['order'][0]['column'];
            $orderDirection = $_POST['order'][0]['dir'];
            
            $columns = [
                'contact_requests.contact_request_id',
                'contact_requests.name',
                'contact_requests.email',
                'contact_requests.phone',
                'contact_requests.subject',
                'contact_requests.status',
                'contact_requests.submitted_at'
            ];
            
            $orderColumnName = $columns[$orderColumnIndex-1];
            
            $query = Capsule::table('contact_requests')
                ->select(
                    'contact_requests.contact_request_id',
                    'contact_requests.name',
                    'contact_requests.email',
                    'contact_requests.phone',
                    'contact_requests.subject',
                    'contact_requests.message',
                    'contact_requests.status',
                    'contact_requests.submitted_at'
                );
        
            // Apply search filter
            if (!empty($searchValue)) {
                $query->where(function($q) use ($searchValue) {
                    $q->where('contact_requests.name', 'like', '%' . $searchValue . '%')
                      ->orWhere('contact_requests.email', 'like', '%' . $searchValue . '%')
                      ->orWhere('contact_requests.phone', 'like', '%' . $searchValue . '%')
                      ->orWhere('contact_requests.subject', 'like', '%' . $searchValue . '%')
                      ->orWhere('contact_requests.status', 'like', '%' . $searchValue . '%');
                });
            }
            
            $totalRecords = Capsule::table('contact_requests')->count();
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
        header('Content-Type: application/json');
        echo json_encode([
            "error" => $th->getMessage()
        ]);
        exit;
    }
}

// Get contact request data for viewing
$contactDetails = null;
if (isset($_GET['view'])) {
    $contactDetails = Capsule::table('contact_requests')
        ->where('contact_request_id', $_GET['view'])
        ->first();
    if (!$contactDetails) {
        $_SESSION['error_message'] = 'Contact request not found';
        header("Location: ?");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once(ADMIN_FILES.'layout/head.php'); ?>
    <style>
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-new { background-color: #d1ecf1; color: #0c5460; }
        .status-in_review { background-color: #fff3cd; color: #856404; }
        .status-responded { background-color: #c3e6cb; color: #155724; }
    </style>
</head>
<body class="sb-nav-fixed">
    <?php require_once ADMIN_FILES.'layout/topnav.php'; ?>
    <div id="layoutSidenav">
        <?php require_once ADMIN_FILES.'layout/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <?php if(isset($_GET['view'])): ?>
                        <h1 class="mt-4">Contact Request Details</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item">Enquiry</li>
                            <li class="breadcrumb-item">Contact Requests</li>
                            <li class="breadcrumb-item active">View Request</li>
                        </ol>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-file-alt me-1"></i>
                                        Request #<?= $contactDetails->contact_request_id ?>
                                    </div>
                                    <div>
                                        <span class="status-badge status-<?= $contactDetails->status ?>">
                                            <?= ucfirst(str_replace('_', ' ', $contactDetails->status)) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Contact Information</h5>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Name</label>
                                            <p class="form-control-static"><?= htmlspecialchars($contactDetails->name) ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Email</label>
                                            <p class="form-control-static"><?= htmlspecialchars($contactDetails->email) ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Phone</label>
                                            <p class="form-control-static"><?= htmlspecialchars($contactDetails->phone) ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Request Details</h5>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Subject</label>
                                            <p class="form-control-static"><?= $contactDetails->subject ? htmlspecialchars($contactDetails->subject) : 'Not specified' ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Submitted At</label>
                                            <p class="form-control-static">
                                                <?= date('M j, Y g:i A', strtotime($contactDetails->submitted_at)) ?>
                                            </p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">IP Address</label>
                                            <p class="form-control-static">
                                                <?= $contactDetails->ip_address ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12 mt-3">
                                        <h5 class="mb-3">Message</h5>
                                        <div class="mb-3">
                                            <div class="border p-3 rounded bg-light">
                                                <?= $contactDetails->message ? nl2br(htmlspecialchars($contactDetails->message)) : 'No message provided' ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="window.history.back()">
                                    <i class="fas fa-arrow-left me-1"></i> Back
                                </button>
                                <button type="button" class="btn btn-sm btn-primary" onclick="updateStatus(<?= $contactDetails->contact_request_id ?>)">
                                    <i class="fas fa-sync-alt me-1"></i> Update Status
                                </button>
                            </div>
                        </div>
                    
                    <?php else: ?>
                    
                        <h1 class="mt-4">Contact Requests</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item">Enquiry</li>
                            <li class="breadcrumb-item">Contact Requests</li>
                        </ol>
                        
                        <?php if(isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                        <?php endif; ?>
                        <?php if(isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
                        <?php endif; ?>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-table me-1"></i>
                                        Contact Requests
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
                                                <th>Phone</th>
                                                <th>Subject</th>
                                                <th>Status</th>
                                                <th>Submitted At</th>
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
                }
            },
            "columns": [
                {
                    "data": "contact_request_id",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return `
                            <button onclick="updateStatus(${data})" class="btn p-0 ps-1"><i class="fa-solid fa-pen-to-square"></i></button>
                            <a href="?view=${data}" class="btn p-0 ps-1"><i class="fa-solid fa-eye"></i></a>
                            <button onclick="deleteItem(${data})" class="btn p-0 ps-1"><i class="fa-solid fa-trash-can"></i></button>
                        `;
                    }
                },
                { "data": "contact_request_id" },
                { "data": "name" },
                { "data": "email" },
                { "data": "phone" },
                { "data": "subject" },
                { 
                    "data": "status",
                    "render": function(data) {
                        const statusMap = {
                            'new': 'New',
                            'in_review': 'In Review',
                            'responded': 'Responded'
                        };
                        return `<span class="status-badge status-${data}">${statusMap[data] || data}</span>`;
                    }
                },
                { 
                    "data": "submitted_at",
                    "render": function(data) {
                        return data ? new Date(data).toLocaleString() : '-';
                    }
                }
            ],
            "order": [[1, 'desc']]
        });
        
        function updateStatus(contactId) {
            Swal.fire({
                title: 'Update Status',
                input: 'select',
                inputOptions: {
                    'new': 'New',
                    'in_review': 'In Review',
                    'responded': 'Responded'
                },
                inputValue: 'new',
                showCancelButton: true,
                confirmButtonText: 'Update',
                preConfirm: (status) => {
                    return $.ajax({
                        url: '?update_status=' + contactId,
                        type: 'POST',
                        data: {
                            status: status,
                            csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                        }
                    }).then(response => {
                        return response;
                    }).catch(error => {
                        Swal.showValidationMessage('Error updating status');
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire(
                        'Updated!',
                        'Status has been updated.',
                        'success'
                    ).then(() => {
                        $('#datatable').DataTable().ajax.reload();
                        if (window.location.href.includes('view=')) {
                            location.reload();
                        }
                    });
                }
            });
        }
        
        function deleteItem(itemId) {
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
                        url: '?delete=' + itemId,
                        type: 'POST',
                        data: {
                            csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'Contact request has been deleted.',
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
    </script>
</body>
</html>