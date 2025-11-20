<?php


namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Config;
use think\Exception;


/**
 * @ApiSector(Category)
 * * Created by Kuy Tangkoan
 * * @title Tangkoancategory
 */
class Tangkoancategory extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function show()
    {
        try{
            $status = $this->request->post("status");

            $data = DB::name('tg_category')
                ->where('status', '1')
                ->select(); // $data គឺជា Array

            // 6. បញ្ជូនទិន្នន័យដែលបានកែរួច
            $this->success(__("Success"), $data);
            
        }catch(Exception $e){
            $this->error(__("Error"), $e->getMessage());
        }
    }

    

    public function create()
    {
        try {
            $title = $this->request->post("title");

            // 1. Validate empty
            if (!$title) {
                $this->error(__("Title is required"));
            }

            // 2. Check duplicate
            $exists = Db::name('tg_category')->where('title', $title)->find();
            if ($exists) {
                $this->error(__("This category already exists"));
            }

            // 3. Prepare data
            $data = [
                'title'       => $title,
                'status'      => "1",
                'createtime'  => time(),
                'updatetime'  => time()
            ];

            // 4. Insert
            $id = Db::name('tg_category')->insertGetId($data);

            // 5. Add inserted ID to return data
            $data['id'] = $id;

            // 6. Success response — ដូច show()
            $this->success(__("Success"), $data);

        } catch (Exception $e) {
            $this->error(__("Error"), $e->getMessage());
        }
    }


    public function edit()
    {
        try {
            $id     = $this->request->post("id");
            $title  = $this->request->post("title");
            $status = $this->request->post("status");

            // 1. Validate ID
            if (!$id) {
                $this->error(__("ID is required"));
            }

            // ✅ Check if id is numeric
            if (!is_numeric($id)) {
                $this->error(__("Category ID must be a number"));
            }

            // 2. Validate Title
            if (!$title) {
                $this->error(__("Title is required"));
            }

            // 3. Validate Status (ONLY 0 or 1 allowed)
            if (!in_array($status, ['0', '1', 0, 1], true)) {
                $this->error(__("Invalid status value. Allowed: 0 or 1"));
            }

            // 4. Check if category exists
            $category = Db::name('tg_category')->where('id', $id)->find();
            if (!$category) {
                $this->error(__("Category not found"));
            }

            // 5. Check duplicate title (exclude its own ID)
            $exists = Db::name('tg_category')
                ->where('title', $title)
                ->where('id', '<>', $id)
                ->find();

            if ($exists) {
                $this->error(__("This category already exists"));
            }

            // 6. Data to update
            $data = [
                'id' => $id,
                'title'      => $title,
                'status'     => $status,
                'updatetime' => time()
            ];

            // 7. Update
            Db::name('tg_category')->where('id', $id)->update($data);

            // Add ID to response
            $data['id'] = $id;

            // 8. Success
            $this->success(__("Success"), $data);

        } catch (Exception $e) {
            $this->error(__("Error"), $e->getMessage());
        }
    }


    public function deleteCategory()
    {
        try {
            $id = $this->request->post('id'); // category id

            // ✅ Check if id is provided
            if (!$id) {
                return json(['code' => 0, 'msg' => 'Category ID is required']);
            }

            // ✅ Check if id is numeric
            if (!is_numeric($id)) {
                return json(['code' => 0, 'msg' => 'Category ID must be a number']);
            }

            // check category exists
            $category = Db::name('categories')->where('id', $id)->find();
            if (!$category) {
                return json(['code' => 0, 'msg' => 'Category not found']);
            }

            // check if any product is using this category
            $productExists = Db::name('products')->where('category_id', $id)->find();
            if ($productExists) {
                return json(['code' => 0, 'msg' => 'Cannot delete, category is in use']);
            }

            // optional: delete image file from server
            if (!empty($category['image'])) {
                $imagePath = ROOT_PATH . 'public' . DS . str_replace('/', DS, ltrim($category['image'], '/'));
                if (file_exists($imagePath)) {
                    @unlink($imagePath); // delete file safely
                }
            }

            // delete category
            $result = Db::name('categories')->where('id', $id)->delete();

            if ($result) {
                return json(['code' => 1, 'msg' => 'Category deleted successfully']);
            } else {
                return json(['code' => 0, 'msg' => 'Failed to delete category']);
            }

        } catch (Exception $e) {
            return json(['error' => $e->getMessage()]);
        }
    }

    public function details()
    {
        try {
            $id = $this->request->post('id');

            if (!$id || !is_numeric($id)) {
                return json(['code' => 0, 'msg' => 'Invalid category ID']);
            }

            // 1. Category info
            $category = Db::name('categories')->where('id', $id)->find();
            if (!$category) {
                return json(['code' => 0, 'msg' => 'Category not found']);
            }

            // 2. Products of this category
            $products = Db::name('products')
                ->where('category_id', $id)
                ->where('status', '1')
                ->select();

            // 3. Domain
            $domain = $this->request->domain();

            // 4. Attach domain to category image
            if (!empty($category['image'])) {
                $category['image'] = $domain . $category['image'];
            }

            // 5. Attach domain to product images
            foreach ($products as &$product) {
                if (!empty($product['image'])) {
                    $product['image'] = $domain . $product['image'];
                }
            }

            // 6. Final output
            $result = [
                'category' => $category,
                'products' => $products
            ];

            return json([
                'code' => 1,
                'msg' => 'Success',
                'data' => $result
            ]);

        } catch (Exception $e) {
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }
    }



}
