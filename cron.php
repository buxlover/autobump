<?php

require_once "functions.php";

// Initializing cURL
$webClient=curl_init();
prepareWebClient($webClient);
loadAllThreads();
foreach($data->threads as $thread){

    if(!isset($thread->lastActivityAt) || $thread->lastActivityAt==""){
        $lastPost=getRecentPost($webClient,$thread->url);
        $thread->lastActivityAt=$lastPost->at;
    }

    if(isOlderThan24Hour($thread->lastActivityAt)){
        $lastPost=getRecentPost($webClient,$thread->url);

        if(isOlderThan24Hour($lastPost->at)){
            login($webClient);
            $threadPage=getThreadPage($webClient,$thread->url);

            if(isset($threadPage->options["Reply"])){

                if(isset($thread->lastBumpURL) && $thread->lastBumpURL!=""){
                    deleteBump($webClient,$thread->lastBumpURL);
                }

                $bump=bumpIt($webClient,$threadPage->options["Reply"]);
                if($bump!==false){
                    $thread->lastBumpURL=$bump->buttons["permalink"];
                    $thread->lastActivityAt=time();
                }
                logout($webClient);
            }
        }else{
            $thread->lastActivityAt=$lastPost->at;
        }
    }

}
saveData();

__END__:

?>
