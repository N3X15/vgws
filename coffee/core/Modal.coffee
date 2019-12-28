# From ChanMan, another project of mine
class Modal
  constructor: ->
    @BackgroundElement = null
    @DialogElement = null
    @TitleElement = null
    @MessageElement = null
    @SpinnerElement = null

  @property 'Title',
    get: ->
      return @TitleElement.text()
    set: (val) ->
      @TitleElement.text val
      return

  @property 'Message',
    get: ->
      return @MessageElement.text()
    set: (val) ->
      @MessageElement.text val
      return

  @property 'MessageHTML',
    get: ->
      return @MessageElement.html()
    set: (val) ->
      @MessageElement.html val
      return

  Begin: (initial_title, initial_message)->
    @BackgroundElement=$('<div class="modalbg" style="display:none">')

    @DialogElement=$('<div class="modal">').appendTo @BackgroundElement
    @TitleElement=$('<header class="title">').text(initial_title).appendTo @DialogElement
    @SpinnerElement=$('<span class="spinner">').appendTo @DialogElement
    @MessageElement=$('<div class="message">').html(initial_message).appendTo @DialogElement
    @BackgroundElement.appendTo('body').fadeIn('slow')
    return

  End: ->
    if @BackgroundElement
      @BackgroundElement.fadeOut 'slow', ->
        $(@).remove()
    @BackgroundElement=null
    @DialogElement=null
    @TitleElement=null
    @SpinnerElement=null
    @MessageElement=null
    return
