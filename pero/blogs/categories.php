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
            $itemId = $_GET['delete'];
            if($itemId == 1){
                throw new Exception("Can't delete primary category.", 400);
            }
            Capsule::table('dynamic_routes')
                ->where('entity_type', 'blog_category')
                ->where('entity_id', $itemId)
                ->delete();
            Capsule::table('blog_categories')
                ->where('category_id', $itemId)
                ->delete();
            Capsule::table('blog_posts')
                ->where('category_id', $itemId)
                ->update([
                    'category_id' => 0,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            $response = [
                'message' => 'Category Deleted Successfully!!!'
            ];
            
        } elseif (isset($_POST['add_category'])) {
            // Reset the CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $routeExists = Capsule::table('dynamic_routes')
                ->where('route', trim(sanitizeSlug($_POST['slug']), '/'))
                ->exists();
            if ($routeExists) {
                throw new Exception("This URL path is already in use. Please choose a different route.", 400);
            }
            $itemData = [
                'name' => sanitizeText($_POST['name']),
                'description' => sanitizeText($_POST['description']),
                'meta_title' => sanitizeText($_POST['meta_title']),
                'meta_description' => sanitizeText($_POST['meta_description']),
                'meta_keywords' => sanitizeText($_POST['meta_keywords']),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $itemId = Capsule::table('blog_categories')->insertGetId($itemData);
            Capsule::table('dynamic_routes')->insert([
                'route' => trim(sanitizeSlug($_POST['slug']), '/'),
                'entity_type' => 'blog_category',
                'entity_id' => $itemId,
                'is_primary' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $_SESSION['success_message'] = 'Category added successfully!';
            header('Location:?');
            exit;
            
        } elseif (isset($_POST['edit_category'])) {
            // Reset the CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $itemId = $_POST['category_id'];
            $currentItem = Capsule::table('blog_categories')->where('category_id', $itemId)->first();
            $currentRoute = Capsule::table('dynamic_routes')
                    ->where('entity_type', 'blog_category')
                    ->where('entity_id', '=', $itemId)
                    ->first();
            $routeExists = Capsule::table('dynamic_routes')
                    ->where('route', sanitizeSlug($_POST['slug']))
                    ->where('entity_id', '!=', $itemId)
                    ->exists();
            if ($routeExists) {
                throw new Exception("This URL path is already in use. Please choose a different slug.", 400);
            }else if($currentRoute->route != $_POST['slug']){
                Capsule::table('dynamic_routes')->insert([
                    'route' => trim(sanitizeSlug($_POST['slug']), '/'),
                    'entity_type' => 'blog_category',
                    'entity_id' => $itemId,
                    'is_primary' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                Capsule::table('dynamic_routes')
                    ->where('route_id', $currentRoute->route_id)
                    ->update([
                        'entity_type' => 'redirect',
                        'entity_id' => 301,
                        'redirect' => trim(sanitizeSlug($_POST['slug']), '/'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
            }
            $itemData = [
                'name' => sanitizeText($_POST['name']),
                'description' => sanitizeText($_POST['description']),
                'meta_title' => sanitizeText($_POST['meta_title']),
                'meta_description' => sanitizeText($_POST['meta_description']),
                'meta_keywords' => sanitizeText($_POST['meta_keywords']),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            Capsule::table('blog_categories')
                ->where('category_id', $itemId)
                ->update($itemData);
            
            $_SESSION['success_message'] = 'Category updated successfully!';
            header('Location:?');
            exit;
            
        } else {
            $draw = intval($_POST['draw']);
            $start = intval($_POST['start']);
            $length = intval($_POST['length']);
            $searchValue = sanitizeText($_POST['search']['value']);
            $orderColumnIndex = $_POST['order'][0]['column'];
            $orderDirection = $_POST['order'][0]['dir'];
            
            $columns = [
                'blog_categories.category_id',
                'blog_categories.name',
                'blog_categories.description',
                'dynamic_routes.route',
                'blog_categories.updated_at'
            ];
            
            $orderColumnName = $columns[$orderColumnIndex - 1];
            
            $query = Capsule::table('blog_categories')
                ->leftJoin('dynamic_routes', function($join) {
                    $join->on('blog_categories.category_id', '=', 'dynamic_routes.entity_id')
                         ->where('dynamic_routes.entity_type', 'blog_category');
                })
                ->select(
                    'blog_categories.category_id',
                    'blog_categories.name',
                    'blog_categories.description',
                    'blog_categories.meta_title',
                    'blog_categories.meta_description',
                    'blog_categories.meta_keywords',
                    'dynamic_routes.route',
                    'blog_categories.updated_at'
                );
        
            // Apply search filter
            if (!empty($searchValue)) {
                $query->where(function($q) use ($searchValue) {
                    $q->where('blog_categories.name', 'like', '%' . $searchValue . '%')
                      ->orWhere('blog_categories.description', 'like', '%' . $searchValue . '%')
                      ->orWhere('dynamic_routes.route', 'like', '%' . $searchValue . '%')
                      ->orWhere('blog_categories.meta_title', 'like', '%' . $searchValue . '%')
                      ->orWhere('blog_categories.meta_keywords', 'like', '%' . $searchValue . '%');
                });
            }
            
            $totalRecords = Capsule::table('blog_categories')->count();
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

// Get all categories
$categories = Capsule::table('blog_categories')->get();

// Get category data for editing
$editCategory = null;
if (isset($_GET['edit'])) {
    $editCategory = Capsule::table('blog_categories')
        ->leftJoin('dynamic_routes', function($join) {
            $join->on('blog_categories.category_id', '=', 'dynamic_routes.entity_id')
                 ->where('dynamic_routes.entity_type', 'blog_category');
        })
        ->where('blog_categories.category_id', $_GET['edit'])
        ->select(
            'blog_categories.*',
            'dynamic_routes.route'
        )
        ->first();
    
    if (!$editCategory) {
        $_SESSION['error_message'] = 'Category not found';
        header("Refresh:0");
        exit;
    }
    
    // Remove leading slash from route for editing
    if ($editCategory->route) {
        $editCategory->route = ltrim($editCategory->route, '/');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once(ADMIN_FILES.'layout/head.php'); ?>
</head>
<body class="sb-nav-fixed">
    <?php require_once ADMIN_FILES.'layout/topnav.php'; ?>
    <div id="layoutSidenav">
        <?php require_once ADMIN_FILES.'layout/sidenav.php'; ?>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <?php if(isset($_GET['add']) || isset($_GET['edit'])): ?>
                        <h1 class="mt-4"><?= isset($_GET['edit']) ? 'Edit' : 'Add' ?> Category</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item active">Blog / <?= isset($_GET['edit']) ? 'Edit' : 'Add' ?> Category</li>
                        </ol>
                        
                        <?php if(isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
                        <?php endif; ?>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-edit me-1"></i>
                                        <?= isset($_GET['edit']) ? 'Edit' : 'Create New' ?> Category
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label for="name" class="form-label">Category Name *</label>
                                                <input type="text" class="form-control" id="name" name="name" 
                                                    value="<?= htmlspecialchars($editCategory->name ?? '') ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="route" class="form-label">URL Route *</label>
                                                <input type="text" class="form-control" id="route" name="slug" 
                                                    value="<?= htmlspecialchars($editCategory->route ?? '') ?>" required>
                                                <small class="text-muted">This will be used in the category URL (e.g., "category/category-name")</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="description" class="form-label">Description</label>
                                                <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($editCategory->description ?? '') ?></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="card mb-4">
                                                <div class="card-header">
                                                    SEO Settings
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-3">
                                                        <label for="meta_title" class="form-label">Meta Title</label>
                                                        <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                                            value="<?= htmlspecialchars($editCategory->meta_title ?? '') ?>">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="meta_description" class="form-label">Meta Description</label>
                                                        <textarea class="form-control" id="meta_description" name="meta_description" rows="2"><?= htmlspecialchars($editCategory->meta_description ?? '') ?></textarea>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                                        <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" 
                                                            value="<?= htmlspecialchars($editCategory->meta_keywords ?? '') ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if(isset($_GET['edit'])): ?>
                                        <input type="hidden" name="category_id" value="<?= $editCategory->category_id ?>">
                                    <?php endif; ?>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="<?= isset($_GET['edit']) ? 'edit_category' : 'add_category' ?>" 
                                            class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> <?= isset($_GET['edit']) ? 'Update' : 'Save' ?> Category
                                        </button>
                                    </div>
                                    
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                </form>
                            </div>
                        </div>
                    
                    <?php else: ?>
                    
                        <h1 class="mt-4">Categories</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item active">Blog / Categories</li>
                        </ol>
                        
                        <?php if(isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                        <?php endif; ?>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-table me-1"></i>
                                        Blog Categories
                                    </div>
                                    <div>
                                        <a href="?add=1" class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-plus"></i> Add Category
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
                                                <th>Description</th>
                                                <th>Route</th>
                                                <th>Updated At</th>
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
                    "data": "category_id",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return `
                            <a href="?edit=${data}" class="btn p-0 ps-1"><i class="fa-solid fa-pencil"></i></a>
                            <a href="/${row.route}" target="_blank" class="btn p-0 ps-1"><i class="fa-solid fa-eye"></i></a>
                            <button onclick="deleteCategory(${data})" class="btn p-0 ps-1"><i class="fa-solid fa-trash-can"></i></button>
                        `;
                    }
                },
                { "data": "category_id" },
                { 
                    "data": "name",
                    "render": function(data, type, row) {
                        return `<a href="?edit=${row.category_id}">${data}</a>`;
                    }
                },
                { 
                    "data": "description",
                    "render": function(data) {
                        return data || '-';
                    }
                },
                { 
                    "data": "route",
                    "render": function(data) {
                        return data ? `<a href="<?=BASE_URL?>${data}" target="_blank">${data}</a>` : '-';
                    }
                },
                { 
                    "data": "updated_at",
                    "render": function(data) {
                        return data ? new Date(data).toLocaleString() : '-';
                    }
                }
            ],
            "order": [[1, 'desc']]
        });
        
        function deleteCategory(categoryId) {
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
                        url: '?delete=' + categoryId,
                        type: 'POST',
                        data: {
                            csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'Your category has been deleted.',
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
        
        // Auto-generate route from name
        $('#name').on('blur', function() {
            if (!$('#route').val()) {
                const name = $(this).val();
                const route = 'category/' + name.toLowerCase()
                    .replace(/[^\w\s-]/g, '') // Remove non-word characters
                    .replace(/[\s_-]+/g, '-') // Replace spaces and underscores with hyphens
                    .replace(/^-+|-+$/g, ''); // Trim hyphens from start and end
                $('#route').val(route);
            }
        });
    </script>
</body>
</html>