<!doctype html>

<?php
    require_once 'model/Post.php';
?>

<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Stuck Overflow - Index</title>
    <base href="<?= $web_root ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Stack overflow for stuck people">
    <meta name="author" content="Gautier Kiss | Guillaume Rigaux">
    <link rel="icon" href="upload/favicon.ico" />
    <link href="css/styles.css" rel="stylesheet" type="text/css"/>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans&display=swap" rel="stylesheet">
    <script src="lib/jquery-3.4.1.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>

    <script>
        let quest_div;
        let quest_json = <?= $quest_json?>;
        let nb_pages = <?= $nb_pages ?>;
        let page_active = 1;
        let sort_active = "newest";

        $(function (){
            $("#search_button").hide();
            $("#lien").hide();
            $(".questions").hide();
            quest_div = $("#js_questions");

            // desactive l'action du formulaire de recherche
            document.getElementById('search').addEventListener('submit', function(evt){
                evt.preventDefault();
            })

            $("#newest_results_active").show();
            $("#active_results").show();
            $("#unanswered_results").show();
            $("#vote_results").show();
            displayQuestions();
        });

        function displayQuestions() {
            quest_div.html("");
            var html = "";
            var i = 1;
            for (let q of quest_json)
            {
                html += "<div class=\"show_questions\">";
                html += "<p><a href=\"post/show/" + q.PostId +"\" class=\"title\">" + q.Title + "</a></p>";
                html += "<p>" + q.Body + "</p>";
                html += "<p class=\"askedby\">Asked " + q.Timestamp + " by <a>" + q.Author + "</a> (" + q.SumVote + " vote(s) " + q.NbAnswer + " answer(s))";
                for (let tag of q.tags) {
                    html += "<a class=\"tagname\" onClick=\"tagInput(this.textContent);\">" + tag.Name + "</a>";
                }
                html += "</p>";
                html += "</div>";
            }
            html += "<div class=\"index_pages\">";
            while (i <= nb_pages)
            {
                if (i == page_active) {
                    html += "<p>" + i++ + "</p>";
                }
                else {
                    html += "<a onClick=\"changePage(this.textContent);\">" + i++ + "</a>";
                }
            }
            if (page_active < nb_pages - 1)
                html += "<a onClick=\"changePage("+ (++page_active) +");\"> > </a>";
            html += "</div>";
            quest_div.html(html);
        }

        function changePage(val) {
            page_active = val;
            if ($("#search_input").val() != "" && $("#search_input").val() != null) {
                var txt = $("#search_input").val();
                if (txt != "" && txt != null) {

                    $.get("post/get_search_service/" + txt +"/"+ val, function(data){
                        quest_json = data;
                        
                        $.get("post/get_search_page_service/" + txt, function(data){
                            nb_pages = data;
                            displayQuestions();
                        }, "json").fail(function(){
                            quest_div.html("<tr><td>Error encountered while retrieving the number of pages!</td></tr>");
                        });

                    }, "json").fail(function(){
                            quest_div.html("<tr><td>Error encountered while retrieving the questions!</td></tr>");
                    });
                }
            }
            else if ($("#questions_tagged_active").html() != "") {

                $.get("post/get_tagged_service/" + tag +"/" + val, function(data){
                    quest_json = data;

                    $.get("post/get_tagged_page_service/" + tag, function(data){
                        nb_pages = data;
                        displayQuestions();
                    }, "json").fail(function(){
                            quest_div.html("<tr><td>Error encountered while retrieving the number of pages!</td></tr>");
                    });

                }, "json").fail(function(){
                        quest_div.html("<tr><td>Error encountered while retrieving the questions!</td></tr>");
                });
            }
            else {
                $.get("post/get_"+ sort_active + "_service/"+val, function(data){
                    quest_json = data;
                    displayQuestions();
                }, "json").fail(function(){
                    quest_div.html("<tr><td>Error encountered while retrieving the questions!</td></tr>");
                });
            }
        }

        function changeSort(val) {
            $("#search_input").val("");
            $("#questions_tagged_active").html("");
            sort_active = val;
            page_active = 1;

            $.get("post/get_" + sort_active + "_service/1", function(data){
                quest_json = data;

                $.get("post/get_" + sort_active + "_page_service/", function(data){
                    nb_pages = data;
                    setHeaders();
                    displayQuestions();
                }, "json").fail(function(){
                        quest_div.html("<tr><td>Error encountered while retrieving the number of pages!</td></tr>");
                });

            }, "json").fail(function(){
                    quest_div.html("<tr><td>Error encountered while retrieving the questions!</td></tr>");
            });
        }

        function setHeaders() {
            // on reset tout
            resetHeaders();

            // on set le active
            $("#" + sort_active + "_results").hide();
            $("#" + sort_active + "_results_active").show();
        }

        function resetHeaders() {
            $("#newest_results_active").hide();
            $("#active_results_active").hide();
            $("#unanswered_results_active").hide();
            $("#vote_results_active").hide();
            $("#questions_tagged_active").hide();
            $("#search_results_active").hide();
            $("#newest_results").show();
            $("#active_results").show();
            $("#unanswered_results").show();
            $("#vote_results").show();
        }

        function tagInput(tag) {
            $("#search_input").val("");
            resetHeaders();
            $("#questions_tagged_active").html("Questions Tagged [" + tag + "]");
            $("#questions_tagged_active").show();
            page_active = 1;

            $.get("post/get_tagged_service/" + tag +"/1", function(data){
                quest_json = data;

                $.get("post/get_tagged_page_service/" + tag, function(data){
                    nb_pages = data;
                    displayQuestions();
                }, "json").fail(function(){
                        quest_div.html("<tr><td>Error encountered while retrieving the number of pages!</td></tr>");
                });

            }, "json").fail(function(){
                    quest_div.html("<tr><td>Error encountered while retrieving the questions!</td></tr>");
            });
        }

        function searchInput() {
            $("#questions_tagged_active").html("");
            var txt = $("#search_input").val();
            if (txt != "" && txt != null) {
                resetHeaders();
                $("#search_results").show();
                $("#search_results_active").show();
                page_active = 1;
                
                $.get("post/get_search_service/" + txt +"/1", function(data){
                    quest_json = data;
                    
                    $.get("post/get_search_page_service/" + txt, function(data){
                        nb_pages = data;
                        displayQuestions();
                    }, "json").fail(function(){
                        quest_div.html("<tr><td>Error encountered while retrieving the number of pages!</td></tr>");
                    });

                }, "json").fail(function(){
                        quest_div.html("<tr><td>Error encountered while retrieving the questions!</td></tr>");
                });
            }
            else {
                changeSort("newest");
            }
        }

    </script>
</head>
<body>
    <header>
        <?php include('menu.php'); ?>
    </header>
    <main>
        <div class ="main">
            <h1>We &lt;3 stuck people</h1>
            <p>Project done by Gautier Kiss && Guillaume Rigaux</p>
        </div>
        <div class = "nav_questions">
            <div id = "lien">
                <?php if ((isset($sort) && (strcmp($sort, "newest") == 0))):?>
                    <p class="active">Newest</p>
                <?php else: ?>
                    <a href ="post/index/newest">Newest</a>
                <?php endif;?>
                <?php if ((isset($sort) && (strcmp($sort, "active") == 0))):?>
                    <p class="active">Active</p>
                <?php else: ?>
                    <a href ="post/index/active">Active</a>
                <?php endif;?>
                <?php if ((isset($sort) && (strcmp($sort, "unanswered") == 0))):?>
                    <p class="active">Unanswered</p>
                <?php else: ?>
                    <a href ="post/index/unanswered">Unanswered</a>
                <?php endif;?>
                <?php if ((isset($sort) && (strcmp($sort, "votes") == 0))):?>
                    <p class="active">Votes</p>
                <?php else: ?>
                    <a href ="post/index/votes">Votes</a>
                <?php endif;?>
                <?php if ((isset($sort) && isset($selected_tag->Name) && (strcmp($sort, "posts") == 0))):?>
                    <p class="active">Questions Tagged [<?=$selected_tag->Name?>]</p>
                <?php endif;?>
            </div>
            <div id="js_links">
                <p id="newest_results_active" hidden>Newest</p>
                <p id="newest_results" onClick="changeSort('newest');" hidden><a>Newest</a></p>
                <p id="active_results_active" hidden>Active</p>
                <p id="active_results" onClick="changeSort('active');" hidden><a>Active</a></p>
                <p id="unanswered_results_active" hidden>Unanswered</p>
                <p id="unanswered_results" onClick="changeSort('unanswered');" hidden><a>Unanswered</a></p>
                <p id="vote_results_active" hidden>Votes</p>
                <p id="vote_results" onClick="changeSort('vote');" hidden><a>Votes</a></p>
                <p id="questions_tagged_active" hidden></p>
                <p id="search_results_active" hidden>Search Results</p>
            </div>
            <!-- DEBUT RECHERCHE -->
            <div id = "form_s">
                <form id ="search" name ="search" action ="post/index/search" method = "post">
                    <input id="search_input" name="search" type="text" placeholder="Search ..." oninput="searchInput();"
                        <?php if (isset($search_value)): ?>
                            value="<?= $search_value ?>"
                        <?php endif; ?>
                    >
                    <button id="search_button" type="submit">&#128269;</button>
                </form>
            </div>
        </div>
            <!-- FIN RECHERCHE -->
        <div class="questions">
            <?php if (isset($questions[0])): ?>
                <?php foreach ($questions as $question):?>
                    <div class = "show_questions">
                        <p><a href ="post/show/<?=$question->PostId?>" class="title"><?= $question->Title?></a></p>
                        <p><?= $question->Body?></p>
                        <p class="askedby">Asked <?=$question->Timestamp ?> by <a href=""><?= $question->Author['FullName']?></a> (<?= $question->SumVote[0]?> vote(s) <?= $question->NbAnswer[0]?> answer(s))
                        <?php foreach($question->tags as $tag): ?>
                            <a href="post/posts/tag/1/<?= $tag->Id ?>" class="tagname"><?=$tag->Name?></a>
                        <?php endforeach; ?></p>
                    </div>
                <?php endforeach; ?>
                <div class="index_pages">
                    <?php if (isset($nb_pages)) :?>
                        <?php if ($active != 1) :?>
                            <?php if (isset($selected_tag)): ?>
                                <a href="post/posts/tag/<?=($active - 1)?>/<?=$selected_tag->Id?>"> < </a>
                            <?php elseif (strcmp($sort, "search") == 0): ?>
                                <a href="post/posts/search/<?=($active - 1)?>/<?=$url?>"> < </a>
                            <?php else :?>
                                <a href="post/posts/<?=$sort?>/<?=($active - 1)?>"> < </a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $nb_pages; $i++) :?>
                            <?php if (isset($selected_tag)): ?>
                                <?php if (isset($active) && $active == $i) :?>
                                    <p><?=$i?></p>
                                <?php else: ?>
                                    <a href="post/posts/tag/<?=$i?>/<?=$selected_tag->Id?>"> <?=$i?> </a>
                                <?php endif; ?>
                            <?php elseif (strcmp($sort, "search") == 0): ?>
                                <?php if (isset($active) && $active == $i) :?>
                                    <p><?=$i?></p>
                                <?php else: ?>
                                    <a href="post/posts/search/<?=$i?>/<?=$url?>"> <?=$i?> </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (isset($active) && $active == $i) :?>
                                    <p><?=$i?></p>
                                <?php else: ?>
                                    <a href="post/posts/<?=$sort?>/<?=$i?>"> <?=$i?></a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($active < (int)$nb_pages) :?>
                            <?php if (isset($selected_tag)): ?>
                                <a href="post/posts/tag/<?=($active + 1)?>/<?=$selected_tag->Id?>"> > </a>
                            <?php elseif (strcmp($sort, "search") == 0): ?>
                                <a href="post/posts/search/<?=($active + 1)?>/<?=$url?>"> > </a>
                            <?php else :?>
                                <a href="post/posts/<?=$sort?>/<?=($active + 1)?>"> > </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif;?>
                </div>
            <?php else: ?>
                <p>No result found</p>
            <?php endif;?>
        </div>

        <div id="js_questions">
        </div>
    </main>
</body>
</html>