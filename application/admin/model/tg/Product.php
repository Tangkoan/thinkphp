<?php

namespace app\admin\model\tg;

use think\Model;


class Product extends Model
{

    

    

    // 表名
    protected $name = 'tg_product';
    
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
        return ['1' => __('Status 1'), '0' => __('Status 0')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }



    public function brand()
    {
        return $this->belongsTo('app\admin\model\tg\Brand', 'brand_id')->setEagerlyType(0);
    }


    public function category()
    {
        return $this->belongsTo('app\admin\model\tg\Category', 'category_id')->setEagerlyType(0);
    }


    public function unit()
    {
        return $this->belongsTo('app\admin\model\tg\Unit', 'unit_id')->setEagerlyType(0);
    }


}
