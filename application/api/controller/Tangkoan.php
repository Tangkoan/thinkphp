<?php


namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Config;
use think\Exception;


/**
 * @ApiSector(Tangkoan)
 * * Created by Kuy Tangkoan
 * * @title Tangkoan
 */
class Tangkoan extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function show()
    {
        try {
            $status = $this->request->post('status');

            $data = Db::name('tangkoan')
                    ->where('status', 1)
                    ->select();

            return $this->success(__("success"), $data);

        } catch(Exception $e){
            $this->error(__("Error"), $e->getMessage());
        }
    }

    public function create() 
    {
        try {
            $title = $this->request->post('title');
            $bran = $this->request->post('bran');

            // Check duplicate
            $exists = Db::name('tangkoan')->where('title', $title)->find();
            if ($exists) {
                return json(['code' => 0, 'msg' => 'This title is already']);
            }

            // Prepare data (id will be added later)
            $data = [
                'title' => $title,
                'bran' => $bran,
                'status' => "1",
                'createtime' => time(),
                'updatetime' => time()
            ];

            // insert to DB
            $id = Db::name('tangkoan')->insertGetId($data);

            // put id at the TOP
            $result = [
                'id' => $id,   // ⭐ ដាក់នៅលើគេ
                'title' => $data['title'],
                'bran' => $data['bran'],
                'status' => $data['status'],
                'createtime' => $data['createtime'],
                'updatetime' => $data['updatetime'],
            ];

            return $this->success("success", $result);

        } catch(Exception $e) {
            return $this->error("error", $e->getMessage());
        }
    }

    public function edit()
    {
        try {
            $id = $this->request->post('id');
            $title = $this->request->post('title');
            $bran = $this->request->post('bran');

            // 1. Validate ID (must be numeric)
            if (!ctype_digit($id)) {
                return json(['code' => 0, 'msg' => 'ID must be integer']);
            }

            // 2. Check if record exists
            $record = Db::name('tangkoan')->where('id', $id)->find();
            if (!$record) {
                return json(['code' => 0, 'msg' => 'Record not found']);
            }

            // Check duplicate
            $exists = Db::name('tangkoan')->where('title', $title)->find();
            if ($exists) {
                return json(['code' => 0, 'msg' => 'This title is already']);
            }

            // 3. Check duplicate title (exclude current row)
            $exists = Db::name('tangkoan')
                ->where('title', $title)
                ->where('id', '<>', $id)
                ->find();

            if ($exists) {
                return json(['code' => 0, 'msg' => 'This title is already']);
            }

            // 4. Data for update
            $data = [
                'title' => $title,
                'bran'  => $bran,
                'updatetime' => time()
            ];

            // 5. Update DB
            Db::name('tangkoan')->where('id', $id)->update($data);

            // 6. Return result with ID on top
            $result = [
                'id' => $id,
                'title' => $data['title'],
                'bran' => $data['bran'],
                'status' => $record['status'], // keep old status
                'createtime' => $record['createtime'],
                'updatetime' => $data['updatetime']
            ];

            return $this->success("success", $result);

        } catch (Exception $e) {
            return $this->error("error", $e->getMessage());
        }
    }

    public function Details()
    {
        try{

            $id = $this->request->post('id');

            // Validate: must be integer
            if (!ctype_digit($id)) {
                return $this->error(__("ID must be an integer"));
            }
            $id = (int)$id;

            $data = DB::name("tangkoan")
                    -> where('id', $id)
                    ->find();

            if (!$data) {
                return $this->error(__("Not Found"));
            }

            return $this->success(__('success'), $data);

        }catch(Exception $e){
            $this->error(__("error"), $e->getMessage());
        }
    }
    
    public function Delete()
    {
        try{

            $id = $this->request->post('id');

            // Validate: must be integer
            if (!ctype_digit($id)) {
                return $this->error(__("ID must be an integer"));
            }
            $id = (int)$id;

            $data = DB::name("tangkoan")
                    -> where('id', $id)
                    ->delete();

            if (!$data) {
                return $this->error(__("Not Found"));
            }

            return $this->success(__('success'), $data);

        }catch(Exception $e){
            $this->error(__("error"), $e->getMessage());
        }
    }

}
