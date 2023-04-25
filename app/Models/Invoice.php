<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Invoice extends Model {

    protected $connection = 'mongodb';
    protected $collection = 'invoices';

    protected $fillable = [
        'user', 'sum', 'currency', 'id', 'confirmations', 'status', 'data', 'type', 'aggregator', 'internal_id', 'redirected', 'usd_converted'
    ];

}
