<?php
/**
 * 商品模型
 */
class Product extends BaseModel
{
    protected string $table = 'products';
    protected string $pk = 'id';
    protected bool $timestamps = true;
}
