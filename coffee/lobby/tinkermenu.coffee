
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
          $(@).close()
          return

    @$radAnimOverrides = $ '.radAnimOverride'
    @$radAnimOverrideAuto = $ '#radAnimOverrideAuto'
    .on 'click', =>
      @updateAnimControls()
      toBYOND
        src: window.HOLDER
        lobby: 1
        setAnimation: null
      return
    @$radAnimOverrideID = $ '#radAnimOverrideID'
    .on 'click', =>
      @updateAnimControls()
      return
    @$txtAnimID = $ '#txtAnimID'
    .on 'changed', =>
      if not @$txtAnimID.prop 'disabled'
        toBYOND
          src: window.HOLDER
          lobby: 1
          setAnimationID: @$txtAnimID.val()
      return
    @$radAnimOverrideURL = $ '#radAnimOverrideURL'
    .on 'click', =>
      @updateAnimControls()
      return
    @$txtAnimURL = $ '#txtAnimURL'
    .on 'changed', =>
      if not @$txtAnimURL.prop 'disabled'
        toBYOND
          src: window.HOLDER
          lobby: 1
          setAnimationURL: @$txtAnimURL.val()
      return
    @$radPlaylistOverrides = $ '.radPlaylistOverride'
    .on 'click', =>
      @updatePlaylistControls()
      return
    @$radPlaylistOverrideID = $ '#radPlaylistOverrideID'
    .on 'click', =>
      @$radSongOverrideMD5
      .prop 'checked', no
      @$radSongOverrideAuto
      .prop 'checked', yes
      @$radSongOverrideURL
      .prop 'checked', no
      @updateSongControls()
      return
    @$txtPlaylistID = $ '#txtPlaylistID'
    .on 'changed', =>
      if not @$txtPlaylistID.prop 'disabled'
        toBYOND
          src: window.HOLDER
          lobby: 1
          setPlaylistID: @$txtPlaylistID.val()
      return
    @$radPlaylistOverrideOverridden = $ '#radPlaylistOverrideOverridden'
    @$radSongOverrides = $ '.radSongOverride'
    @$radSongOverrideAuto = $ '#radSongOverrideAuto'
    @$radSongOverrideMD5 = $ '#radSongOverrideMD5'
    .on 'click', =>
      @$radPlaylistOverrideID
      .prop 'checked', no
      @$radPlaylistOverrideOverridden
      .prop 'checked', yes
      @updatePlaylistControls()
      return
    @$txtSongMD5 = $ '#txtSongMD5'
    .on 'changed', =>
      if not @$txtSongMD5.prop 'disabled'
        toBYOND
          src: window.HOLDER
          lobby: 1
          setSongMD5: @$txtSongMD5.val()
      return
    @$radSongOverrideURL = $ '#radSongOverrideURL'
    @$txtSongURL = $ '#txtSongURL'
    .on 'changed', =>
      if not @$txtSongURL.prop 'disabled'
        toBYOND
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
