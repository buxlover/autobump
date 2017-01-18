<?php
date_default_timezone_set('UTC');
require_once "config.php";
require_once "data.php";
require_once "simple_html_dom.php";


function login(&$WebClient){
    global $bitcointalk;
    global $data;
    if($data==NULL){
        loadAllThreads();
    }
    $dataToPost=    "user=". $data->settings->username .
                    "&passwrd=". base64_decode($data->settings->password) .
                    "&cookieLength=". $data->settings->cookieLength .
                    "&hash_passwrd=";

    curl_setopt($WebClient, CURLOPT_HEADER, true);
    curl_setopt($WebClient, CURLOPT_NOBODY, false);
    curl_setopt($WebClient, CURLOPT_URL, $bitcointalk["loginUrl"]);
    curl_setopt($WebClient, CURLOPT_SSL_VERIFYHOST, 2);

    curl_setopt($WebClient, CURLOPT_COOKIEJAR, $bitcointalk["cookieFile"]);
    //set the cookie the site has for certain features, this is optional
    curl_setopt($WebClient, CURLOPT_COOKIE, "cookiename=0");
    curl_setopt($WebClient, CURLOPT_USERAGENT, $bitcointalk["UserAgent"]);
    curl_setopt($WebClient, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($ch, CURLOPT_REFERER, $_SERVER['REQUEST_URI']);
    curl_setopt($WebClient, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($WebClient, CURLOPT_FOLLOWLOCATION, true);

    curl_setopt($WebClient, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($WebClient, CURLOPT_POST, 1);
    curl_setopt($WebClient, CURLOPT_POSTFIELDS, $dataToPost);
    curl_setopt($WebClient, CURLOPT_HEADERFUNCTION, 'readLoginheader');
    $html=curl_exec($WebClient);

    if($html===false){
        return false;
    }else{

        if(!in_array("Refresh",$bitcointalk["afterLoginHeaders"])){
            return false;
        }
        $startAt=strpos($html,"URL=");
        $urlToGo=urldecode(substr($html,$startAt+4));
        curl_setopt($WebClient,CURLOPT_HTTPHEADER,$bitcointalk["afterLoginHeaders"]);
        $actualHtml = curl_exec($WebClient);
        if($actualHtml===false)
            return false;
        $dom=str_get_html($actualHtml);
        $topLinks=$dom->find("td.maintab_back a");
        foreach($topLinks as $topLink){
            if(strtolower($topLink->plaintext)=="logout"){
                $bitcointalk["logout"]=$topLink->href;
                break;
            }
        }
    }

    $bitcointalk["isLoggedIn"]=true;
    return true;

}

function logout(&$webCient){
    global $bitcointalk;
    if(!isset($bitcointalk["logout"]) ||$bitcointalk["logout"]=="" ){
        return false;
    }
    curl_setopt($webClient,CURLOPT_URL,$bitcointalk["logout"]);
    $html=curl_exec($webClient);
    return $html!==false;
}

function getThread(&$webClient,$threadIndex){
    global $data;

    $thread=$data->threads[$threadIndex];
    curl_setopt($webClient, CURLOPT_HEADER, false);
    curl_setopt($webClient,CURLOPT_URL,$thread->url);
    $html=curl_exec($webClient);
    return $html;
}

function grabPage(&$webClient,$url){
    curl_setopt($webClient, CURLOPT_HEADER, false);
    curl_setopt($webClient,CURLOPT_URL,$url);
    $html=curl_exec($webClient);
    return $html;
}

function getThreadPage(&$webClient,$url){
    $html=grabPage($webClient,$url);
    if($html===false){
        return false;
    }
    return getPosts($html,$url);
}

function getPosts($html,$url,$debug=false){
    $posts=array();
    $dom=str_get_html($html);
    $replies=$dom->find("#quickModForm table tbody tr");
    $toplinks=$dom->find(".mirrortab_back a");
    $threadNav=array();
    foreach($toplinks as $toplink){
        $threadNav[$toplink->plaintext]=$toplink->href;
    }

    $paginationContainer=$dom->find("#quickModForm .middletext");
    $paginationContainer=$paginationContainer[count($paginationContainer)-1];
    $allPages=array();
    $allPagesContainer=$paginationContainer->find("a.navPages");
    foreach($allPagesContainer as $pageContainer){
        if($pageContainer->plaintext!='&#187;' && $pageContainer->plaintext!='&#171;'){
            $allPages[$pageContainer->plaintext]=$pageContainer->href;
        }

    }
    ksort($allPages);

    $boldPage=$paginationContainer->find("b");
    $currentPage=trim($boldPage[0]->plaintext);

    if($currentPage=="..."){
        $currentPage=trim($boldPage[count($boldPage)-1]->plaintext);
    }
    $page_numbers=array_keys($allPages);
    $pagination=array(
        "currentPage"=>intval($currentPage),
        "isLastPage"=>intval($currentPage)>end($page_numbers),
        "allPages"=>$allPages
    );

    foreach($replies as $reply){
        $className=trim($reply->getAttribute("class"));
        //Get  Poster Info
        if(empty($className)){
            continue;
        }
        $poster_info=$reply->find(".poster_info");
        if(count($poster_info)==0){
            continue;
        }
        $poster_info=$poster_info[0];
        $user=explode("<br />",trim($poster_info->find("div.smalltext")[0]->innertext))[0];
        $postTime=str_replace("Today",date("Y-m-d"),trim($reply->find(".td_headerandpost div.smalltext")[0]->plaintext));
        $postTime=str_replace("at","",$postTime);
        $post=array(
            "by"=>trim($poster_info->find("b")[0]->plaintext),
            "at"=>strtotime($postTime),
            "membership"=>$user,
            "buttons"=>array(),
            "title"=>trim($reply->find(".td_headerandpost div.subject")[0]->plaintext),
            "content"=>trim($reply->find(".post")[0]->plaintext)
        );
        $buttons=$reply->find(".td_buttons a");

        foreach($buttons as $button){
            $imgs=$button->find("img");
            $text="permalink";
            if(count($imgs)>0){
                switch($imgs[0]->src){
                    case "/Themes/custom1/images/frostee/frostee_quote.png":
                    case "https://bitcointalk.org/Themes/custom1/images/frostee/frostee_quote.png":
                        $text="quote";
                        break;
                    case "/Themes/custom1/images/frostee/frostee_edit.png":
                    case "https://bitcointalk.org/Themes/custom1/images/frostee/frostee_edit.png":
                        $text="edit";
                        break;
                    case "/Themes/custom1/images/frostee/frostee_delete.png":
                    case "https://bitcointalk.org/Themes/custom1/images/frostee/frostee_delete.png":
                        $text="delete";
                        break;
                    default:
                        $text=$imgs[0]->src;
                }
            }
            $post["buttons"][$text]=$button->href;

        }
        if($post["content"]!=$post["by"]){
            $posts[]=(object)$post;
        }
    }
    $page=(object)array(
        "url"=>$url,
        "options"=>$threadNav,
        "pagination"=>(object)$pagination,
        "posts"=>$posts
    );
    if($debug!=false){
        var_dump($page);
        echo $html;
    }
    return $page;
}

function bumpIt(&$webClient,$replyURL){
    global $data;
    global $bitcointalk;

    echo "Putting Bump<br />";
    curl_setopt($webClient, CURLOPT_HEADER, false);
    curl_setopt($webClient,CURLOPT_URL,$replyURL);
    $html=curl_exec($webClient);
    if($html===false){
        return false;
    }
    $dom=str_get_html($html);
    $postInput=$dom->find("#postmodify input");
    $postSelect=$dom->find("#postmodify select");
    $form=$dom->find("#postmodify")[0];
    $formSubmitURL=$form->getAttribute("action");

    $postData=array();
    $postData=array(
        "topic"=>$form->find('input[name="topic"]')[0]->getAttribute("value"),
        "subject"=>$form->find('input[name="subject"]')[0]->getAttribute("value"),
        "icon"=>"xx",
        "message"=>$data->settings->msgToPost,
        "notify"=>"1",
        "do_watch"=>"0",
        "do_watch"=>"1",
        "goback"=>"1",
        "post"=>"Post",
        "num_replies"=>$form->find('input[name="num_replies"]')[0]->getAttribute("value"),
        "additional_options"=>$form->find('input[name="additional_options"]')[0]->getAttribute("value"),
        "sc"=>$form->find('input[name="sc"]')[0]->getAttribute("value"),
        "seqnum"=>$form->find('input[name="seqnum"]')[0]->getAttribute("value")
    );
    curl_setopt($webClient,CURLOPT_URL,$formSubmitURL);
    curl_setopt($webClient, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($webClient, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($webClient, CURLOPT_POST, 1);
    curl_setopt($webClient,CURLOPT_POSTFIELDS,$postData);
    $url=curl_getinfo($webClient,CURLINFO_EFFECTIVE_URL);
    $htmlAfterSubmit=curl_exec($webClient);

    if(!isError($htmlAfterSubmit)){
        $posts=getPosts($htmlAfterSubmit,$url,true);
        if($posts===false){
            return false;
        }
        foreach($posts->posts as $post){
            if($post->by==$data->settings->username && $post->content=$data->settings->msgToPost){
                return $post;
            }
        }
    }
    return false;
}

function deleteBump(&$webClient,$bumpURL){
    global $data;
    global $bitcointalk;
    //Delete Bump from the Thread

    $threadPage=getThreadPage($webClient,$bumpURL);
    if($threadPage===false){
        return false;
    }
    $deleteUrl="";

    foreach($threadPage->posts as $post){
        if($post->by == $data->settings->username && $post->content==$data->settings->msgToPost){
            $deleteUrl=$post->buttons["delete"];
            break;
        }
    }
    if($deleteUrl==""){
        return false;
    }

    curl_setopt($webClient, CURLOPT_HEADER, false);
    curl_setopt($webClient,CURLOPT_URL,$deleteUrl);
    $html=curl_exec($webClient);
    if($html===false){
        return false;
    }
    return true;
}

function readLoginheader($opts,$header_line){
    global $bitcointalk;
    if (strpos($header_line, ":") === false) {
        return strlen($header_line);
    }
    list($key, $value) = explode(":", trim($header_line), 2);
    $bitcointalk["afterLoginHeaders"][trim($key)] = trim($value);
    return strlen($header_line);
}

function reponseHeaders($opts,$header_line,&$header_array){
    if (strpos($header_line, ":") === false) {
        return strlen($header_line);
    }
    list($key, $value) = explode(":", trim($header_line), 2);
    $header_array[trim($key)] = trim($value);
    return strlen($header_line);
}

function prepareWebClient(&$WebClient){
    global $bitcointalk;
    curl_setopt($WebClient, CURLOPT_HEADER, true);
    curl_setopt($WebClient, CURLOPT_NOBODY, false);
    curl_setopt($WebClient, CURLOPT_SSL_VERIFYHOST, 2);

    curl_setopt($WebClient, CURLOPT_COOKIEJAR, $bitcointalk["cookieFile"]);
    curl_setopt($WebClient, CURLOPT_COOKIE, "cookiename=0");
    curl_setopt($WebClient, CURLOPT_USERAGENT, $bitcointalk["UserAgent"]);
    curl_setopt($WebClient, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($WebClient, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($WebClient, CURLOPT_FOLLOWLOCATION, true);

}

function getLastPost($page){
    $lastPost=NULL;
    foreach($page->posts as $post){

        if($lastPost==NULL){
            $lastPost=$post;
            continue;
        }
        if($post->at > $lastPost->at){
            $lastPost=$post;
        }
    }
    return $lastPost;
}

function isOlderThan24Hour($time){
    $difference=time() - $time;
    return round($difference/(60*60),2)>24.00;
}

function getRecentPost(&$webClient,$url){
    $postsFirstPage=getThreadPage($webClient,$url);
    $lastPost=getLastPost($postsFirstPage);
    if($postsFirstPage->pagination->isLastPage===false){
        $postsLastPage=getThreadPage($webClient,end($postsFirstPage->pagination->allPages));
        $lastPost=getLastPost($postsLastPage);
    }
    $last=count($postsFirstPage->posts)-1;
    return $lastPost;
}

function isError($html){
    $error_string="an error has occurred!";
    $dom=str_get_html($html);
    $title=$dom->find("title");
    $body_error=$dom->find("tr.titlebg td");
    $body_error_found=false;
    foreach($body_error as $body_error_single){
        if(strtolower($body_error_single->plaintext)==$error_string){
            $body_error_found=true;
            break;
        }
    }
    return ((
        isset($title->plaintext) &&
        strtolower($title->plaintext)==$error_string ) ||
        $body_error_found
    );
}
?>
