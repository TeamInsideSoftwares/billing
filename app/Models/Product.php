<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $connection = 'admin_mysql';

    protected $table = 'products';

    protected $primaryKey = 'productid';

    public $incrementing = false;

    public $timestamps = false;
}
