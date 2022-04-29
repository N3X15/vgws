

import {core, log} from '../core/zzStartup.coffee'

core.whenReady ->
  body = $(document.body)

  ## Set up testing environment.
  window.$tests = $ '<ol>'
  .attr 'id', 'tests'
  .css 'position', 'fixed'
  .css 'top', '0'
  .css 'left', '0'
  .appendTo body

  ## Test player's browser for functionality.
  # Ensure Audio API is present
  assertExists 'Audio'
  # Ensure polyfills worked
  assertExists 'Object'
  assertExists 'URLSearchParams'

  result = if (ntests-goodtests) > 0 then 'FAILED' else 'PASSED'
  $ '<li>'
  .text "Diagnostic tests #{result}: #{goodtests}✓ #{ntests-goodtests}✗"
  .css 'color', '#f00'
  .appendTo window.$tests

  if goodtests == ntests
    # Hide test report
    window.$tests.hide()
  else
    # Stop execution, leave test results up, remove dlgTinkerMenu prototype.
    $('#dlgTinkerMenu').hide()
    return

  # Setup admin menu
  tinker_menu = new TinkerMenu()

  # Before we do any more, let's check for overrides from the server.
  window.query = query = new URLSearchParams window.location.search

  # Set up our BYOND vars.
  if query.has 'src'
    window.CLIENT = query.get 'src'
  if query.has 'holder'
    window.HOLDER = query.get 'holder'
  if query.has 'ckey'
    window.CKEY = query.get 'ckey'

  window.ORIG_PLAYLIST = window.PLAYLIST
  window.ORIG_ANIM_URL = window.ANIMATION.url

  anim = window.ANIMATION
  if query.has 'bg'
    anim =
      'url': query.get 'bg'
    window.ANIM_OVERRIDE = EAnimOverride.URL
  _displayBackground anim

  $credits_box = $ '<span>'
  .attr 'id', 'credits-box'
  $credits_title = $ '<span>'
  .attr 'id', 'credits-title'
  $credits_artist = $ '<span>'
  .attr 'id', 'credits-author'
  $credits_album = $ '<span>'
  .attr 'id', 'credits-album'
  $credits_box.append $credits_title
  $credits_box.append $credits_artist
  $credits_box.append $credits_album
  $(document.body).append $credits_box

  if window.HOLDER
    $tinker_button = $ '<span>'
    .attr 'id', 'tinker-button'
    .on 'click', ->
      log.info "CLICKED"
      tinker_menu.init()
      tinker_menu.show()
      return
    .appendTo $(document.body)

    $ '<img>'
    .attr 'src', window.WEB_ROOT+'/img/gear.svg'
    .appendTo $tinker_button

  core.setOneShotTimer 1000, ->
    window.PLAYLIST_OVERRIDE = EPlaylistOverride.BLOCKED
    if query.has 'song_url'
      window.SONG_OVERRIDE = ESongOverride.URL
      setMediaURL(query.get('song_url'))
    else if query.has 'song_id'
      window.SONG_OVERRIDE = ESongOverride.ID
      setPlaylistID window.PLAYLIST, ->
        setSongMD5(query.get('song_id'))
        return # setPlaylistID
    else
      window.PLAYLIST_OVERRIDE = EPlaylistOverride.ID
      setPlaylistID window.PLAYLIST
    return # setOneShotTimer
  return # whenReady
