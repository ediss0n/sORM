<?php

include "models.php";

//Initialising table objects
$user = new Users();
$publication = new Publications();
$comment = new Comments();

// Creating new user
$Ed = new usersRecord($user);
$Ed->username = "Ed";
$Ed->Save();

// Creating another user
$Bob = new usersRecord($user);
$Bob->username = "Bob";
$Bob->Save();

// Creating new publication
$article = new publicationsRecord($publication);
$article->title = "Hello there!";
$article->body = "This is my ORM";
$article->users = $Ed;
$article->Save();

// Creating comments for publication
$comm1 = new commentsRecord($comment);
$comm1->publications = $article;
$comm1->users = $Bob;
$comm1->comment = "This is comment";
$comm1->Save();

$comm2 = new commentsRecord($comment);
$comm2->publications = $article;
$comm2->users = $Ed;
$comm2->comment = "This is another comment";
$comm2->Save();


// Lets get all comments on publication
$dataset = $article->getRelated("comments");

foreach ($dataset->data as $obj) {
    print("User: ".$obj->users->username.", Comment: ".$obj->comment.", Date: ".$obj->comm_date->format('Y-m-d H:i:s')."<br/> \n ");
}

?>