$(function(){

  // フッターを最下部に固定
  let $ftr = $('footer');
  if( window.innerHeight > $ftr.offset().top + $ftr.outerHeight() ){
    $ftr.attr({'style': 'position:fixed; top:' + (window.innerHeight - $ftr.outerHeight()) +'px;' });
  };

  // メッセージ表示
  let $jsShowMsg = $('#js-show-msg');
  let msg = $jsShowMsg.text();
  if(msg.replace(/^[\s　]+|[\s　]+$/g, "").length){
    $jsShowMsg.slideToggle('slow');
    setTimeout(function(){ $jsShowMsg.slideToggle('slow'); }, 5000);
  };

  // 画像ライブプレビュー
  let $dropArea = $('#drop-area');
  let $fileInput = $('#input-img');
  $dropArea.on('dragover', function(e){
    e.stopPropagation();
    e.preventDefault();
    $(this).css('border', '3px #ccc dashed');
  });
  $dropArea.on('dragleave', function(e){
    e.stopPropagation();
    e.preventDefault();
    $(this).css('border', 'none');
  });
  $fileInput.on('change', function(e){
    $dropArea.css('border', 'none');
    let file = this.files[0],
        $img = $(this).siblings('.prev-img'),
        fileReader = new FileReader();

    fileReader.onload = function(event){
      $img.attr('src', event.target.result).show();
    };
    fileReader.readAsDataURL(file);
  });

  // 文字数カウント
  $(".js-input").keyup(function(){
    let form_g = $(this).closest('.form-group');
    let form_l = $(this).val().length;
    form_g.find('#js-count').text( form_l );
  });

  // お気に入り登録・削除（ajax）
  let $like;
  let likePostId;

  $like = $('.js-click-like') || null;
  likePostId = $like.data('postid') || null;
  if(likePostId !== undefined && likePostId !== null){
    $like.on('click',function(){
      let $this = $(this);
      $.ajax({
        type: "POST",
        url: "ajaxLike.php",
        data: {postId : likePostId}
      }).done(function(){
        console.log('Ajax Success');
        $this.toggleClass('active');
      }).fail(function(){
        console.log('Ajax Error.');
      });
    })
  }

});
