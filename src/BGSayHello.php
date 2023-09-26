<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace losthost\telle;

/**
 * Description of BGSayHello
 *
 * @author drweb
 */
class BGSayHello extends AbstractBackgroundProcess {
    
    public function run() {
        error_log("Hello world at ". date_create()->format(\losthost\DB\DB::DATE_FORMAT));
    }

}
