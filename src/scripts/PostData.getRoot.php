<?php

use Drupal\post\Entity\PostData;

$paths = PostData::loadParents(6178);

foreach($paths as $p) {
    echo $p->id() . "\n";
}
/*
$root = PostData::getRoot(6178);
echo $root->id();
*/

