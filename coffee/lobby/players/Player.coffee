window.PLAYERS={}

getExt = (url) ->
  return url
    .split '.'
    .pop()

class Player
  @Types: []

  @Register: (cls) ->
    for typ in cls.Types
      window.PLAYERS[typ] = cls
    return

  constructor: (@Config) ->

  display: ->
    return
  destroy: ->
    return
