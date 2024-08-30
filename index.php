<?php
$redirectUrl = $_SERVER['HTTP_REFERER'];
$tmp = explode("?",$redirectUrl);
if(isset($_GET['lang'])){
    if(count($tmp) <= 1){
        $redirectUrl.= "?lang=".$_GET['lang'];
    }else{
        $redirectUrl.= "&lang=".$_GET['lang'];
    }
}
header("Location: ".$redirectUrl);