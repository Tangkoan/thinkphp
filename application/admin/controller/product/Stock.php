<?php

namespace app\admin\controller\product;

use app\common\controller\Backend;
use think\Db; // ✅ បន្ថែមបន្ទាត់នេះ

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Stock extends Backend
{

    /**
     * Stock模型对象
     * @var \app\admin\model\product\Stock
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\product\Stock;
        $this->view->assign("statusList", $this->model->getStatusList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    public function index()
    {
        // សម្អាតទិន្នន័យពី Request ដើម្បីជៀសវាងសុវត្ថិភាព និងពាក្យបញ្ចូលមិនចាំបាច់
        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->isAjax()) {
            // ប្រើសម្រាប់ Selectpage នៅលើ frontend (ការរើសទិន្នន័យដោយស្វ័យប្រវត្តិ)
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            // ទទួល filter និង operator (op) ពី frontend
            $filter = $this->request->request('filter');
            $params = json_decode(urldecode($filter), true);

            $op_filter = $this->request->request('op');
            $op = json_decode(urldecode($op_filter), true);

            $w = [];

            // បើមាន filter និង op => បម្លែងឲ្យសមទៅ alias 's'
            if ($params && $op) {
                $new_parmas = [];
                $new_op = [];

                foreach ($params as $key => $value) {
                    // ដាក់ alias 's.' ដើម្បីបញ្ជាក់ថា field មកពីតារាងសំខាន់ (stock table)
                    $new_parmas["s.$key"] = $value;
                    $new_op["s.$key"] = $op[$key];
                }

                // បង្កើត where condition
                $w = $this->rewriteQuery($new_parmas, $new_op);
            }

            // បង្កើត parameter សម្រាប់ where, sort, order, offset, limit
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 🟩 Query សំខាន់សម្រាប់ទាញ Data
            $list = $this->model
                ->alias('s') // 👉 ដាក់ alias 's' សម្រាប់តារាងសំខាន់ (stock table)
                
                // 🟩 JOIN ទៅតារាង Product
                // 'fa_products' គឺឈ្មោះតារាងពិតនៅក្នុង database ដែលផ្ទុកទិន្នន័យ Product
                // យើង join ដើម្បីយកឈ្មោះ Productតាម product_id
                // 'sc' ជា alias សម្រាប់ fa_products ដើម្បីសរសេរងាយក្នុង field()
                ->join('fa_products sc', 's.product_id = sc.id', 'LEFT')

                // 🟩 ជ្រើសយក Field ដែលត្រូវការ
                // s.* = ទាញទិន្នន័យទាំងអស់ពី stock table
                // sc.product_name = យកឈ្មោះ Product ពីតារាង fa_products
                // និងដាក់ alias ថា 'product.product_name' ដើម្បីអោយបង្ហាញលើ frontend ជា field nested
                ->field('s.*, sc.product_name as `product.product_name`')

                // ដាក់ where ដើម្បី filter ទិន្នន័យ
                ->where($w)

                // ដាក់លំដាប់តាម $sort និង $order
                ->order($sort, $order)

                // កំណត់ចំនួនដែលត្រូវបង្ហាញក្នុងមួយទំព័រ
                ->limit($offset, $limit)

                // ទាញទិន្នន័យចេញពី database
                ->select();

            // 🟩 ការគណនាចំនួនសរុបសម្រាប់ pagination
            $total = $this->model
                ->alias('s')
                ->join('fa_products sc', 's.product_id = sc.id', 'LEFT')
                ->where($w)
                ->count();

            // 🟩 បម្លែង Collection ទៅជា array
            $rows = collection($list)->toArray();

            // 🟩 បញ្ចូលចូលក្នុង result ដើម្បីបញ្ជូនទៅ frontend ជា JSON
            $result = [
                "total" => $total,
                "rows" => $rows
            ];

            return json($result);
        }

        // បើមិនមែន Ajax => បង្ហាញ View ទូទៅ
        return $this->view->fetch();
    }





    
    
    public function product()
    {
        if ($this->request->isAjax()) {
            $w = ['status' => '1'];

            $q_word = $this->request->request("q_word/a", []);
            $query = Db::name('products')->where($w);

            if (array_filter($q_word)) {
                $wq = [];
                foreach ($q_word as $value) {
                    if ($value !== '') {
                        $wq[] = ['product_name', 'like', "%{$value}%"];
                    }
                }
                if (!empty($wq)) {
                    $query = $query->whereOr($wq);
                }
            }

            $data = $query->field('id,product_name')->select();

            // ✅ បង្កើត structure ត្រឹមត្រូវសម្រាប់ selectpage
            return json(['list' => $data]);
        }
    }


    /**
     * ផ្ទាំងបង្ហាញព័ត៌មានលម្អិត (Details View)
     */
    public function details($ids = null)
    {
        if (!$ids) {
            $this->error(__('Invalid parameters'));
        }

        // ✅ ដំណោះស្រាយ៖ បញ្ជាក់ឈ្មោះតារាងឲ្យច្បាស់
        $tableName = $this->model->getTable(); // យកឈ្មោះតារាង 'stock'

        // ប្រើ $tableName . '.id' ជំនួសឲ្យ 'id'
        $row = $this->model
            ->with('product')
            ->where($tableName . '.id', $ids) // ⬅️ កែនៅត្រង់នេះ
            ->find();
        
        if (!$row) {
            $this->error(__('No Results were found'));
        }

        // បញ្ជូនទៅទំព័រ view
        $this->view->assign('row', $row);
        return $this->view->fetch();
    }


}
