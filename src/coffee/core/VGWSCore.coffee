export class VGWSCore
  constructor: ->
    log.info 'Starting up VGWS JS...'
    @bodyCallbacks = []
    @bodyIsReady = false
    @Wait = null

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

  _build_path: (base, path, get_args={}, anchor='') ->
    o = ''+base
    if path.length > 0
      for entry in path
        o += '/'+entry
    if Object.keys(get_args).length > 0
      o += '?'
      for k,v of get_args
        o += k+'='+encodeURIComponent(v)
    if anchor.length > 0
      o += '#'+encodeURIComponent(anchor)
    return o

  buildWebURI : (path, get_args={}, anchor='') ->
    return @_build_path INDEX_PHP_URL, path, get_args, anchor

  buildAPIURI : (args) ->
    return @_build_path API_PHP_URL, path, get_args, anchor
