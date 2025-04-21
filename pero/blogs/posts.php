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
            $postId = $_GET['delete'];
            Capsule::table('dynamic_routes')
                ->where('entity_type', 'post')
                ->where('entity_id', $postId)
                ->delete();
            Capsule::table('blog_posts')
                ->where('post_id', $postId)
                ->delete();
            $response = [
                'message' => 'Post Deleted Successfully!!!'
            ];
            
        } elseif (isset($_POST['add_post'])) {
            // Reset the CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $routeExists = Capsule::table('dynamic_routes')
                ->where('route', $_POST['slug'])
                ->exists();
            if ($routeExists) {
                throw new Exception("This URL path is already in use. Please choose a different slug.", 400);
            }
            $postData = [
                'title' => sanitizeText($_POST['title']),
                'content' => $_POST['content'],
                'excerpt' => sanitizeText($_POST['excerpt']),
                'status' => sanitizeText($_POST['status']),
                'category_id' => sanitizeText($_POST['category']),
                'published_at' => $_POST['published_at'] ?: null,
                'author_id' => $_SESSION['user_id'],
                'meta_title' => sanitizeText($_POST['meta_title']),
                'meta_description' => sanitizeText($_POST['meta_description']),
                'meta_keywords' => sanitizeText($_POST['meta_keywords']),
                'allow_comments' => isset($_POST['allow_comments']) ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if (!empty($_FILES['featured_image']['name'])) {
                $uploadDir = '/var/www/html/public/uploads/';
                $fileName = time() . '_' . basename($_FILES['featured_image']['name']);
                $targetPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $targetPath)) {
                    $postData['featured_image'] = '/public/uploads/' . $fileName;
                }
            }
            $postId = Capsule::table('blog_posts')->insertGetId($postData);
            Capsule::table('dynamic_routes')->insert([
                'route' => sanitizeSlug($_POST['slug']),
                'entity_type' => 'post',
                'entity_id' => $postId,
                'is_primary' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $_SESSION['success_message'] = 'Post added successfully!';
            header('Location:?');
            exit;
            
        } elseif (isset($_POST['edit_post'])) {
            // Reset the CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $postId = $_POST['post_id'];
            $currentPost = Capsule::table('blog_posts')->where('post_id', $postId)->first();
            $currentRoute = Capsule::table('dynamic_routes')
                    ->where('entity_type', 'post')
                    ->where('entity_id', '=', $postId)
                    ->first();
            $routeExists = Capsule::table('dynamic_routes')
                    ->where('route', sanitizeSlug($_POST['slug']))
                    ->where('entity_id', '!=', $postId)
                    ->exists();
            if ($routeExists) {
                throw new Exception("This URL path is already in use. Please choose a different slug.", 400);
            }else if($currentRoute->route != $_POST['slug']){
                Capsule::table('dynamic_routes')->insert([
                    'route' => sanitizeSlug($_POST['slug']),
                    'entity_type' => 'post',
                    'entity_id' => $postId,
                    'is_primary' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                Capsule::table('dynamic_routes')
                    ->where('route_id', $currentRoute->route_id)
                    ->update([
                        'entity_type' => 'redirect',
                        'redirect' => sanitizeSlug($_POST['slug']),
                        'entity_id' => 301,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                
            }
            $postData = [
                'title' => sanitizeText($_POST['title']),
                'content' => $_POST['content'],
                'excerpt' => sanitizeText($_POST['excerpt']),
                'status' => sanitizeText($_POST['status']),
                'category_id' => sanitizeText($_POST['category']),
                'published_at' => $_POST['published_at'] ?: null,
                'meta_title' => sanitizeText($_POST['meta_title']),
                'meta_description' => sanitizeText($_POST['meta_description']),
                'meta_keywords' => sanitizeText($_POST['meta_keywords']),
                'allow_comments' => isset($_POST['allow_comments']) ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if(isset($_POST['remove_featured_image'])){
                $postData['featured_image'] = '';
            }else if (!empty($_FILES['featured_image']['name'])) {
                $uploadDir = 'public/uploads/';
                $fileName = time() . '_' . basename($_FILES['featured_image']['name']);
                $targetPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $targetPath)) {
                    if ($currentPost->featured_image) {
                        $oldImagePath = $currentPost->featured_image;
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    $postData['featured_image'] = '/public/uploads/' . $fileName;
                }
            }
            Capsule::table('blog_posts')
                ->where('post_id', $postId)
                ->update($postData);
            
            $_SESSION['success_message'] = 'Post updated successfully!';
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
                'blog_posts.post_id',
                'blog_posts.title',
                'blog_categories.name',
                'blog_posts.status',
                'blog_posts.published_at'
            ];
            
            $orderColumnName = $columns[$orderColumnIndex - 1];
            
            $query = Capsule::table('blog_posts')
                ->leftJoin('blog_categories', 'blog_posts.category_id', '=', 'blog_categories.category_id')
                ->leftJoin('dynamic_routes', function($join) {
                    $join->on('dynamic_routes.entity_id', '=', 'blog_posts.post_id')
                         ->where('dynamic_routes.entity_type', 'post');
                })
                ->select(
                    'blog_posts.post_id as post_id', 
                    'blog_posts.title', 
                    'dynamic_routes.route as slug', 
                    'blog_posts.status', 
                    'blog_posts.published_at', 
                    'blog_posts.created_at',
                    'blog_categories.name as category_name'
                );
            
            // Apply search filter
            if (!empty($searchValue)) {
                $query->where(function($q) use ($searchValue) {
                    $q->where('blog_posts.title', 'like', '%' . $searchValue . '%')
                      ->orWhere('blog_posts.content', 'like', '%' . $searchValue . '%')
                      ->orWhere('blog_categories.name', 'like', '%' . $searchValue . '%')
                      ->orWhere('blog_posts.status', 'like', '%' . $searchValue . '%');
                });
            }
            
            $totalRecords = Capsule::table('blog_posts')->count();
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
// Get post data for editing
$editPost = null;
if (isset($_GET['edit'])) {
    $editPost = Capsule::table('blog_posts')
        ->leftJoin('dynamic_routes', function($join) {
            $join->on('dynamic_routes.entity_id', '=', 'blog_posts.post_id')
                    ->where('dynamic_routes.entity_type', 'post');
        })
        ->where('blog_posts.post_id', $_GET['edit'])
        ->select(
            'blog_posts.*', 
            'dynamic_routes.route as slug', 
        )->first();
    if (!$editPost) {
        $_SESSION['error_message'] = 'Post not found';
        header("Refresh:0");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once(ADMIN_FILES.'layout/head.php'); ?>
    <link rel="stylesheet" href="<?=ADMIN_ASSET?>assets/editor/core.css" />
    <link rel="stylesheet" href="<?=ADMIN_ASSET?>assets/editor/isolated-block-editor.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .editor>div, #editor {
            height:110vh;
            overflow:auto;
        }
        .editor.full-screen>div {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 10;
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
                        <h1 class="mt-4"><?= isset($_GET['edit']) ? 'Edit' : 'Add' ?> Post</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item active">Blog / <?= isset($_GET['edit']) ? 'Edit' : 'Add' ?> Post</li>
                        </ol>
                        
                        <?php if(isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
                        <?php endif; ?>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-edit me-1"></i>
                                        <?= isset($_GET['edit']) ? 'Edit' : 'Create New' ?> Post
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label for="title" class="form-label">Post Title *</label>
                                                <input type="text" class="form-control" id="title" name="title" value="<?= $editPost->title ?? '' ?>" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="slug" class="form-label">URL Slug *</label>
                                                <input type="text" class="form-control" id="slug" name="slug" value="<?= $editPost->slug ?? '' ?>" required>
                                                <small class="text-muted">This will be used in the post URL</small>
                                            </div>

                                            <div class="mb-3">
                                                <label for="editor" class="form-label">Content * <!-- <button id="toggleEditorFullScreen" type="button" data-toggle-class="fa-solid fa-down-left-and-up-right-to-center" class="btn btn-sm"><i class="fa-solid fa-up-right-and-down-left-from-center"></i></button> --></label>
                                                <textarea id="editor" class="form-control" name="content"><?= $editPost->content ?? '' ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="excerpt" class="form-label">Excerpt</label>
                                                <textarea class="form-control" id="excerpt" name="excerpt" rows="4"><?= $editPost->excerpt ?? '' ?></textarea>
                                                <small class="text-muted">A short summary of your post</small>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="card mb-4">
                                                <div class="card-header">
                                                    Publish
                                                </div>
                                                <div class="card-body">
                                                    <?php if(isset($_GET['edit'])): ?>
                                                        <input type="hidden" name="post_id" value="<?= $editPost->post_id ?>">
                                                    <?php endif; ?>
                                                    
                                                    <div class="mb-3">
                                                        <label for="status" class="form-label">Status *</label>
                                                        <select class="form-select" id="status" name="status" required>
                                                            <option value="draft" <?= ($editPost->status ?? '') == 'draft' ? 'selected' : '' ?>>Draft</option>
                                                            <option value="published" <?= ($editPost->status ?? '') == 'published' ? 'selected' : '' ?>>Published</option>
                                                            <option value="archived" <?= ($editPost->status ?? '') == 'archived' ? 'selected' : '' ?>>Archived</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="category" class="form-label">Category * <a target="_blank" href="<?=ADMIN_URL?>categories">manage</a></label>
                                                        <select class="form-select" id="category" name="category" required>
                                                            <?php foreach ($categories as $category): ?>
                                                                <option value="<?=$category->category_id?>" <?= ($editPost->category_id ?? '') == $category->category_id ? 'selected' : '' ?>><?= htmlspecialchars($category->name) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="published_at" class="form-label">Publish Date/Time</label>
                                                        <input type="datetime-local" class="form-control" id="published_at" name="published_at" value="<?= isset($editPost->published_at) ? date('Y-m-d\TH:i', strtotime($editPost->published_at)) : date('Y-m-d\TH:i') ?>">
                                                    </div>
                                                    
                                                    <div class="d-grid gap-2">
                                                        <button type="submit" name="<?= isset($_GET['edit']) ? 'edit_post' : 'add_post' ?>" class="btn btn-primary">
                                                            <i class="fas fa-save me-1"></i> <?= isset($_GET['edit']) ? 'Update' : 'Save' ?> Post
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="card mb-4">
                                                <div class="card-header">
                                                    Featured Image
                                                </div>
                                                <div class="card-body">
                                                    <?php if(isset($_GET['edit']) && $editPost->featured_image): ?>
                                                        <div class="mb-3">
                                                            <img src="<?= BASE_URL . ltrim($editPost->featured_image, '/') ?>" class="img-fluid mb-2" style="max-height: 150px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" id="remove_featured_image" name="remove_featured_image">
                                                                <label class="form-check-label" for="remove_featured_image">
                                                                    Remove featured image
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="mb-3">
                                                        <label for="featured_image" class="form-label">Upload Image</label>
                                                        <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="card mb-4">
                                                <div class="card-header">
                                                    SEO Settings
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-3">
                                                        <label for="meta_title" class="form-label">Meta Title</label>
                                                        <input data-max-length="255" type="text" class="form-control" id="meta_title" name="meta_title" value="<?= $editPost->meta_title ?? '' ?>">
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="meta_description" class="form-label">Meta Description</label>
                                                        <textarea data-max-length="500" class="form-control" id="meta_description" name="meta_description" rows="2"><?= $editPost->meta_description ?? '' ?></textarea>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="meta_keywords" class="form-label">Meta Keywords</label>
                                                        <input data-max-length="255" type="text" class="form-control" id="meta_keywords" name="meta_keywords" value="<?= $editPost->meta_keywords ?? '' ?>">
                                                    </div>
                                                    
                                                    <div class="form-check form-switch mb-3 d-none">
                                                        <input class="form-check-input" type="checkbox" id="allow_comments" name="allow_comments" <?=isset($editPost->allow_comments) ? ($editPost->allow_comments ? 'checked' : '') : 'checked' ?>>
                                                        <label class="form-check-label" for="allow_comments">
                                                            Allow Comments
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                </form>
                            </div>
                        </div>
                    
                    <?php else: ?>
                    
                        <h1 class="mt-4">Posts</h1>
                        <ol class="breadcrumb mb-4">
                            <li class="breadcrumb-item active">Blog / Posts</li>
                        </ol>
                        
                        <?php if(isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                        <?php endif; ?>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="fas fa-table me-1"></i>
                                        Blog Posts List
                                    </div>
                                    <div>
                                        <a href="?add=1" class="btn btn-sm btn-primary">
                                            <i class="fa-solid fa-plus"></i> Add Post
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
                                                <th>Title</th>
                                                <th>Category</th>
                                                <th>Status</th>
                                                <th>Published At</th>
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
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="<?=ADMIN_ASSET?>assets/editor/isolated-block-editor.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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
                    "data": "post_id",
                    "orderable": false,
                    "render": function(data, type, row) {
                        return `
                            <a href="?edit=${data}" class="btn p-0 ps-1"><i class="fa-solid fa-pencil"></i></a>
                            <a href="/${row.slug}" target="_blank" class="btn p-0 ps-1"><i class="fa-solid fa-eye"></i></a>
                            <button onclick="deletePost(${data})" class="btn p-0 ps-1"><i class="fa-solid fa-trash-can"></i></button>
                        `;
                    }
                },
                { "data": "post_id" },
                { 
                    "data": "title",
                    "render": function(data, type, row) {
                        return `<a href="?edit=${row.post_id}">${data}</a>`;
                    }
                },
                { 
                    "data": "category_name",
                    "render": function(data, type, row) {
                        return data || 'Uncategorized';
                    }
                },
                { 
                    "data": "status",
                    "render": function(data) {
                        const statusClass = data === 'published' ? 'success' : 
                                        data === 'draft' ? 'warning' : 'secondary';
                        return `<span class="badge bg-${statusClass}">${data}</span>`;
                    }
                },
                { 
                    "data": "published_at",
                    "render": function(data) {
                        return data ? new Date(data).toLocaleString() : 'Not published';
                    }
                }
            ],
            "order": [[1, 'desc']]
        });
        
        function deletePost(postId) {
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
                        url: '?delete=' + postId,
                        type: 'POST',
                        data: {
                            csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'Your post has been deleted.',
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
        
        // Initialize editor
        const editor = document.getElementById('editor');
        if(editor) {
            if(editor.style.display === 'none') {
                wp.detachEditor(editor);
            } else {
                wp.attachEditor(editor);
            }
            // Initialize datetime picker
            flatpickr("#published_at", {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true
            });
            // Auto-generate slug from title
            $('#title').on('blur', function() {
                if (!$('#slug').val()) {
                    const title = $(this).val();
                    const slug = title.toLowerCase()
                        .replace(/[^\w\s-]/g, '') // Remove non-word characters
                        .replace(/[\s_-]+/g, '-') // Replace spaces and underscores with hyphens
                        .replace(/^-+|-+$/g, ''); // Trim hyphens from start and end
                    $('#slug').val(slug);
                }
            });

            $('#slug').on('blur', function() {
                if (!$('#slug').val()) {
                    const title = $(this).val();
                    const slug = title.toLowerCase()
                        .replace(/[^\w\s-]/g, '') // Remove non-word characters
                        .replace(/[\s_-]+/g, '-') // Replace spaces and underscores with hyphens
                        .replace(/^-+|-+$/g, ''); // Trim hyphens from start and end
                    $('#slug').val(slug);
                }
            });

            $('#toggleEditorFullScreen').click(function() {
                const $iTag = $(this).find('i');
                const toggleClass = $(this).attr('data-toggle-class');
                $(this).attr('data-toggle-class',$iTag.attr('class'));
                $iTag.attr('class', toggleClass);
                $('.editor').toggleClass('full-screen');
            });
        }

        $('[data-max-length]').on('input', function() {
            var maxLength = $(this).data('max-length');
            var currentLength = $(this).val().length;
            if (currentLength > maxLength) {
                $(this).css('border', '2px solid red');
            } else {
                $(this).css('border', '2px solid green');
            }
        });
        
    </script>
</body>
</html>