
class TinkerMenu
  constructor: ->
    @Element = $('#dlgTinkerMenu')
    console.log @Element
    @Dialog = @Element.dialog
      autoOpen: false
      resizable: false
      modal: true
      height: "auto"
      width: "auto"
      buttons:
        'OK': (ui, e) ->
          $(@).dialog 'close'
          return

    $ '#dlgTinkerMenu input[type=text]'
    .on 'keyup', (e) =>
      # Force change event when enter key pressed
      if not $(e.target).prop 'disabled'
        switch e.which
          when 13
            $(e.target).trigger 'commit'
          else
            $(e.target).addClass('modified')
      return
    @$radAnimOverrides = $ '.radAnimOverride'
    .on 'change', =>
      @updateAnimControls()
      return
    @$radAnimOverrideAuto = $ '#radAnimOverrideAuto'
    .on 'change', =>
      if not @$radAnimOverrideAuto.prop 'checked'
        return
      @$txtAnimID.removeClass('modified').val()
      @$txtAnimURL.removeClass('modified').val()
      toByond
        src: window.HOLDER
        lobby: 1
        setAnimationID: null
      return
    @$radAnimOverrideID = $ '#radAnimOverrideID'
    @$txtAnimID = $ '#txtAnimID'
    .on 'commit', (e) =>
      if not @$txtAnimID.prop 'disabled'
        $(e.target).removeClass('modified')
        toByond
          src: window.HOLDER
          lobby: 1
          setAnimationID: @$txtAnimID.val()
      return
    @$radAnimOverrideURL = $ '#radAnimOverrideURL'
    .on 'change', (e) =>
      if not @$txtAnimURL.prop 'disabled'
        $(e.target).removeClass('modified')
        toByond
          src: window.HOLDER
          lobby: 1
          setAnimationURL: @$txtAnimURL.val()
      return
    @$txtAnimURL = $ '#txtAnimURL'
    .on 'commit', (e) =>
      if not @$txtAnimURL.prop 'disabled'
        $(e.target).removeClass('modified')
        if query.has 'TEST'
          setAnimationURL @$txtAnimURL.val()
        toByond
          src: window.HOLDER
          lobby: 1
          setAnimationURL: @$txtAnimURL.val()
      return
    @$radPlaylistOverrides = $ '.radPlaylistOverride'
    .on 'change', =>
      @updatePlaylistControls()
      return
    @$radPlaylistOverrideID = $ '#radPlaylistOverrideID'
    .on 'change', =>
      if not @$radPlaylistOverrideID.prop 'checked'
        return
      @$radSongOverrideMD5
      .prop 'checked', no
      @$radSongOverrideAuto
      .prop 'checked', yes
      @$radSongOverrideURL
      .prop 'checked', no
      @updateSongControls()
      return
    @$txtPlaylistID = $ '#txtPlaylistID'
    .on 'commit', (e) =>
      if not @$txtPlaylistID.prop 'disabled'
        $(e.target).removeClass('modified')
        if query.has 'TEST'
          setPlaylistID @$txtPlaylistID.val()
        toByond
          src: window.HOLDER
          lobby: 1
          setPlaylistID: @$txtPlaylistID.val()
      return
    @$radPlaylistOverrideOverridden = $ '#radPlaylistOverrideOverridden'
    @$radSongOverrides = $ '.radSongOverride'
    .on 'change', =>
      @updateSongControls()
      return
    @$radSongOverrideAuto = $ '#radSongOverrideAuto'
    @$radSongOverrideMD5 = $ '#radSongOverrideMD5'
    .on 'change', =>
      if not @$radSongOverrideMD5.prop 'checked'
        return
      @$radPlaylistOverrideID
      .prop 'checked', no
      @$radPlaylistOverrideOverridden
      .prop 'checked', yes
      @updatePlaylistControls()
      return
    @$txtSongMD5 = $ '#txtSongMD5'
    .on 'commit', (e) =>
      if not @$txtSongMD5.prop 'disabled'
        $(e.target).removeClass('modified')
        if query.has 'TEST'
          setSongMD5 @$txtSongMD5.val()
        toByond
          src: window.HOLDER
          lobby: 1
          setSongMD5: @$txtSongMD5.val()
      return
    @$radSongOverrideURL = $ '#radSongOverrideURL'
    .on 'change', =>
      if not @$radSongOverrideURL.prop 'checked'
        return
      @$radPlaylistOverrideID
      .prop 'checked', no
      @$radPlaylistOverrideOverridden
      .prop 'checked', yes
      @updatePlaylistControls()
      return
    @$txtSongURL = $ '#txtSongURL'
    .on 'commit', (e) =>
      if not @$txtSongURL.prop 'disabled'
        $(e.target).removeClass('modified')
        if query.has 'TEST'
          setSongURL @$txtSongURL.val()
        toByond
          src: window.HOLDER
          lobby: 1
          setSongURL: @$txtSongURL.val()
      return

  init: ->
    @loadVals()
    @updateControls()
    return

  updateControls: ->
    @updateAnimControls()
    @updatePlaylistControls()
    @updateSongControls()
    return

  updateAnimControls: ->
    @$txtAnimID.prop 'disabled', not @$radAnimOverrideID.prop 'checked'
    @$txtAnimURL.prop 'disabled', not @$radAnimOverrideURL.prop 'checked'
    return

  updatePlaylistControls: ->
    @$txtPlaylistID.prop 'disabled', not @$radPlaylistOverrideID.prop 'checked'
    return

  updateSongControls: ->
    @$txtSongMD5.prop 'disabled', not @$radSongOverrideMD5.prop 'checked'
    @$txtSongURL.prop 'disabled', not @$radSongOverrideURL.prop 'checked'
    return

  loadVals: ->
    @loadValsIntoAnimControls()
    @loadValsIntoPlaylistControls()
    @loadValsIntoSongControls()
    return

  loadValsIntoAnimControls: ->
    switch window.ANIM_OVERRIDE
      when EAnimOverride.URL
        @$radAnimOverrideAuto.prop 'checked', no
        @$radAnimOverrideID.prop   'checked', no
        @$radAnimOverrideURL.prop  'checked', yes
        @$txtAnimURL.val window.query.get 'bg'
      when EAnimOverride.ID
        @$radAnimOverrideAuto.prop 'checked', no
        @$radAnimOverrideID.prop   'checked', yes
        @$radAnimOverrideURL.prop  'checked', no
        @$txtAnimID.val window.query.get 'anim'
      else
        @$radAnimOverrideAuto.prop 'checked', yes
        @$radAnimOverrideID.prop   'checked', no
        @$radAnimOverrideURL.prop  'checked', no
    return
  loadValsIntoSongControls: ->
    # Remember that MediaServer URLs differ for each IP
    #@$txtSongURL.val window.PLAYING_URL
    @$txtSongMD5.val window.PLAYING_MEDIA.MD5
    switch window.SONG_OVERRIDE
      when ESongOverride.URL
        @$radSongOverrideAuto.prop 'checked', no
        @$radSongOverrideMD5.prop  'checked', no
        @$radSongOverrideURL.prop  'checked', yes
      when ESongOverride.ID
        @$radSongOverrideAuto.prop 'checked', no
        @$radSongOverrideMD5.prop  'checked', yes
        @$radSongOverrideURL.prop  'checked', no
      else
        @$radSongOverrideAuto.prop 'checked', yes
        @$radSongOverrideMD5.prop  'checked', no
        @$radSongOverrideURL.prop  'checked', no
    return
  loadValsIntoPlaylistControls: ->
    switch window.PLAYLIST_OVERRIDE
      when EPlaylistOverride.ID
        @$radPlaylistOverrideID.prop          'checked', yes
        @$radPlaylistOverrideOverridden.prop  'checked', no
        @$txtPlaylistID.val window.PLAYLIST
      else
        @$radPlaylistOverrideID.prop          'checked', no
        @$radPlaylistOverrideOverridden.prop  'checked', yes
    return

  hide: ->
    @Dialog.dialog 'close'
    return
  show: ->
    @Dialog.dialog 'open'
    return
