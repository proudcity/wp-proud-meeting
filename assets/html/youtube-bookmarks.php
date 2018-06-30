<div class="youtube-hidden" id="youtube-wrapper">

<hr/>

<div class="row">

  <div class="col-md-6 pull-right youtube-player-wrapper">
    <div id="player"></div>
  </div>

  <div class="col-md-6">
    <button id="youtube-add" class="btn btn-default margin-bottom"><i class="fa fa-plus-circle fa-fw"></i>Add bookmark at current video time</button>

    <div id="youtube-new" class="youtube-hidden margin-bottom">
      <button id="youtube-update-time" class="btn btn-default margin-bottom"><i class="fa fa-refresh fa-fw"></i>Update bookmark time</button>
      <div class="form-inline">
        <input id="youtube-new-label" type="text" class="form-control" placeholder="Enter label" />
        <div class="input-group">
          <input id="youtube-new-time" type="number" class="form-control input-youtube-time" />
          <div class="input-group-addon">seconds</div>
        </div>
        <button id="youtube-new-submit" type="submit" class="btn btn-primary mb-2">Add</button>
      </div>
    </div>

    <script id="youtube-list-template" type="text/x-handlebars-template">
      <ul class="list-group">
        {{#each youtube}}
        <li class="list-group-item">
          {{this.label}}
          <button type="button" class="close pull-right" aria-label="Close" data-youtube-remove="{{@index}}">
            <span aria-hidden="true">&times;</span>
          </button>
          <a href="#" data-youtube-seek="{{this.seconds}}" class="badge badge-default pull-right">
            {{this.time}}</a>
          </a>
        </li>
        {{else}}
        <p>Meeting videos can be long! Use bookmarks to help visitors find the part they are looking for.</p>
        <p>Press play, find the correct place in the video and click the button above to add a bookmark.</p>
        {{/each}}
      </ul>
    </script>

    <div id="youtube-list"></div>
  </div>

</div>

</div>