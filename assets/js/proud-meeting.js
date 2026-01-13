(function ($, Proud) {
  Proud.behaviors.proud_meeting = {
    attach: function (context, settings) {

      $('#wpseo_meta').hide();
      $('#postdivrich').appendTo('#agenda-wrapper');

      $('#form-meeting_datetime-1-datetime').datetimepicker({
        format: 'YYYY-MM-DD hh:mm a',
      });

      // ---- Helpers ----
      function debounce(fn, wait) {
        var t;
        return function () {
          var ctx = this, args = arguments;
          clearTimeout(t);
          t = setTimeout(function () { fn.apply(ctx, args); }, wait);
        };
      }

      function getPostId() {
        // WP edit screen usually has #post_ID
        var v = $('#post_ID').val();
        return v ? parseInt(v, 10) : 0;
      }

      /**
       * Watch a metabox container for changes and optionally send AJAX.
       *
       * options = {
       *   postId: number,
       *   nonce: string,         // required for AJAX security
       *   action: string,        // WP ajax action name
       *   metaKey: string,       // which meta to update
       *   debounceMs: number,    // textarea debounce
       *   onChange: function(info) {},
       *
       */
      function watchMetaBox(containerSelector, options) {
        // Try within context first, then fall back to document
        var $container = $(context).find(containerSelector);
        if (!$container.length) {
          $container = $(containerSelector);
        }
        if (!$container.length) return;

        var postId = getPostId();

        var opts = $.extend({
          nonce: '',
          action: 'proud_track_metabox_change',
          metaKey: '_meeting_modified',
          debounceMs: 3000,
          onChange: function () { }
        }, options || {});

        // making sure we have a unique ns var for each metabox
        var ns = '.proudWatch' + containerSelector.replace(/[^a-z0-9]/gi, '_');

        function sendAjax(info) {
          // Even if nonce missing, still run onChange/class updates
          if (!postId || !opts.nonce || !window.ajaxurl) return;

          $.post(window.ajaxurl, {
            action: opts.action,
            nonce: opts.nonce,
            post_id: postId,
            meta_key: opts.metaKey,
            container: containerSelector,
            change_type: info.type,
            event: info.event
          })
            .done(function () {
              $container.addClass('ajax-saved').removeClass('ajax-failed');
            })
            .fail(function () {
              $container.addClass('ajax-failed');
            });
        }

        var sendAjaxDebounced = debounce(sendAjax, opts.debounceMs);

        function markChanged(info) {
          opts.onChange(info); // this is where you add has-changes
        }

        // ---- 1) Textarea changes (Text mode / fallback) ----
        $container
          .off('input' + ns + ' change' + ns, '.wp-editor-area')
          .on('input' + ns + ' change' + ns, '.wp-editor-area', function (e) {
            var info = { type: 'editor', event: e.type, target: this, container: $container[0] };
            markChanged(info);

            if (e.type === 'input') sendAjaxDebounced(info);
            else sendAjax(info);
          });

        // ---- 2) Upload/remove clicks ----
        $container
          .off('click' + ns, '.upload_file_button, .remove_file_button')
          .on('click' + ns, '.upload_file_button, .remove_file_button', function () {
            var info = {
              type: $(this).hasClass('upload_file_button') ? 'upload' : 'remove',
              event: 'click',
              target: this,
              container: $container[0]
            };
            markChanged(info);
            sendAjax(info);
          });


        // ---- 3) TinyMCE Visual editor typing (ALL editors in container) ----
        function bindTinyMceForTextarea($ta) {
          if (!$ta || !$ta.length) return;

          var editorId = $ta.attr('id');
          if (!editorId || !window.tinymce) return;

          var ed = tinymce.get(editorId);
          if (!ed) return; // not initialized yet

          // Avoid double-binding per editor instance
          if (ed._proudWatchBound) return;
          ed._proudWatchBound = true;

          // Use a single handler (debounced) for typical TinyMCE activity
          var handler = function () {
            var info = {
              type: 'editor',
              event: 'tinymce',
              target: $ta[0],
              container: $container[0],
              editorId: editorId
            };
            markChanged(info);
            sendAjaxDebounced(info);
          };

          // Key events + content changes + undo/redo
          ed.on('keyup change input undo redo SetContent paste cut', handler);

          // Optional: when TinyMCE is removed/re-added (switching Visual/Text),
          // allow rebinding by clearing the flag when editor is removed.
          ed.on('remove', function () {
            ed._proudWatchBound = false;
          });
        }

        function bindAllTinyMceInContainer() {
          $container.find('textarea.wp-editor-area').each(function () {
            bindTinyMceForTextarea($(this));
          });
        }

        // Try now (for any editors already initialized)
        bindAllTinyMceInContainer();

        // Bind as WP initializes editors later.
        // WP typically passes (event, editor) where editor is the TinyMCE instance.
        $(document)
          .off('tinymce-editor-init' + ns)
          .on('tinymce-editor-init' + ns, function (e, editor) {
            // If WP provides the editor object, only bind if it lives in this container.
            if (editor && editor.id) {
              var $ta = $('#' + editor.id);
              if ($ta.length && $.contains($container[0], $ta[0])) {
                bindTinyMceForTextarea($ta);
              }
              return;
            }

            // Fallback: bind everything in the container
            bindAllTinyMceInContainer();
          });

      } // end watchMetaBox

      // Agenda tracking
      watchMetaBox('#meeting_agenda_meta_box', {
        nonce: (window.ProudMeeting && ProudMeeting.proudMeetingNonce) ? ProudMeeting.proudMeetingNonce : '',
        action: 'proud_track_metabox_change',
        metaKey: '_proud_meeting_agenda_modified',
        onChange: function (info) {
          $(info.container).addClass('has-changes');
        }
      });

      // Agenda Packet tracking
      watchMetaBox('#meeting_agenda_packet_meta_box', {
        nonce: (window.ProudMeeting && ProudMeeting.proudMeetingNonce) ? ProudMeeting.proudMeetingNonce : '',
        action: 'proud_track_metabox_change',
        metaKey: '_proud_meeting_agenda_packet_modified',
        onChange: function (info) {
          $(info.container).addClass('has-changes');
        }
      });

      // Minutes tracking
      watchMetaBox('#meeting_minutes_meta_box', {
        nonce: (window.ProudMeeting && ProudMeeting.proudMeetingNonce) ? ProudMeeting.proudMeetingNonce : '',
        action: 'proud_track_metabox_change',
        metaKey: '_proud_meeting_minutes_modified',
        onChange: function (info) {
          $(info.container).addClass('has-changes');
        }
      });


    } // end Proud.behaviors.proud_meeting
  };
})(jQuery, Proud);


