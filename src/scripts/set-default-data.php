<?php
use Drupal\library\Library;
use Drupal\post\Entity\PostConfig;
use Drupal\post\Entity\PostData;
use Drupal\post\Post;


Post::emptyData();
PostConfig::create()->set('name', 'discussion')->save();
PostConfig::create()->set('name', 'job')->save();


for( $i=0; $i<999; $i++ ) {
    $p = [
        'post_config_name' => 'discussion',
        'username' => 'admin',
        'title' => "이것은 자유게시판의 제목입니다. $i",
        'content' => "<h1>이것은 내용!</h1>그럼",
    ];
    PostData::insert($p);
}


for( $i=0; $i<999; $i++ ) {
    $p = [
        'post_config_name' => 'job',
        'username' => 'firefox',
        'title' => "이것은 구인 구직 게시판의 제목입니다. $i",
        'content' => "<h1>이것은 내용!</h1>그럼",
    ];
    PostData::insert($p);
}
