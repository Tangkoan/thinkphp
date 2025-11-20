<?php

namespace app\admin\controller\tg;

use app\common\controller\Backend;

use think\Db;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Product extends Backend
{

    /**
     * Product模型对象
     * @var \app\admin\model\tg\Product
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\tg\Product;
        $this->view->assign("statusList", $this->model->getStatusList()); 
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    public function category()
    {
        if ($this->request->isAjax()) {
            // 1. ករណីបង្ហាញឈ្មោះក្នុង Form Edit
            if ($this->request->request("keyValue")) {
                $value = $this->request->request("keyValue");
                $data = Db::name('tg_category')
                    ->where('id', $value)
                    ->field('id,title') // 👈 សំខាន់ណាស់
                    ->select();
                return json(['list' => $data]);
            }

            $page = $this->request->request("pageNumber", 1);
            $pagesize = $this->request->request("pageSize", 10);
            $q_word = $this->request->request("q_word/a", []);
            $q_word = array_filter($q_word);

            $searchLogic = function ($query) use ($q_word) {
                if (!empty($q_word)) {
                    $query->where(function ($q) use ($q_word) {
                        foreach ($q_word as $value) {
                            $q->whereOr('title', 'like', "%{$value}%");
                        }
                    });
                }
            };

            // Count
            $countQuery = Db::name('tg_category')->where('status', '1');
            $searchLogic($countQuery);
            $total = $countQuery->count();

            // List
            $listQuery = Db::name('tg_category')->where('status', '1');
            $searchLogic($listQuery);
            $list = $listQuery
                ->field('id,title') // 👈 ត្រូវតែដាក់ ដើម្បីឲ្យ SelectPage ស្គាល់ ID ច្បាស់
                ->page($page, $pagesize)
                ->select();

            return json(['list' => $list, 'total' => $total]);
        }
    }

    public function unit()
    {
        if ($this->request->isAjax()) {
            // 1️⃣ ករណីបង្ហាញឈ្មោះក្នុង Form Edit (KeyValue)
            if ($this->request->request("keyValue")) {
                $value = $this->request->request("keyValue");
                $data = Db::name('tg_unit')
                    ->where('id', $value)
                    ->field('id,title')
                    ->select();
                return json(['list' => $data]);
            }

            // 2️⃣ ទទួលយក Pagination និង Search Keyword
            $page = $this->request->request("pageNumber", 1);
            $pagesize = $this->request->request("pageSize", 10);
            $q_word = $this->request->request("q_word/a", []);
            $q_word = array_filter($q_word); // សម្អាតតម្លៃទទេចេញ

            // 3️⃣ កំណត់លក្ខខណ្ឌ Search
            $searchLogic = function ($query) use ($q_word) {
                if (!empty($q_word)) {
                    $query->where(function ($q) use ($q_word) {
                        foreach ($q_word as $value) {
                            $q->whereOr('title', 'like', "%{$value}%");
                        }
                    });
                }
            };

            // 4️⃣ រាប់ចំនួនសរុប (Total)
            $countQuery = Db::name('tg_unit')->where('status', '1');
            $searchLogic($countQuery);
            $total = $countQuery->count();

            // 5️⃣ ទាញទិន្នន័យ (List)
            $listQuery = Db::name('tg_unit')->where('status', '1');
            $searchLogic($listQuery);
            $list = $listQuery
                ->field('id,title')
                ->page($page, $pagesize)
                ->select();

            return json(['list' => $list, 'total' => $total]);
        }
    }

    public function brand()
    {
        if ($this->request->isAjax()) {
            // 1️⃣ ករណីបង្ហាញឈ្មោះក្នុង Form Edit (KeyValue)
            if ($this->request->request("keyValue")) {
                $value = $this->request->request("keyValue");
                $data = Db::name('tg_brand')
                    ->where('id', $value)
                    ->field('id,title')
                    ->select();
                return json(['list' => $data]);
            }

            // 2️⃣ ទទួលយក Pagination និង Search Keyword
            $page = $this->request->request("pageNumber", 1);
            $pagesize = $this->request->request("pageSize", 10);
            $q_word = $this->request->request("q_word/a", []);
            $q_word = array_filter($q_word); // សម្អាតតម្លៃទទេចេញ

            // 3️⃣ កំណត់លក្ខខណ្ឌ Search
            $searchLogic = function ($query) use ($q_word) {
                if (!empty($q_word)) {
                    $query->where(function ($q) use ($q_word) {
                        foreach ($q_word as $value) {
                            $q->whereOr('title', 'like', "%{$value}%");
                        }
                    });
                }
            };

            // 4️⃣ រាប់ចំនួនសរុប (Total)
            $countQuery = Db::name('tg_brand')->where('status', '1');
            $searchLogic($countQuery);
            $total = $countQuery->count();

            // 5️⃣ ទាញទិន្នន័យ (List)
            $listQuery = Db::name('tg_brand')->where('status', '1');
            $searchLogic($listQuery);
            $list = $listQuery
                ->field('id,title')
                ->page($page, $pagesize)
                ->select();

            return json(['list' => $list, 'total' => $total]);
        }
    }





    public function index()
    {
        // សម្អាតទិន្នន័យពី Request
        $this->request->filter(['strip_tags', 'trim']);

        if ($this->request->isAjax()) {
            // ប្រើសម្រាប់ Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            // 1️⃣ ទទួល filter និង op ពី frontend
            $filter = $this->request->get("filter", '');
            $op = $this->request->get("op", '');
            $params = (array)json_decode($filter, true);
            $ops = (array)json_decode($op, true);

            $w = [];

            // 2️⃣ បម្លែង Field ទាំងអស់ឲ្យទៅជា 's.field' ដើម្បីកុំឲ្យជាន់ឈ្មោះគ្នា
            if ($params && $ops) {
                $new_params = [];
                $new_ops = [];

                foreach ($params as $key => $value) {
                    // ដាក់ alias 's.' នៅពីមុខគ្រប់ field (ឧទាហរណ៍៖ status => s.status)
                    $new_params["s.$key"] = $value;
                    $new_ops["s.$key"] = $ops[$key];
                }

                // Update request វិញដើម្បីឲ្យ buildparams ស្គាល់
                $this->request->get(['filter' => json_encode($new_params)]);
                $this->request->get(['op' => json_encode($new_ops)]);
            }

            // បង្កើត parameter សម្រាប់ where, sort, order...
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            // 3️⃣ Query សំខាន់សម្រាប់ទាញ Data (Manual Join)
            $list = $this->model
                ->alias('s') // 👉 ដាក់ alias 's' សម្រាប់តារាង tg_product

                // 🟩 JOIN ទៅតារាងផ្សេងៗ (Category, Brand, Unit)
                // ឈ្មោះតារាងត្រូវប្រាកដថាត្រឹមត្រូវក្នុង Database (ឧ. fa_tg_category)
                ->join('fa_tg_category c', 's.category_id = c.id', 'LEFT')
                ->join('fa_tg_brand b', 's.brand_id = b.id', 'LEFT')
                ->join('fa_tg_unit u', 's.unit_id = u.id', 'LEFT')

                // 🟩 ជ្រើសយក Field និងប្ដូរឈ្មោះឲ្យត្រូវនឹង JS (Nest Object)
                ->field('s.*, 
                        c.title as `category.title`, 
                        b.title as `brand.title`, 
                        u.title as `unit.title`')

                // ដាក់ where ដែលយើងបាន build ខាងលើ (ដែលមាន s.status)
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            // 🟩 ការគណនាចំនួនសរុប
            $total = $this->model
                ->alias('s')
                ->join('fa_tg_category c', 's.category_id = c.id', 'LEFT')
                ->join('fa_tg_brand b', 's.brand_id = b.id', 'LEFT')
                ->join('fa_tg_unit u', 's.unit_id = u.id', 'LEFT')
                ->where($where)
                ->count();

            $rows = collection($list)->toArray();

            $result = [
                "total" => $total,
                "rows" => $rows
            ];

            return json($result);
        }

        return $this->view->fetch();
    }





}
