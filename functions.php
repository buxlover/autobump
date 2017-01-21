<?php

# Including required files to process
{
    date_default_timezone_set('UTC');
    require_once "config.php";
    require_once "data.php";
    require_once "simple_html_dom.php";
}

/**
# @summary Login to BTCTalk account with the given credentials. Make sure that you have entered correct Username and Password in "data.json" file. Param:WebClient is passed by reference to keep active session throughout the process.
# @param WebClient(Object[WebClient]): Passed by reference to process all Browser oriented stuffs.
# @return BOOLEAN(TRUE|FALSE) True if login success, false otherwise
*/
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

/**
# @summary Logout from the provided BTCTalk account which had been logged out.
# @param WebClient(Object[WebClient]): Passed by reference to process all Browser oriented stuffs.
# @return BOOLEAN. True if successfully logged out, false otherwise.
*/
function logout(&$webCient){
    global $bitcointalk;
    if(!isset($bitcointalk["logout"]) ||$bitcointalk["logout"]=="" ){
        return false;
    }
    curl_setopt($webClient,CURLOPT_URL,$bitcointalk["logout"]);
    $html=curl_exec($webClient);
    return $html!==false;
}

/**
# @summary Get HTML output for the given URL.
# @param WebClient(Object[WebClient]): Passed by reference to process all Browser oriented stuffs.
# @param $url String.
# @return HTML String if success. false otherwise.
*/
function grabPage(&$webClient,$url){
    curl_setopt($webClient, CURLOPT_HEADER, false);
    curl_setopt($webClient,CURLOPT_URL,$url);
    $html=curl_exec($webClient);
    return $html;
}

/**
# @summary Get HTML page of the thread for the given Index.
# @param WebClient(Object[WebClient]): Passed by reference to process all Browser oriented stuffs.
# @param ThreadIndex. Index of the thread to extract from the Database. Here, it's data.json.
# @return Array of Posts. HTML output of the Thread mentioned in Database. Typically a HTML web page.
*/
function getThreadPage(&$webClient,$url){
    $html=grabPage($webClient,$url);
    if($html===false){
        return false;
    }
    return getPosts($html,$url);
}

/**
# @summary Get array of Posts and related information like Pagination and options of thread for the given HTML string.
# @param HTML String. Html output of the thread's page.
# @param URI String. URL/URI of the Thread page that have been passed.
# @param Debug BOOLEAN (Optional). If set to TRUE, result of the operation will be dumped on screen. Defaultly false.
# @return ThreadPage Object. Object with Thread page's informmation along with all posts present in the page.
*/
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

/**
# @summary BUMP the thread.
# @param WebClient(Object[WebClient]): Passed by reference to process all Browser oriented stuffs.
# @param ReplyUrl URI. URL/URI of the Reply page of the given thread.
# @return Post Object. Object containing detailed information of the posted BUMP. False otherwise
*/
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
        "message"=>getMessageToPost(),
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

/**
# @summary Delete the given Bump URL. A Bummp is nothing but a Reply to a thread in general.
# @param WebClient(Object[WebClient]): Passed by reference to process all Browser oriented stuffs.
# @param Bump's URI. URL/URI of the Bump that had to be deleted.
# @return BOOLEAN. True if the given BUMP successfully deleted. False otherwise
*/
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

/**
# @summary Save the Headers of the HTTP response while processing Login operation.
# @param Options String.
# @param Header Line String.
# @return Header size INT. Size of the header line from the HTTP response gotten.
*/
function readLoginheader($opts,$header_line){
    global $bitcointalk;
    if (strpos($header_line, ":") === false) {
        return strlen($header_line);
    }
    list($key, $value) = explode(":", trim($header_line), 2);
    $bitcointalk["afterLoginHeaders"][trim($key)] = trim($value);
    return strlen($header_line);
}

/**
# @summary Set the Header value to be sent back to server.
# @param Option String.
# @param Header Value String.
# @param Header Array(By Reference)/
# @return Header Size.
*/
function reponseHeaders($opts,$header_line,&$header_array){
    if (strpos($header_line, ":") === false) {
        return strlen($header_line);
    }
    list($key, $value) = explode(":", trim($header_line), 2);
    $header_array[trim($key)] = trim($value);
    return strlen($header_line);
}

/**
# @summary Preparing WebClient for Browser oriennted operations with all necessary options.
# @param WebClient(Object[WebClient]): Passed by reference to process all Browser oriented stuffs.
*/
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

/**
# @summary Get the recent post from the give posts.
# @param Page Array of Posts.
# @return Post Object.
*/
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

/**
# @summary Check whether given time is older than 24 hours or not.
# @param Time TIMESTAMP.
# @return True is the given TimeStamp is older than 24 hours. False  otherwise.
*/
function isOlderThan24Hour($time){
    $difference=time() - $time;
    return round($difference/(60*60),2)>24.00;
}

/**
# @summary Get the recent post for the given Thread. Typically the most recent post comparing all pages.
# @param WebClient(Object[WebClient]): Passed by reference to process all Browser oriented stuffs.
# @param Thread's URL URI.
# @return Post Object.
*/
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

/**
# @summary Check whether there is any error sent from the Server against its HTML output.
# @param HTML output STRING.
# @return True if there is any error. False otherwise
*/
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

/**
# @summary Get the Random message to post as reply. Introduced in version 0.2, you can post any custom message instead of classic "BUMP" message.
# @return Message STRING.
*/
function getMessageToPost(){
    global $data;

    if($data==NULL){
        loadAllThreads();
    }
    $is_file=false;
    $texts=array();

    # Check whether Folder is enabled, if yes, read files from the
    # given folder.
    if(isset($data->messages->isFolder) && $data->messages->isFolder && file_exists($data->messages->folder)){
        $texts=array_diff(
            scandir($data->messages->folder),
            array(".","..")
        );
        $is_file=true;
    }
    if(count($texts)==0 && isset($data->messages->texts) && count($data->messages->texts)>0){
        # if there is no folder exists in the path provided in settings
        # or isFolder is "false", check for "texts" property for text
        # contents. It will be an array of strings
        $texts=$data->messages->texts;
        $is_file=false;
    }

    if(count($texts)==0){
        # if no options said above is mentioned.
        # Either, user is not upgraded settings file
        # default message as per older settings.
        $texts=array($data->settings->msgToPost);
        $is_file=false;
    }
    if(count($texts)==1){
        return $texts[0];
    }

    # Re-arrange the indices of the items
    sort($texts);

    # Maximumm number to get random index
    $max=count($texts)-1;

    $text=$texts[rand(0,$max)];

    if($is_file){
        $path=$data->messages->folder .
            (
                substr(
                    $data->messages->folder,
                    strlen($data->messages->folder)-2,
                    1
                )==DIRECTORY_SEPARATOR?
                "":
                DIRECTORY_SEPARATOR
            ) .
            $text;
    }

    return $is_file?file_get_contents($path):$text;
}

/**
# @summary Get Activity of the logged in User.
# @param WebClient(Object[WebClient]): Passed by reference to process all Browser oriented stuffs.
# @return Activity INT. Acitivity of the user who logged in.
*/
function getUserActivity(&$webClient){
    $uri="https://bitcointalk.org/index.php?action=profile";
    $html=grabPage($webClient,$uri);
    if($html===false){
        return false;
    }
    $dom=str_get_html($html);
    $tdList=$dom->find(".windowbg tbody tr td");
    return intval($tdList[5]->plaintext);
}

/**
# @summary Get the Minimum required interval between each posts for the logged in user in seconds.
# @param  Activity INT. Activity of the User.
# @return Interval in Seconds INT.
*/
function minPostInterval($activity){
    $time = 360; // seconds
    if($activity >= 15){
        $time = intval(90 - $activity);
    }
    if($activity >= 60){
        $time = intval(34.7586 - (0.0793103 * $activity));
    }
    if($activity >= 100){
        $time = max(intval(14 - $activity/50),4);
    }
    return $time;
}


############################################################################################################################################
#    Place write the Test Code
############################################################################################################################################
$webClient=curl_init();
prepareWebClient($webClient);
login($webClient)===false;
$activity=getUserActivity($webClient);
echo minPostInterval($activity);
logout($webClient);


?>
