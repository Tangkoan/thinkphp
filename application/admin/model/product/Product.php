<?php

namespace app\admin\model\product;

use think\Model;


class Product extends Model
{

    

    

    // 表名
    protected $name = 'products';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'integer';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];
    

    
    // Function នេះសម្រាប់ status មែន តែក៏បន្ថេម Header របស់ List Category អោយយើងដឹងថា មាន All , Public , Draft
    public function getStatusList()
    {   
        return ['1' => __('Public'), '0' => __('Draft')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function category()
    {
        return $this->belongsTo('app\admin\model\contents\Categories', 'category_id')->setEagerlyType(0);
    }



}
