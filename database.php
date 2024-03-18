<?php

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;

if (isset($_GET['database_install'])) {
    $model = new Database();
    $model->database_install();
    exit ("База данных установлена.");
}

class Database
{

    public Manager $db;

    public function __construct()
    {
        $this->db = new Manager();

        $this->db->addConnection([
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'test',
            'username' => 'root',
            'password' => 'q1w2e3!',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ]);

        $this->db->setAsGlobal();
    }

    public function database_install()
    {
        $this->db::schema()->dropIfExists("users");

        return $this->db::schema()->create('users', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('cid');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
        });
    }

    public function addUser(string $cid, string $first_name, string $last_name)
    {

        $this->db::table("users")->updateOrInsert(
            [
                "cid" => $cid,
            ],
            [
                "first_name" => $first_name,
                "last_name" => $last_name,
            ]
        );
    }

    public function addPhone($cid, $phone)
    {
        $this->db::table("users")->updateOrInsert(
            [
                "cid" => $cid,
            ],
            [
                "phone" => $phone
            ]
        );
    }
}



