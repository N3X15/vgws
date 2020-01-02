class VideoPlayer extends Player
  @Types: [
    'webm'
    'mp4'
    'ogv'
  ]

  @TypeMap:
    'webm': 'video/webm'
    'mp4':  'video/mpeg'
    'ogv':  'video/ogg'

  constructor: (cfg) ->
    super cfg
    @URL = @Config['url']
    @Element = null
    @VideoElement = null

  display: ->
    @Element = $('<div>')
      .attr 'id', 'background'
    ve = $ '<video>'
      .prop 'loop',     yes
      .prop 'muted',    yes
      .prop 'autoplay', yes
      .appendTo @Element
    $ '<source>'
      .attr 'src', @URL
      .attr 'type', @constructor.TypeMap[getExt(@URL)]
      .appendTo ve

    $(document.body).append @Element
    return

  destroy: ->
    @Element.remove()
    return
Player.Register VideoPlayer
