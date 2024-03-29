<?php
include("../../../inc/loader.php");

if(parse_url(@$_SERVER["HTTP_REFERER"])["path"] != "/admin/posts/list"){
    http_response_code(400);
    exit;
}

if (!$auth->isLogged()) {
    http_response_code(403);
    exit;
}

$userInfo = $auth->getCurrentUser();
if(!is_authorized($userInfo["authority"], array("Admin", "Moderator"))){
    http_response_code(403);
    exit;
}

//IF POST
if($_POST) {
    //DO SQL JOBS
    $return = array(
        "error" => true,
        "message" => ""
    );

    if( empty(post("post_id")) || empty(post("answer_text"))){
        http_response_code(400);
        exit;
    }


    try {

        $sql->beginTransaction();

        $query = $sql->prepare("UPDATE `posts` SET `answer` = :answer");
        $query->execute(array(
            "answer" => trim(post("answer_text"))
        ));



        //NO ERROR HANDLED, SUCCESFULL
        $sql->commit();
        $return = array(
            "error" => false,
            "message" => $auth->__lang("post_edit_success")
        );
        return_error($return);


    } catch (PDOException $e) {
        $return["message"] = $e->errorInfo[2];
        $sql->rollBack();
        return_error($return);
    }

    exit;
}

/*
     * EDIT FORM STARTS HERE
     */

if( empty(get("post_id")) ){
    http_response_code(400);
    exit;
}


try{

    $query = $sql->prepare("SELECT `post_id`, `question`, `answer`, `user`.`full_name` AS `user_name` , `author`.`full_name` AS `author_name`
                                     FROM `posts`
                                     LEFT JOIN `phpauth_users` AS `user` ON `posts`.user_id = `user`.id 
                                     LEFT JOIN `phpauth_users` AS `author` ON `posts`.author_id = `author`.id 
                                     WHERE `posts`.`post_id` = :post_id LIMIT 1");
    $query->execute(array(
        "post_id" => get("post_id")
    ));
    $content = $query->fetch(PDO::FETCH_ASSOC)
    ?>


    <div class="container-fluid" style="padding:0;">
        <div class="row">

            <div class="col-md-12" style="padding:0;">
                <div class="bgc-white bd bdrs-3 p-20 mB-20">
                    <h4 class="c-grey-900 mT-10 mB-30">Edit Post</h4>

                    <form id="post_answer_form" action="/admin/ajax/post-answer" method="POST"  class="needs-validation" autocomplete="off" novalidate>

                        <fieldset>


                            <div class="form-group row">

                                <label for="" class="col-sm-2 col-form-label"><strong>User Name </strong></label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" style="text-transform: "  value="<?=$content["user_name"]?>" disabled>
                                </div>
                            </div>

                            <div class="form-group row">

                                <label for="" class="col-sm-2 col-form-label"><strong>Question </strong></label>
                                <div class="col-sm-6">
                                    <textarea type="text" class="form-control"  style="min-height: 100px;" disabled><?=trim($content["question"])?></textarea>
                                </div>
                            </div>

                            <div class="form-group row">

                                <label for="" class="col-sm-2 col-form-label"><strong>Author Name </strong></label>
                                <div class="col-sm-4">
                                    <input type="text" class="form-control" style="text-transform: "  value="<?=$content["author_name"]?>" disabled>
                                </div>
                            </div>

                            <div class="form-group row">

                                <label for="" class="col-sm-2 col-form-label"><strong>Answer <span class="text-danger">*</span></strong></label>
                                <div class="col-sm-6">
                                    <textarea type="text" name="answer_text" class="form-control"  style="min-height: 100px;" required><?=trim($content["answer"])?></textarea>
                                <div class="invalid-feedback">Üşenme, cevap yaz!</div>
                                </div>
                            </div>

                            <hr>
                            <div class="form-group row">
                                <div class="col-sm-4 float-right">
                                    <div class="btn alert-danger" onclick="delete_post(<?=$content["post_id"]?>)" >Delete Post</div>
                                </div>
                            </div>

                            <input type="hidden" name="post_id" value="<?=$content["post_id"]?>">

                            <hr>
                            <div class="alert message"></div>
                            <button type="submit" class="btn btn-primary " data-loading-text="<i class='fa fa-circle-o-notch fa-spin'></i> Processing..." >Submit</button>

                        </fieldset>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        function delete_post(id){
            var a = confirm("Are you sure you want to delete this user and all its information?");
            if(a){
                $.ajax({
                    type: "POST",
                    url: "/admin/ajax/post-delete",
                    data: {"post_id":id},
                    error:function () {
                        alert("An Error Occured.");
                    },

                    success: function (data) {
                        alert(data.message);
                        if(!data.error){
                            location.reload();
                        }
                    }
                });
            }
        }
    </script>

    <script src="/admin/js/form-validator.js"></script>
    <script>
        form_validate({
            do_before: function(){
                $("#password").val("");
            },
            do_error: function(){},
            do_success: function(){},
            error_true: function(data){
                $(".alert.message").removeClass("alert-success").addClass("alert-danger").html(data.message).slideDown();
            },
            error_false: function (data) {
                $(".alert.message").removeClass("alert-danger").addClass("alert-success").html(data.message+"<br><a href='/posts/list'>Soruları Görüntüle</a>").slideDown();
            }
        });
    </script>

    <?php

}catch (PDOException $e){
    echo $e->errorInfo[2];
    http_response_code(503);
    exit;
}
?>