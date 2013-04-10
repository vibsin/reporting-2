<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


include('clean_up_config.php');


shell_exec("cd ".BASE_PATH_CSV);
shell_exec("rm -rf *.zip *.csv");