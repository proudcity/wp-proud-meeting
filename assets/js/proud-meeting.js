(function($, Proud) {
  Proud.behaviors.proud_meeting = {
    attach: function(context, settings) {

      //$('#wr_editor_tabs, .wr-editor-tab-content').hide();
      $('#wpseo_meta').hide();

      // Move Editor into Agenda metafield
      $('#postdivrich').appendTo('#agenda-wrapper');

      // Enable datetimepicker
      // @todo: make this work
      $('#form-meeting_datetime-1-datetime').datetimepicker();

      // Youtube interaction
      var player;
      function onYouTubeIframeAPIReady() {
        player = new YT.Player('video-placeholder', {
          width: 600,
          height: 400,
          videoId: 'Xa0Q0J5tOP0',
          playerVars: {
            color: 'white',
            playlist: 'taJ60kskkns,FG0fTKAqZ5g'
          },
          events: {
            onReady: initialize
          }
        });
      }


    }
  };
})(jQuery, Proud);