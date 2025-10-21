<?php
/*
Plugin Name: Cache Clear for お名前.com
Version: 1.0.1
Description: お名前.comのレンタルサーバーで、記事の投稿時に自動でキャッシュを削除するためのプラグインです。サーバーキャッシュの設定を有効にしたままで、新規の投稿をすぐに確認できるようになります。
Author: GMO Internet, Inc.
Author URI: https://www.onamae.com
*/

function cache_clear_onamae($post_ID){
        $homeurl = home_url();
        $url_parts = explode("/",$homeurl);
        $cwd = getcwd();
        $clear_url = "http://127.0.0.1:9080/clearcache?cwd=$cwd";
        $ch = curl_init($clear_url);
        $mh = curl_multi_init();
        curl_multi_add_handle($mh,$ch);
        $running = NULL;
        curl_multi_exec($mh,$running);
        curl_multi_close($mh);
}

add_action( 'new_to_publish', 'cache_clear_onamae' );
add_action( 'pending_to_publish', 'cache_clear_onamae' );
add_action( 'draft_to_publish', 'cache_clear_onamae' );
add_action( 'auto-draft_to_publish', 'cache_clear_onamae' );
add_action( 'future_to_publish', 'cache_clear_onamae' );
add_action( 'private_to_publish', 'cache_clear_onamae' );
