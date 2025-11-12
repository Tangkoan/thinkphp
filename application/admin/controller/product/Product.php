<?php

namespace app\admin\controller\product;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Product extends Backend
{

    /**
     * Product模型对象
     * @var \app\admin\model\product\Product
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\product\Product;
        $this->view->assign("statusList", $this->model->getStatusList());
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    // public function index()
    // {
    //     if ($this->request->isAjax()) {
    //         list($where, $sort, $order, $offset, $limit) = $this->buildparams();
    //         if ($this->request->request('keyField')) {
    //             return $this->selectpage();
    //         }


    //         $list = $this->model
    //         ->with(["category"])
    //         ->where(function($query) use ($where){
    //             // $where ត្រូវជា array
    //             if(is_array($where)){
    //                 // status belong to product table
    //                 if(isset($where['status'])){
    //                     $query->where('product.status', $where['status']);
    //                     unset($where['status']);
    //                 }
    //                 // បន្ថែម where ផ្សេងៗ
    //                 foreach($where as $k => $v){
    //                     $query->where($k, $v);
    //                 }
    //             }
    //         })
    //         ->order($sort, $order)
    //         ->paginate($limit);

    //         $result = array("total" => $list->total(), "rows" => $list->items());

    //         return json($result);
    //     }
    //     return $this->view->fetch();
    // }




    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->isAjax()) {
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $filter = $this->request->request('filter');
            $params = json_decode(urldecode($filter), true);

            $op_filter = $this->request->request('op');
            $op = json_decode(urldecode($op_filter), true);

            $w = [];

            if ($params && $op) {
                $new_parmas = [];
                $new_op = [];

                foreach ($params as $key => $value) {
                    $new_parmas["s.$key"] = $value;
                    $new_op["s.$key"] = $op[$key];
                }

                // បង្កើត where condition
                $w = $this->rewriteQuery($new_parmas, $new_op);
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 🟩 Query សំខាន់សម្រាប់ទាញ Data
            $list = $this->model
                ->alias('s')
                ->join('fa_categories sc', 's.category_id = sc.id', 'LEFT')
                ->field('s.*, sc.title as `category.title`')
                ->where($w)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 🟩 ការគណនាចំនួនសរុបសម្រាប់ pagination
            $total = $this->model
                ->alias('s')
                ->join('fa_categories sc', 's.category_id = sc.id', 'LEFT')
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


    

    

}