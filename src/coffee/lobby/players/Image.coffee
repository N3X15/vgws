class ImagePlayer extends Player
  @Types: [
    'jpg'
    'gif'
    'png'
    'webp'
  ]

  constructor: (cfg) ->
    super cfg
    @URL = @Config['url']
    @Element = null

  display: ->
    @Element = $('<div>')
      .attr 'id', 'background'
      .html "<img src=\"#{@URL}\">"
    $(document.body).append @Element
    return

  destroy: ->
    @Element.remove()
    return
    
Player.Register ImagePlayer
