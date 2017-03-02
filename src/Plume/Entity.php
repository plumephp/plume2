<?php
/**
 * Created by PhpStorm.
 * User: renshan
 * Date: 16-11-2
 * Time: 上午9:35
 */

namespace Plume;

use Illuminate\Database\Migrations\Migration;


class Entity extends Migration
{
    public function create()
    {
        try {
            $this->up();
        } catch(\Exception $e) {
            $this->down();
            $this->up();
        }
    }
}