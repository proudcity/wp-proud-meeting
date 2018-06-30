(function($, Proud) {
  Proud.behaviors.proud_meeting_youtube_bookmarks = {
    attach: function(context, settings) {

      var youtubeInput = '#form-meeting_video-1-video';
      var bookmarkInput = '#form-meeting_video-1-youtube_bookmarks';


      // var tag = document.createElement('script');
      // tag.src = "https://www.youtube.com/iframe_api";
      // var firstScriptTag = document.getElementsByTagName('script')[0];
      // firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

      var player;
      window.onYouTubeIframeAPIReady = function () {
        setVideo();
      }
      function setVideo() {
        var val = $(youtubeInput).val();
        if (!val) {
          $('#youtube-wrapper').hide();
          return;
        }
        var pattern = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        var matches = val.match(pattern);
        var id = matches != null && matches[2] ? matches[2] : val;
        if (player) {
          player.loadVideoById(id);
        }
        else {
          player = new YT.Player('player', {
            height: '315',
            width: '560',
            videoId: id,
            playerVars: { 'autoplay': 0, 'controls': 1 },
          });
        }
        $(youtubeInput).val(id);
        $('#youtube-wrapper').show();
      }
      $(youtubeInput).bind('change', function() {
        setVideo();
      });


      var source = document.getElementById("youtube-list-template").innerHTML;
      var template = Handlebars.compile(source);

      function renderYoutubeList() {
        youtube.sort(function(a, b){return a.seconds - b.seconds});
        var html = template({youtube: youtube});
        var $html = $(html);
        $html.find('button[data-youtube-remove]').bind('click', function(e) {
          e.preventDefault();
          youtube.splice($(this).data('youtube-remove'), 1);
          setYoutube();
          renderYoutubeList();
        });
        $html.find('a[data-youtube-seek]').bind('click', function(e) {
          e.preventDefault();
          player.seekTo($(this).data('youtube-seek'));
        });
        $('#youtube-list').html($html);
      }

      function getYoutube() {
        var val = $(bookmarkInput).val();
        if (!val) {
          return [];
        }
        try {
          return JSON.parse(val);
        }
        catch(error) {
          return [];
        }
      }

      var youtube = getYoutube();
      function setYoutube() {
        $(bookmarkInput).val(JSON.stringify(youtube));
      }
      renderYoutubeList();


      $('#youtube-add').bind('click', function(e) {
        e.preventDefault();
        $('#youtube-new-time').val(Math.round(player.getCurrentTime()));
        $('#youtube-new-label').val('');
        $('#youtube-new').show();
        $('#youtube-add').hide();
        player.pauseVideo();
      });

      $('#youtube-update-time').bind('click', function(e) {
        e.preventDefault();
        $('#youtube-new-time').val(Math.round(player.getCurrentTime()));
        player.pauseVideo();
      });

      $('#youtube-new-submit').bind('click', function(e) {
        e.preventDefault();
        var time = $('#youtube-new-time').val();
        var minutes = Math.floor(time / 60);
        var seconds = time - minutes * 60;
        youtube.push({
          label: $('#youtube-new-label').val(),
          seconds: time,
          time: minutes + ':' + ('0' + seconds).slice(-2)
        });
        setYoutube();
        console.log(youtube);
        renderYoutubeList();
        $('#youtube-new').hide();
        $('#youtube-add').show();
      });

      $('#youtube-new-time').bind('change', function(e) {
        player.seekTo($(this).val());
        player.pauseVideo();
      });


    }
  };
})(jQuery, Proud);