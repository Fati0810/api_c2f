<?php
namespace App\Controllers;

use App\Models\DonModel;
use App\Database\Database;

class DonController
{
    private DonModel $donModel;

    public function __construct()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $this->donModel = new DonModel($conn);
    }

  
}
?>
