<?php


namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Config;
use think\Exception;

// Image ដើម្បីអាចអោយ Category Image Add TO DB
use think\facade\Filesystem;

/**
 * @ApiSector(Category)
 * * Created by Kuy Tangkoan
 * * @title Category
 */
class Category extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function show(){
        try{
            $status = $this->request->post("status");

            $data = DB::name('categories')
                ->where('status', 'public')
                ->select(); // $data គឺជា Array

            // 1. យក Domain
            $domain = $this->request->domain();
            
            // 2. បង្កើត Array ថ្មីមួយដើម្បីទុកទិន្នន័យដែលបានកែរួច
            $formattedData = [];

            // 3. ប្រើ foreach របស់ PHP ជំនួស ->each()
            foreach ($data as $item) {
                // ពិនិត្យមើលថា $item['image'] មានតម្លៃ
                if (isset($item['image']) && !empty($item['image'])) {
                    // 4. ភ្ជាប់ Domain ទៅខាងមុខ Path
                    $item['image'] = $domain . $item['image'];
                }
                // 5. បញ្ចូល $item ដែលបានកែហើយទៅក្នុង Array ថ្មី
                $formattedData[] = $item;
            }

            // 6. បញ្ជូនទិន្នន័យដែលបានកែរួច
            $this->success(__("Success"), $formattedData);
            
        }catch(Exception $e){
            $this->error(__("Error"), $e->getMessage());
        }
    }

    

    public function addCategory()
    {
        try {
            $title = $this->request->post("title");
            $short_content = $this->request->post("short_content", "");
            $content = $this->request->post("content", "");

            // ✅ ពិនិត្យមើលថា title មានរួចហើយឬនៅ
            $exists = Db::name('categories')->where('title', $title)->find();
            if ($exists) {
                return json(['code' => 0, 'msg' => 'This category is already']);
            }

            $file = request()->file('image');
            $imagePath = null;

            if ($file) {
                // ផ្ទុកទៅក្នុង public/uploads/
                $uploadDir = ROOT_PATH . 'public' . DS . 'uploads';
                $info = $file->move($uploadDir);
                if ($info) {
                    // path ដែលរក្សាទុកក្នុង DB
                    $imagePath = '/uploads/' . str_replace('\\', '/', $info->getSaveName());
                } else {
                    return json(['code' => 0, 'msg' => $file->getError()]);
                }
            }

            // រៀបចំទិន្នន័យសម្រាប់ insert
            $dataToInsert = [
                'title' => $title,
                'image' => $imagePath,
                'short_content' => $short_content,
                'content' => $content,
                'status' => "public",
                'createtime' => time(),
                'updatetime' => time()
            ];

            // insert ទៅ DB
            $categoryId = Db::name('categories')->insertGetId($dataToInsert);

            if ($categoryId) {
                // query ទិន្នន័យថ្មី
                $category = Db::name('categories')->where('id', $categoryId)->find();

                // បន្ថែម domain ទៅ image_path
                if (!empty($category['image'])) {
                    $category['image'] = request()->domain() . $category['image'];
                }

                return json([
                    'code' => 1,
                    'msg' => 'Success',
                    'data' => $category
                ]);
            } else {
                return json(['code' => 0, 'msg' => 'Failed']);
            }

        } catch (\Exception $e) {
            return json(['error' => $e->getMessage()]);
        }
    }


    public function editCategory()
    {
        try {
            $id = $this->request->post('id'); // id នៃ category ដែលចង់ edit
            if (!$id) {
                return json(['code' => 0, 'msg' => 'Category ID is required']);
            }

            // ✅ Check if id is numeric
            if (!is_numeric($id)) {
                return json(['code' => 0, 'msg' => 'Category ID must be a number']);
            }

            $title = $this->request->post("title");
            $short_content = $this->request->post("short_content", "");
            $content = $this->request->post("content", "");

            // ពិនិត្យថា category មានតែ id នេះឬទេ
            $category = Db::name('categories')->where('id', $id)->find();
            if (!$category) {
                return json(['code' => 0, 'msg' => 'This Category not found']);
            }

            // ✅ ពិនិត្យមើលថា title មាន category ផ្សេងមានដូចគ្នាមានទេ
            $exists = Db::name('categories')
                        ->where('title', $title)
                        ->where('id', '<>', $id)
                        ->find();
            if ($exists) {
                return json(['code' => 0, 'msg' => 'This category is already']);
            }

            $file = request()->file('image');
            $imagePath = $category['image']; // default ជា previous image

            if ($file) {
                // ផ្ទុកទៅ public/uploads/
                $uploadDir = ROOT_PATH . 'public' . DS . 'uploads';
                $info = $file->move($uploadDir);
                if ($info) {
                    // replace previous image
                    $imagePath = '/uploads/' . str_replace('\\', '/', $info->getSaveName());
                } else {
                    return json(['code' => 0, 'msg' => $file->getError()]);
                }
            }

            // រៀបចំ data សម្រាប់ update
            $dataToUpdate = [
                'title' => $title,
                'image' => $imagePath,
                'short_content' => $short_content,
                'content' => $content,
                'updatetime' => time()
            ];

            // update DB
            $result = Db::name('categories')->where('id', $id)->update($dataToUpdate);

            if ($result !== false) {
                // query ទិន្នន័យថ្មី
                $category = Db::name('categories')->where('id', $id)->find();

                // បន្ថែម domain ទៅ image_path
                if (!empty($category['image'])) {
                    $category['image'] = request()->domain() . $category['image'];
                }

                return json([
                    'code' => 1,
                    'msg' => 'Category updated successfully',
                    'data' => $category
                ]);
            } else {
                return json(['code' => 0, 'msg' => 'Failed to update category']);
            }

        } catch (\Exception $e) {
            return json(['error' => $e->getMessage()]);
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

    } catch (\Exception $e) {
        return json(['error' => $e->getMessage()]);
    }
}



}
