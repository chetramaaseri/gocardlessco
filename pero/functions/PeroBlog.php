<?php
use Illuminate\Database\Capsule\Manager as Capsule;
class PeroBlog
{
    public $user_uz;
    public $today;
    public function __construct(){
        $this->user_uz = new DateTimeZone('Asia/Kolkata');
        $this->today = new DateTime('', $this->user_uz);
    }

    public function getById(int $post_id){
        $post = $capsule->table('blog_posts')
            ->where('post_id', $post_id)
            ->where('status', 'published')
            ->where(function($query) {
                $query->whereNull('published_at')
                      ->orWhere('published_at', '<=', date('Y-m-d H:i:s'));
            })
            ->leftJoin('blog_categories', 'blog_posts.category_id', '=', 'blog_categories.category_id')
            ->leftJoin('users', 'blog_posts.author_id', '=', 'users.user_id')
            ->leftJoin('dynamic_routes as pr', function($join) {
                $join->on('pr.entity_id', '=', 'blog_posts.post_id')
                    ->where('pr.entity_type', 'post');
            })
            ->leftJoin('dynamic_routes as cr', function($join) {
                $join->on('cr.entity_id', '=', 'blog_posts.category_id')
                    ->where('cr.entity_type', 'blog_category');
            })
            ->select(
                'blog_posts.*', 
                'pr.route as slug', 
                'cr.route as category_slug', 
                'users.name as author_name', 
                'blog_categories.name as category_name'
            )
            ->first();

        if($post){
            $capsule->table('blog_posts')
                ->where('post_id', $post_id)
                ->increment('view_count');
        }
        return $post;
    }

    public function getAllByCategoryId(int $categoryId, int $currentPage = 1, int $perPage = 5){
        $query = Capsule::table('blog_posts')
            ->where('blog_posts.category_id', $categoryId)
            ->leftJoin('blog_categories', 'blog_posts.category_id', '=', 'blog_categories.category_id')
            ->leftJoin('users', 'blog_posts.author_id', '=', 'users.user_id')
            ->leftJoin('dynamic_routes as pr', function($join) {
                $join->on('pr.entity_id', '=', 'blog_posts.post_id')
                    ->where('pr.entity_type', 'post');
            })
            ->leftJoin('dynamic_routes as cr', function($join) {
                $join->on('cr.entity_id', '=', 'blog_posts.category_id')
                    ->where('cr.entity_type', 'blog_category');
            })
            ->select(
                'blog_posts.*', 
                'pr.route as slug', 
                'cr.route as category_slug', 
                'users.name as author_name', 
                'blog_categories.name as category_name'
            );
    
        $query->orderBy('published_at', 'desc');
        $totalPosts = $query->count();
        $totalPages = ceil($totalPosts / $perPage);
        $posts = $query->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();
        return [$posts,$currentPage,$totalPages,$perPage];
    }

    public function getAllByAuthorId(int $authorId, int $currentPage = 1, int $perPage = 5){
        $query = Capsule::table('blog_posts')
            ->where('users.user_id', $authorId)
            ->leftJoin('blog_categories', 'blog_posts.category_id', '=', 'blog_categories.category_id')
            ->leftJoin('users', 'blog_posts.author_id', '=', 'users.user_id')
            ->leftJoin('dynamic_routes as pr', function($join) {
                $join->on('pr.entity_id', '=', 'blog_posts.post_id')
                    ->where('pr.entity_type', 'post');
            })
            ->leftJoin('dynamic_routes as cr', function($join) {
                $join->on('cr.entity_id', '=', 'blog_posts.category_id')
                    ->where('cr.entity_type', 'blog_category');
            })
            ->select(
                'blog_posts.*', 
                'pr.route as slug', 
                'cr.route as category_slug', 
                'users.name as author_name', 
                'blog_categories.name as category_name'
            );
    
        $query->orderBy('published_at', 'desc');
        $totalPosts = $query->count();
        $totalPages = ceil($totalPosts / $perPage);
        $posts = $query->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();
        return [$posts,$currentPage,$totalPages,$perPage];
    }
    
    public function getAllCategories(){
        $categories = Capsule::table('blog_categories')
            ->leftJoin('dynamic_routes as cr', function($join) {
                $join->on('cr.entity_id', '=', 'blog_categories.category_id')
                    ->where('cr.entity_type', 'blog_category');
            })
            ->select('blog_categories.*', 'cr.route as category_slug')
            ->get();
    
        return $categories;
    }
}