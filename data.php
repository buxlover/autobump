<?php

require_once "config.php";

function loadAllThreads(){
    global $bitcointalk;
    global $data;
    $raw=file_get_contents("data.json");
    $json=json_decode($raw);
//    echo "<pre>";
//    var_dump($json);
//    echo "</pre>";
    $data=(object)array(
        "file"=>"data.json",
        "settings"=>(object)$json->settings,
        "threads"=>(array)$json->threads
    );
}

function saveData(){
    global $data;
    global $bitcointalk;
    $raw=json_encode($data);
    file_put_contents("data.json",$raw);
}

//loadAllThreads();
//var_dump($data);
//echo base64_decode($data->settings->password);
?>
