
class VGWSCore
  constructor: ->
    log.info 'Starting up VGWS JS...'
    @bodyCallbacks = []
    @bodyIsReady = false

  setRecurringTimer: (delay, callback) ->
    return setInterval callback, delay

  setOneShotTimer: (delay, callback) ->
    return setTimeout callback, delay

  eachSlowly = (selector, delay, callback, endCallback=null) ->
    for i in [0...selector.length]
      @setOneShotTimer i*delay, callback(i,selector[i])
    if endCallback
      @setOneShotTimer selector.length*delay, callback
    return

  beginWaiting: (title, message) ->
    @disableScroll()
    @Wait=new Modal()
    @Wait.Begin(title, message)
    return @Wait

  endWaiting: ->
    @Wait.End()
    @enableScroll()
    return

  api_get: (endpoint, action, data, callback) ->
    if action != null
      data['act'] = action
    data['ajax'] = 1
    $.ajax
      type:  'GET'
      url:    @buildURI endpoint
      data:   data
    .done callback
    return
    
  api_post: (endpoint, action, data, callback) ->
    if action != null
      data['act'] = action
    data['ajax'] = 1
    $.ajax
      type:  'POST'
      url:    @buildURI endpoint
      data:   data
    .done callback
    return

  whenReady: (cb) =>
    if @bodyIsReady
      cb()
      return
    @bodyCallbacks.push cb
    return

  buildWebURI : (args) ->
    o = WEB_ROOT + '/index.php'
    if args.length > 0
      i = 0
      while i < args.length
        o += '/' + args[i]
        i++
    return o
  buildAPIURI : (args) ->
    o = WEB_ROOT + '/api.php'
    if args.length > 0
      i = 0
      while i < args.length
        o += '/' + args[i]
        i++
    return o
