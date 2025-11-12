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
                ->where('status',"1")
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
            $product_name = $this->request->post("product_name");
            $category_id = $this->request->post("category_id");
            $product_details = $this->request->post("product_details");
            $product_cost = $this->request->post("product_cost");
            $product_price = $this->request->post("product_price");
            $status = $this->request->post("status");

            // ✅ ពិនិត្យថា category_id មានក្នុង categories table ឬអត់
            $categoryExists = Db::name('categories')->where('id', $category_id)->find();
            if (!$categoryExists) {
                return json(['code' => 0, 'msg' => 'Category ID does not exist']);
            }

            // ✅ ពិនិត្យថា product មានរួចហើយឬនៅ
            $exists = Db::name('products')->where('product_name', $product_name)->find();
            if ($exists) {
                return json(['code' => 0, 'msg' => 'This product already exists']);
            }

            $file = request()->file('image');
            $imagePath = null;

            if ($file) {
                $uploadDir = ROOT_PATH . 'public' . DS . 'uploads';
                $info = $file->move($uploadDir);
                if ($info) {
                    $imagePath = '/uploads/' . str_replace('\\', '/', $info->getSaveName());
                } else {
                    return json(['code' => 0, 'msg' => $file->getError()]);
                }
            }

            // ✅ រៀបចំទិន្នន័យសម្រាប់ insert
            $dataToInsert = [
                'product_name' => $product_name,
                'image' => $imagePath,
                'category_id' => $category_id,
                'product_details' => $product_details,
                'status' => $status ?: 'public',
                'product_cost' => $product_cost,
                'product_price' => $product_price,
                'current_stock' => 0,
                'createtime' => time(),
                'updatetime' => time()
            ];

            // ✅ Insert ទៅ DB
            $newProductId = Db::name('products')->insertGetId($dataToInsert);

            if ($newProductId) {
                $Product = Db::name('products')->where('id', $newProductId)->find();
                if (!empty($Product['image'])) {
                    $Product['image'] = request()->domain() . $Product['image'];
                }
                return json(['code' => 1, 'msg' => 'Product added successfully', 'data' => $Product]);
            } else {
                return json(['code' => 0, 'msg' => 'Failed to insert product']);
            }

        } catch (\Exception $e) {
            return json(['error' => $e->getMessage()]);
        }
    }

    public function editProduct()
    {
        try {
            $id = $this->request->post('id');
            $product_name = $this->request->post("product_name");
            $category_id = $this->request->post("category_id");
            $product_details = $this->request->post("product_details");
            $product_cost = $this->request->post("product_cost");
            $product_price = $this->request->post("product_price");
            $status = $this->request->post("status");

            // ✅ ពិនិត្យថា product មានក្នុង DB ឬអត់
            $product = Db::name('products')->where('id', $id)->find();
            if (!$product) {
                return json(['code' => 0, 'msg' => 'Product not found']);
            }

            // ✅ ពិនិត្យថា category មានក្នុង DB ឬអត់
            $categoryExists = Db::name('categories')->where('id', $category_id)->find();
            if (!$categoryExists) {
                return json(['code' => 0, 'msg' => 'Category ID does not exist']);
            }

            $file = request()->file('image');
            $imagePath = $product['image']; // រក្សារូបចាស់ជាលំនាំដើម

            if ($file) {
                $uploadDir = ROOT_PATH . 'public' . DS . 'uploads';
                $info = $file->move($uploadDir);
                if ($info) {
                    // លុបរូបចាស់
                    if (!empty($product['image']) && file_exists(ROOT_PATH . 'public' . $product['image'])) {
                        unlink(ROOT_PATH . 'public' . $product['image']);
                    }
                    // ផ្ទុករូបថ្មី
                    $imagePath = '/uploads/' . str_replace('\\', '/', $info->getSaveName());
                } else {
                    return json(['code' => 0, 'msg' => $file->getError()]);
                }
            }

            // ✅ រៀបចំទិន្នន័យសម្រាប់ update
            $dataToUpdate = [
                'product_name' => $product_name,
                'category_id' => $category_id,
                'product_details' => $product_details,
                'product_cost' => $product_cost,
                'product_price' => $product_price,
                'status' => $status ?: 'public',
                'image' => $imagePath,
                'updatetime' => time(),
            ];

            $updated = Db::name('products')->where('id', $id)->update($dataToUpdate);

            if ($updated !== false) {
                $updatedProduct = Db::name('products')->where('id', $id)->find();
                if (!empty($updatedProduct['image'])) {
                    $updatedProduct['image'] = request()->domain() . $updatedProduct['image'];
                }
                return json(['code' => 1, 'msg' => 'Product updated successfully', 'data' => $updatedProduct]);
            } else {
                return json(['code' => 0, 'msg' => 'Failed to update product']);
            }

        } catch (\Exception $e) {
            return json(['error' => $e->getMessage()]);
        }
    }

    public function deleteProduct()
    {
        try {
            $id = $this->request->post('id');

            if (!$id) {
                return json(['code' => 0, 'msg' => 'Product ID is required']);
            }

            // ✅ ពិនិត្យថា product មានក្នុង DB ឬអត់
            $product = Db::name('products')->where('id', $id)->find();
            if (!$product) {
                return json(['code' => 0, 'msg' => 'Product not found']);
            }

            // ✅ ពិនិត្យថា product នេះត្រូវបានប្រើក្នុង table stock ឬអត់
            $usedInStock = Db::name('stock')->where('product_id', $id)->find();
            if ($usedInStock) {
                return json(['code' => 0, 'msg' => 'Cannot delete this product because it exists in stock records']);
            }

            // ✅ លុបរូបភាពចេញពី server (បើមាន)
            if (!empty($product['image']) && file_exists(ROOT_PATH . 'public' . $product['image'])) {
                unlink(ROOT_PATH . 'public' . $product['image']);
            }

            // ✅ លុបទិន្នន័យពី DB
            $deleted = Db::name('products')->where('id', $id)->delete();

            if ($deleted) {
                return json(['code' => 1, 'msg' => 'Product deleted successfully']);
            } else {
                return json(['code' => 0, 'msg' => 'Failed to delete product']);
            }

        } catch (\Exception $e) {
            return json(['error' => $e->getMessage()]);
        }
    }


}
