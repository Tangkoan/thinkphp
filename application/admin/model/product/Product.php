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
    

    
    public function getStatusList()
    {
        return ['public' => __('Public'), 'hidden' => __('Hidden')];
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
