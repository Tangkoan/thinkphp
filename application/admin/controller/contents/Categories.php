<?php

namespace app\admin\controller\contents;

use app\common\controller\Backend;
use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Categories extends Backend
{

    /**
     * Categories模型对象
     * @var \app\admin\model\contents\Categories
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\contents\Categories;
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
            $w = ['status' => '1'];

            $q_word = $this->request->request("q_word/a", []);
            $query = Db::name('categories')->where($w);

            if (array_filter($q_word)) {
                $wq = [];
                foreach ($q_word as $value) {
                    if ($value !== '') {
                        $wq[] = ['title', 'like', "%{$value}%"];
                    }
                }
                if (!empty($wq)) {
                    $query = $query->whereOr($wq);
                }
            }

            $data = $query->field('id,title')->select();

            // ✅ បង្កើត structure ត្រឹមត្រូវសម្រាប់ selectpage
            return json(['list' => $data]);
        }
    }




}
