<?php
namespace App\Controllers;

use App\Models\DonModel;
use App\Validators\DonValidator;
use App\Database\Database;
use Firebase\JWT\JWT;

class DonControllerontroller
{
    private DonModel $donModel;

    public function __construct()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $this->Model = new DonModel($conn);
    }

}

?>