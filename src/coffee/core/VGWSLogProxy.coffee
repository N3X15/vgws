export ELogLevels =
  DEBUG: 0
  INFO: 1
  WARNING: 2
  ERROR: 3
  CRITICAL: 4

export class VGWSLogProxy
  constructor: ->
    @Level = 2

  _processargs: (level, message, args...) ->
    if typeof(message) == 'string'
      message = "[#{level.padStart(7, ' ')}] #{message}"
    args.unshift message
    return args

  debug: (message, args...) ->
    if console and @Level <= 4
      newargs = @_processargs 'DEBUG', message, args...
      console.debug newargs...

  info: (message, args...) ->
    if console and @Level <= 3
      newargs = @_processargs 'INFO', message, args...
      console.log newargs...

  warn: (message, args...) ->
    if console and @Level <= 2
      newargs = @_processargs 'WARNING', message, args...
      console.warn newargs...

  error: (message, args...) ->
    if console and @Level <= 1
      newargs = @_processargs '!ERROR!', message, args...
      console.error newargs...
