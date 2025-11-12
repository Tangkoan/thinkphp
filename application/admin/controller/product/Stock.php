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
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $list = $this->model
            ->with(["product"])
            ->where(function($query) use ($where){
                // $where ត្រូវជា array
                if(is_array($where)){
                    // status belong to stock table
                    if(isset($where['status'])){
                        $query->where('stock.status', $where['status']);
                        unset($where['status']);
                    }
                    // បន្ថែម where ផ្សេងៗ
                    foreach($where as $k => $v){
                        $query->where($k, $v);
                    }
                }
            })
            ->order($sort, $order)
            ->paginate($limit);
    
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
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
