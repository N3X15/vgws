audio = null
playing = no
songs = []
$credits_box = $credits_album = $credits_title = $credits_artist = null

# Utility, not really useful to BYOND
findBaseName = (url) ->
  fileName = url.substring(url.lastIndexOf('/') + 1)
  dot = fileName.lastIndexOf('.')
  if dot == -1 then fileName else fileName.substring(0, dot)

###
# Set Playlist
#
# Sets playlist and downloads song list from media server.
###
setPlaylist = (playlistID)->
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
    # Start playing.
    nextSong()
    return # $.ajax().done
  return # setPlaylist

###
# Set Media URL
#
# Overrides currently-playing song, and sets the playlist to a single song.
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
      audio = null
    audio = new Audio @URL
    audio.play()
    $(audio).on 'ended', nextSong
    return

nextSong = ->
  me = null
  while songs.length > 1
    me = songs[Math.floor(Math.random()*songs.length)]
    if me and me.URL != @URL
      break
  me.play()
  return

core.whenReady ->
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
    setPlaylist(window.PLAYLIST)
    return # setOneShotTimer
  return # whenReady
