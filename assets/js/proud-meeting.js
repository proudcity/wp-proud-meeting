(function($, Proud) {
  Proud.behaviors.proud_meeting = {
    attach: function(context, settings) {

      //$('#wr_editor_tabs, .wr-editor-tab-content').hide();
      $('#wpseo_meta').hide();

      // Move Editor into Agenda metafield
      $('#postdivrich').appendTo('#agenda-wrapper');

      // Enable datetimepicker
      $('#form-meeting_datetime-1-datetime').datetimepicker({
        format:'YYYY-MM-DD hh:mm a',
      });



    }
  };
})(jQuery, Proud);