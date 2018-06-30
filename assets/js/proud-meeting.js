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


    }
  };
})(jQuery, Proud);