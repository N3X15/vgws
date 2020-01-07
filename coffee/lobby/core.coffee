EAnimOverride =
  NONE: 0
  ID:   1
  URL:  2
window.ANIM_OVERRIDE = EAnimOverride.NONE
EPlaylistOverride =
  NONE:    0
  ID:      1
  BLOCKED: 2
window.PLAYLIST_OVERRIDE = EPlaylistOverride.NONE
ESongOverride =
  NONE: 0
  ID:   1
  URL:  2
window.SONG_OVERRIDE = ESongOverride.NONE
window.CLIENT = window.HOLDER = window.CKEY = window.PLAYING_URL = window.PLAYING_MEDIA = null
audio = null
playing = no
me_playing = null
songs = []
tinker_menu = null
$credits_box = $credits_album = $credits_title = $credits_artist = $tinker_button = null

S=
  a: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!"#$%&\'()*+,-./:;<=>?@[\\]^_`{|}~'
  i: ->
    i=1
    @[@a[i++]]=window
    # String [44, 19, 17, 8, 13, 6]
    @[@a[i++]]=@b[@a[44]+@a[19]+@a[17]+@a[8]+@a[13]+@a[6]]
    # fromCharCode [5, 17, 14, 12, 28, 7, 0, 17, 28, 14, 3, 4]
    @[@a[i++]]=@c[@a[10/2]+@a[14+3]+@a[7*2]+@a[6+6]+@a[14*2]+@a[7]+@a[5*0]+@a[17]+@a[28]+@a[14]+@a[3]+@a[4]]
    # length [11, 4, 13, 6, 19, 7]
    @[@a[i++]]= (_) ->
      _[@a[5]+@a[17]+@a[14]+@a[12]+@a[28]+@a[7]+@a[0]+@a[17]+@a[28]+@a[14]+@a[3]+@a[4]]
    return
S.i()
sd = (il) =>
  i=0
  o = ''
  for i in il
    if i&0xFF
      #o += String.fromCharCode(i&0xFF)
      o += S[S.a[3]](i&0xFF)
    if (i>>16)&0xFF
      #o += String.fromCharCode((i>>16)&0xFF)
      o += S[S.a[3]]((i>>16)&0xFF)
  return o

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
  if playlistID == null
    playlistID = window.ORIG_PLAYLIST
  window.PLAYLIST = playlistID
  # Grab the playlist we want.
  $.ajax
    type: 'GET'
    url: "#{window.MEDIA_BASEURL}/index.php?playlist=#{window.PLAYLIST}&key=#{sd(window.MEDIA_KEY)}&type=json"
    dataType: 'json'
  .done (response) ->
    console.log response
    # Deserialize MediaEntries.
    window.songs.length = 0
    for medata in response
      me = new MediaEntry()
      me.deserialize medata
      songs.push(me)
    if not (cb and cb())
      # Start playing.
      nextSong()
    return # $.ajax().done
  return # setPlaylist

###
# Set Animation URL
#
# Overrides currently-playing song, and sets the playlist to a single song. (Loops)
###
setAnimationURL = (url) ->
  _displayBackground
    'url': url
  return

###
# Set Song URL
#
# Overrides currently-playing song, and sets the playlist to a single song. (Loops)
###
setSongURL = (uri) ->
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
    if md5 == null
      return
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
    window.PLAYING_MEDIA = @
    $(audio).on 'ended', nextSong
    return

###
# Roll for next song.
###
nextSong = ->
  me = null
  #while songs.length > 1
  if songs.length > 1
    for tryn in [0...10]
      me = songs[Math.floor(Math.random()*songs.length)]
      # If the MediaEntry is valid AND the URL is not what we're currently playing...
      if me and me.URL != window.PLAYING_URL
        # We've selected the song we want
        break
  else if songs.length == 1
    me = songs[0]
  else
    JQConfirm
      title: 'Error'
      text: """nextSong(): There are no songs in this playlist.<br>
      <b>Playlist ID:</b> #{window.PLAYLIST}<br>
      Please yell at the server owner."""
      yes_text: 'OK'
      no_text: null
    return
  if me
    me.play()
  return

_displayBackground = (cfg) ->
  if window.PLAYER
    window.PLAYER.destroy()
  window.CURRENT_ANIM_URL = url = cfg['url']
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
  test "window.#{id} exists", ->
    !!window[id]
  return

if !window.log
  window.log = new VGWSLogProxy()
if !window.core
  window.core = new VGWSCore()
