<?php


namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Config;
use think\Exception;


/**
 * @ApiSector(Stock)
 * * Created by Kuy Tangkoan
 * * @title Stock
 */
class Stock extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function show()
    {
        
        try{
            $status = $this->request->post("status");

            $data = DB::name('stock')
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

    public function addStock()
    {
        try {
            $product_id = $this->request->post("product_id");
            $quantity = $this->request->post("quantity");
            $type = (int)$this->request->post("type"); // 1 = Add, 2 = Remove
            $status = $this->request->post("status");
            $date = $this->request->post("date");

            // ✅ ត្រួតពិនិត្យ field ចាំបាច់
            if (empty($product_id) || empty($quantity) || empty($type)) {
                return json(['code' => 0, 'msg' => 'Please fill all required fields']);
            }

            if (!is_numeric($product_id)) {
                return json(['code' => 0, 'msg' => 'Product ID must be a number']);
            }

            if (!is_numeric($quantity) || $quantity <= 0) {
                return json(['code' => 0, 'msg' => 'Quantity must be a positive number']);
            }

            // ✅ ពិនិត្យថា product មានឬអត់
            $product = Db::name('products')->where('id', $product_id)->find();
            if (!$product) {
                return json(['code' => 0, 'msg' => 'Invalid product_id: product not found']);
            }

            // ✅ ទាញស្តុកបច្ចុប្បន្នពី table products
            $currentStock = (int)$product['current_stock'];

            // ✅ ប្រសិនបើជាដំណើរការដកស្តុក ត្រូវតែពិនិត្យថាបន្ទាប់ពីដកមិនអាចតិចជាង 0
            if ($type == 2) {
                $newStock = $currentStock - $quantity;

                if ($newStock < 0) {
                    return json([
                        'code' => 0,
                        'msg' => 'Cannot remove more than available stock. Current stock: ' . $currentStock
                    ]);
                }
            } else {
                $newStock = $currentStock + $quantity;
            }

            // ✅ កំណត់តម្លៃថ្ងៃ (ប្រើបច្ចុប្បន្ន បើគ្មានអ្នកផ្ដល់)
            $dateTime = !empty($date) ? date('Y-m-d H:i:s', strtotime($date)) : date('Y-m-d H:i:s');

            // ✅ បង្កើតទិន្នន័យសម្រាប់ insert log ទៅ stock table
            $dataToInsert = [
                'product_id' => $product_id,
                'quantity' => $type == 1 ? $quantity : -$quantity, // ដកស្តុកចេញជាលេខអវិជ្ជមាន
                'type' => $type,
                'status' => $status ?? 1,
                'date' => $dateTime, // ✅ datetime format
                'createtime' => time(),
                'updatetime' => time()
            ];

            // ✅ Insert ទៅ stock (ប្រវត្តិស្តុក)
            $stockId = Db::name('stock')->insertGetId($dataToInsert);

            if ($stockId) {
                // ✅ បន្ទាប់ពីបន្ថែម ឬដក stock ត្រូវ update ទៅក្នុង product.main
                Db::name('products')
                    ->where('id', $product_id)
                    ->update([
                        'current_stock' => $newStock,
                        'updatetime' => date('Y-m-d H:i:s')
                    ]);

                return json([
                    'code' => 1,
                    'msg' => $type == 1 ? 'Stock added successfully' : 'Stock removed successfully',
                    'current_stock' => $newStock
                ]);
            } else {
                return json(['code' => 0, 'msg' => 'Failed to process stock']);
            }

        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }
    }

    public function editStock()
    {
        try {
            $id = $this->request->post("id"); // stock id
            $quantity = $this->request->post("quantity");
            $type = (int)$this->request->post("type"); // 1 = Add, 2 = Remove
            $status = $this->request->post("status");
            $date = $this->request->post("date");

            // ✅ ត្រួតពិនិត្យ field ចាំបាច់
            if (empty($id) || empty($quantity) || empty($type)) {
                return json(['code' => 0, 'msg' => 'Please fill all required fields']);
            }

            if (!is_numeric($id)) {
                return json(['code' => 0, 'msg' => 'Stock ID must be a number']);
            }

            if (!is_numeric($quantity) || $quantity <= 0) {
                return json(['code' => 0, 'msg' => 'Quantity must be a positive number']);
            }

            // ✅ ទាញ stock record ចាស់
            $oldStock = Db::name('stock')->where('id', $id)->find();
            if (!$oldStock) {
                return json(['code' => 0, 'msg' => 'Stock record not found']);
            }

            $product_id = $oldStock['product_id'];

            // ✅ ទាញ product
            $product = Db::name('products')->where('id', $product_id)->find();
            if (!$product) {
                return json(['code' => 0, 'msg' => 'Product not found']);
            }

            // ✅ Recalculate current_stock temporarily
            $stocks = Db::name('stock')->where('product_id', $product_id)->select();
            $tempTotalStock = 0;
            foreach ($stocks as $s) {
                if ($s['id'] == $id) {
                    // replace old quantity/type with new quantity/type
                    $tempTotalStock += ($type == 1 ? $quantity : -$quantity);
                } else {
                    $tempTotalStock += ($s['type'] == 1 ? $s['quantity'] : -$s['quantity']);
                }
            }

            // ✅ Check if new stock < 0
            if ($tempTotalStock < 0) {
                return json([
                    'code' => 0,
                    'msg' => 'Cannot update stock: current stock would become negative'
                ]);
            }

            // ✅ Update stock record
            $updateData = [
                'quantity' => $quantity,
                'type' => $type,
                'status' => $status ?? $oldStock['status'],
                'date' => $date ? strtotime($date) : $oldStock['date'],
                'updatetime' => time()
            ];
            Db::name('stock')->where('id', $id)->update($updateData);

            // ✅ Update current_stock in products table
            Db::name('products')->where('id', $product_id)
                ->update([
                    'current_stock' => $tempTotalStock,
                    'updatetime' => time()
                ]);

            return json([
                'code' => 1,
                'msg' => 'Stock record updated successfully',
                'current_stock' => $tempTotalStock
            ]);

        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }
    }

    

}
