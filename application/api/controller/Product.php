<?php


namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Config;
use think\Exception;

// Image ដើម្បីអាចអោយ Product Image Add TO DB
use think\facade\Filesystem;

/**
 * @ApiSector(Product)
 * * Created by Kuy Tangkoan
 * * @title Product
 */
class Product extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function show(){
        try{
            $status = $this->request->post("status");

            $data = DB::name('products')
                ->where('status', 1)
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

    

    public function addProduct()
    {
        try {
            $title = $this->request->post("title");
            $short_content = $this->request->post("short_content", "");
            $content = $this->request->post("content", "");

            // ✅ ពិនិត្យមើលថា title មានរួចហើយឬនៅ
            $exists = Db::name('products')->where('title', $title)->find();
            if ($exists) {
                return json(['code' => 0, 'msg' => 'This Product is already']);
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
                'status' => 1,
                'createtime' => time(),
                'updatetime' => time()
            ];

            // insert ទៅ DB
            $ProductId = Db::name('products')->insertGetId($dataToInsert);

            if ($ProductId) {
                // query ទិន្នន័យថ្មី
                $Product = Db::name('products')->where('id', $ProductId)->find();

                // បន្ថែម domain ទៅ image_path
                if (!empty($Product['image'])) {
                    $Product['image'] = request()->domain() . $Product['image'];
                }

                return json([
                    'code' => 1,
                    'msg' => 'Success',
                    'data' => $Product
                ]);
            } else {
                return json(['code' => 0, 'msg' => 'Failed']);
            }

        } catch (\Exception $e) {
            return json(['error' => $e->getMessage()]);
        }
    }


    public function editProduct()
    {
        try {
            $id = $this->request->post('id'); // id នៃ Product ដែលចង់ edit
            if (!$id) {
                return json(['code' => 0, 'msg' => 'Product ID is required']);
            }

            // ✅ Check if id is numeric
            if (!is_numeric($id)) {
                return json(['code' => 0, 'msg' => 'Product ID must be a number']);
            }

            $title = $this->request->post("title");
            $short_content = $this->request->post("short_content", "");
            $content = $this->request->post("content", "");

            // ពិនិត្យថា Product មានតែ id នេះឬទេ
            $Product = Db::name('products')->where('id', $id)->find();
            if (!$Product) {
                return json(['code' => 0, 'msg' => 'This Product not found']);
            }

            // ✅ ពិនិត្យមើលថា title មាន Product ផ្សេងមានដូចគ្នាមានទេ
            $exists = Db::name('products')
                        ->where('title', $title)
                        ->where('id', '<>', $id)
                        ->find();
            if ($exists) {
                return json(['code' => 0, 'msg' => 'This Product is already']);
            }

            $file = request()->file('image');
            $imagePath = $Product['image']; // default ជា previous image

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
            $result = Db::name('products')->where('id', $id)->update($dataToUpdate);

            if ($result !== false) {
                // query ទិន្នន័យថ្មី
                $Product = Db::name('products')->where('id', $id)->find();

                // បន្ថែម domain ទៅ image_path
                if (!empty($Product['image'])) {
                    $Product['image'] = request()->domain() . $Product['image'];
                }

                return json([
                    'code' => 1,
                    'msg' => 'Product updated successfully',
                    'data' => $Product
                ]);
            } else {
                return json(['code' => 0, 'msg' => 'Failed to update Product']);
            }

        } catch (\Exception $e) {
            return json(['error' => $e->getMessage()]);
        }
    }



    public function deleteProduct()
{
    try {
        $id = $this->request->post('id'); // Product id

        // ✅ Check if id is provided
        if (!$id) {
            return json(['code' => 0, 'msg' => 'Product ID is required']);
        }

        // ✅ Check if id is numeric
        if (!is_numeric($id)) {
            return json(['code' => 0, 'msg' => 'Product ID must be a number']);
        }

        // check Product exists
        $Product = Db::name('products')->where('id', $id)->find();
        if (!$Product) {
            return json(['code' => 0, 'msg' => 'Product not found']);
        }

        // check if any product is using this Product
        $productExists = Db::name('products')->where('Product_id', $id)->find();
        if ($productExists) {
            return json(['code' => 0, 'msg' => 'Cannot delete, Product is in use']);
        }

        // optional: delete image file from server
        if (!empty($Product['image'])) {
            $imagePath = ROOT_PATH . 'public' . DS . str_replace('/', DS, ltrim($Product['image'], '/'));
            if (file_exists($imagePath)) {
                @unlink($imagePath); // delete file safely
            }
        }

        // delete Product
        $result = Db::name('products')->where('id', $id)->delete();

        if ($result) {
            return json(['code' => 1, 'msg' => 'Product deleted successfully']);
        } else {
            return json(['code' => 0, 'msg' => 'Failed to delete Product']);
        }

    } catch (\Exception $e) {
        return json(['error' => $e->getMessage()]);
    }
}



}
