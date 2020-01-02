audio = null
playing = no
me_playing = null
songs = []
$credits_box = $credits_album = $credits_title = $credits_artist = null

# Utility, not really useful to BYOND
findBaseName = (url) ->
  fileName = url.substring(url.lastIndexOf('/') + 1)
  dot = fileName.lastIndexOf('.')
  if dot == -1 then fileName else fileName.substring(0, dot)

###
# Set Playlist ID
#
# Sets playlist and downloads song list from media server.
###
setPlaylistID = (playlistID, cb=null)->
  window.PLAYLIST = playlistID
  # Grab the playlist we want.
  $.ajax
    type: 'GET'
    url: "#{window.MEDIA_BASEURL}/index.php?playlist=#{window.PLAYLIST}&key=#{window.MEDIA_KEY}&type=json"
    dataType: 'json'
  .done (response) ->
    console.log response
    # Deserialize MediaEntries.
    window.songs.length = 0
    for medata in response
      me = new MediaEntry()
      me.deserialize medata
      songs.push(me)
      cb and cb()
    # Start playing.
    nextSong()
    return # $.ajax().done
  return # setPlaylist

###
# Set Media URL
#
# Overrides currently-playing song, and sets the playlist to a single song. (Loops)
###
setAnimationURL = (url) ->
  _displayBackground
    'url': url
  return

###
# Set Media URL
#
# Overrides currently-playing song, and sets the playlist to a single song. (Loops)
###
setMediaURL = (uri) ->
  me = new MediaEntry()
  window.songs.length = 0
  songs.push me
  me.URL = uri
  me.OrigFileName = findBaseName(me.URL)
  me.MD5 = ''
  me.Length = -1 # Not actually used but whatever
  me.play()
  return

###
# Set Song MD5
# Play song from playlist based on MD5. (Loops)
###
setSongMD5 = (md5) ->
  setPlaylistID window.PLAYLIST, ->
    desired = null
    for me in window.songs
      if me and me.MD5 == md5
        desired = me
        break
    if desired
      window.songs = [desired]
      desired.play()
    return
  return

class MediaEntry
  constructor: ->
    @Title = ''
    @Artist = ''
    @Album = ''
    @URL = ''
    @MD5 = ''
    @OrigFileName = ''

    @Length = 0

  deserialize: (data) ->
    @Title = if 'title' of data then data['title'] else ''
    @Artist = if 'artist' of data then data['artist'] else ''
    @Album = if 'album' of data then data['album'] else ''
    @Length = data['length']
    @URL = data['url']
    @MD5 = data['md5']
    @OrigFileName = if 'orig_filename' of data then data['orig_filename'] else ''

  play: ->
    if !@Artist and !@Album and !@Title
      @Title = "Untitled"
      @Album = if !@OrigFileName then "#{@MD5}.mp3" else @OrigFileName
    $credits_artist.text @Artist
    $credits_album.text @Album
    $credits_title.text @Title
    $credits_box
      .fadeIn 3000 # 3s
      .delay 10000 # 10s
      .fadeOut 3000 # 3s
    if audio
      audio.pause()
      $(audio).off 'ended' # Or we get double-plays
      audio = null
    audio = new Audio @URL
    audio.play()
    window.PLAYING_URL = @URL
    me_playing = @
    $(audio).on 'ended', nextSong
    return

nextSong = ->
  me = null
  while songs.length > 1
    me = songs[Math.floor(Math.random()*songs.length)]
    if me and me.URL != window.PLAYING_URL
      break
  if me
    me.play()
  return

_displayBackground = (cfg) ->
  if window.PLAYER
    window.PLAYER.destroy()
  url = cfg['url']
  window.PLAYER = player = new window.PLAYERS[getExt(url)] cfg
  player.display()
  return

ntests = 0
goodtests = 0
test = (id, callback) ->
  ntests++
  success = callback()
  $ '<li>'
  .text "#{id}: #{if success then 'OK' else 'FAIL'}"
  .css 'color', if success then '#0f0' else '#f00'
  .appendTo window.$tests
  if success
    goodtests++
  return

assertExists = (id) ->
  test id, ->
    !!window[id]
  return

if !window.log
  window.log = new VGWSLogProxy()
if !window.core
  window.core = new VGWSCore()

core.whenReady ->
  #window.onload = ->
  body = $(document.body).html('')
  window.$tests = $ '<ol>'
  .attr 'id', 'tests'
  .css 'position', 'fixed'
  .css 'top', '0'
  .css 'left', '0'
  .appendTo body

  assertExists 'Audio'
  assertExists 'URLSearchParams'

  if goodtests == ntests
    window.$tests.remove()
  else
    return


  # Before we do any more, let's check for overrides from the server.
  window.query = query = new URLSearchParams window.location.search

  anim = window.ANIMATION
  if query.has 'bg'
    anim =
      'url': query.get 'bg'
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

  core.setOneShotTimer 1000, ->
    if query.has 'song_url'
      setMediaURL(query.get('song_url'))
    else if query.has 'song_id'
      setPlaylistID window.PLAYLIST, ->
        setSongMD5(query.get('song_id'))
        return # setPlaylistID
    else
      setPlaylistID window.PLAYLIST
    return # setOneShotTimer
  return # whenReady
